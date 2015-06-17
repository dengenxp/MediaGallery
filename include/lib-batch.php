<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | lib-batch.php                                                            |
// |                                                                          |
// | batch process management                                                 |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2015 by the following authors:                             |
// |                                                                          |
// | Yoshinori Tahara       taharaxp AT gmail DOT com                         |
// |                                                                          |
// | Based on the Media Gallery Plugin for glFusion CMS                       |
// | Copyright (C) 2002-2008 by the following authors:                        |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// +--------------------------------------------------------------------------+
// |                                                                          |
// | This program is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU General Public License              |
// | as published by the Free Software Foundation; either version 2           |
// | of the License, or (at your option) any later version.                   |
// |                                                                          |
// | This program is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with this program; if not, write to the Free Software Foundation,  |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.          |
// |                                                                          |
// +--------------------------------------------------------------------------+

if (strpos(strtolower($_SERVER['PHP_SELF']), strtolower(basename(__FILE__))) !== false) {
    die('This file can not be used on its own!');
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/lib/imglib/lib-image.php';

/**
* creates a new batch session id
*
* @parm     char action to be performed
* @return   int  false if error, session_id if OK
*
*/
function MG_beginSession($action, $origin, $description, $flag0='', $flag1='', $flag2='', $flag3='', $flag4='')
{
    global $_TABLES, $_USER, $_MG_CONF;

    // create a new session_id
    $session_id          = COM_makesid();
    $session_uid         = intval($_USER['uid']);
    $session_status      = 1;  // 0 = complete, 1 = active, 2 = aborted ?? 0 not started, 1 started, 2 complete, 3 aborted?
    $session_action      = $action;
    $session_start_time  = time();
    $session_end_time    = time();
    $session_description = addslashes($description);
    $flag0               = addslashes($flag0);
    $flag1               = addslashes($flag1);
    $flag2               = addslashes($flag2);
    $flag3               = addslashes($flag3);
    $flag4               = addslashes($flag4);

    $sql = "INSERT INTO {$_TABLES['mg_sessions']} "
         . "(session_id, session_uid, session_description, "
         . "session_status, session_action, session_origin, "
         . "session_start_time, session_end_time, session_var0, "
         . "session_var1, session_var2, session_var3, session_var4) "
         . "VALUES "
         . "('$session_id', $session_uid, '$session_description', "
         . "$session_status, '$session_action', '$origin', "
         . "$session_start_time, $session_end_time, '$flag0', "
         . "'$flag1', '$flag2', '$flag3', '$flag4')";
    $result = DB_query($sql, 1);
    if (DB_error()) {
        COM_errorLog("MediaGallery: Error - Unable to create new batch session");
        return false;
    }
    return $session_id;
}

/**
 * Continues a session - handles timeout, looping, etc.
 *
 * @parm    char    session id to continue
 * @parm    int     number of items to process per run
 *                  0 indicates initial run
 * @return  char    HTML of status screen
 */
function MG_continueSession($session_id, $item_limit, $refresh_rate)
{
    global $_CONF, $_MG_CONF, $_TABLES, $_USER, $LANG_MG00, $LANG_MG01, $LANG_MG02;

    $retval = '';

    $cycle_start_time = time();

    $temp_time = array();
    $timer_expired = false;
    $num_rows = 0;

    $session_id = COM_applyFilter($session_id);

    // Pull the session status info
    $sql = "SELECT * FROM {$_TABLES['mg_sessions']} "
         . "WHERE session_id='" . addslashes($session_id) . "'";
    $result = DB_query($sql, 1);
    if (DB_error()) {
        COM_errorLog("MediaGallery:  Error - Unable to retrieve batch session data");
        return '';
    }

    $nRows = DB_numRows($result);
    if ($nRows > 0) {
        $session = DB_fetchArray($result);
    } else {
        COM_errorLog("MediaGallery: Error - Unable to find batch session id");
        return '';      // no session found
    }

    // security check - make sure we are continuing a session that we own...
    if ($session['session_uid'] != $_USER['uid'] && !SEC_hasRights('mediagallery.admin')) {
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    // Setup timer information

    $time_limit = $_MG_CONF['def_time_limit'];

    @set_time_limit($time_limit + 20);

    // get execution time
    $max_execution_time = ini_get('max_execution_time');

    if ($time_limit > $max_execution_time) {
        $time_limit = $max_execution_time;
    }

    $label = COM_stripslashes($session['session_description']);
    // Pull the detail data from the sessions_items table...

    $sql = "SELECT * FROM {$_TABLES['mg_session_items']} "
         . "WHERE session_id='" . addslashes($session_id) . "' "
         . "AND status=0 LIMIT " . $item_limit;
    $result = DB_query($sql);

    while (($row = DB_fetchArray($result)) && ($timer_expired == false)) {
        // used for calculating loop duration and changing the timer condition
        $start_temp_time = time();

        $function = 'mg_batch_session_' . $session['session_action'];
        if (function_exists($function)) {
            $function($row);
            DB_change($_TABLES['mg_session_items'], 'status', 1, 'id', $row['id']);
        }

        // calculate time for each loop iteration
        $temp_time[$num_rows] = time() - $start_temp_time;
        // get the max
        $timer_time = max($temp_time);

        $num_rows++;

        // check if timer is about to expire
        if (time() - $cycle_start_time >= $time_limit - $timer_time) {
            $timer_expired_secs = time() - $cycle_start_time;
            $timer_expired = true;
        }
    }

    // end the timer
    $cycle_end_time = time();

    // find how much time the last cycle took
    $last_cycle_time = $cycle_end_time - $cycle_start_time;

    $T = COM_newTemplate(MG_getTemplatePath(0));
    $T->set_file('batch', 'batch_progress.thtml');
    $processing_messages = '';
    if ($timer_expired) {
        $processing_messages = '<p>' . sprintf($LANG_MG01['timer_expired'], $timer_expired_secs) . '</p>';
    }

    $sql = "SELECT COUNT(*) AS processed "
         . "FROM {$_TABLES['mg_session_items']} "
         . "WHERE session_id='" . addslashes($session_id) . "' AND status=1";
    $result = DB_query($sql);
    $row = DB_fetchArray($result);
    $session_items_processed = $row['processed'];

    $sql = "SELECT COUNT(*) AS processing "
         . "FROM {$_TABLES['mg_session_items']} "
         . "WHERE session_id='" . addslashes($session_id) . "'";
    $result = DB_query($sql);
    $row = DB_fetchArray($result);
    $session_items_processing = $row['processing'];

    $items_remaining = $session_items_processing - $session_items_processed;

    if ($items_remaining > 0) {
        if ($item_limit == 0) {
            $processing_messages .= '<p>' . $LANG_MG01['begin_processing'] . '</p>';
            $item_limit = $_MG_CONF['def_item_limit'];
        } else {
            $processing_messages .= '<p>' . sprintf($LANG_MG01['processing_next_items'], $item_limit) . '</p>';
        }
        $form_action = $_MG_CONF['site_url'] . '/batch.php?mode=continue&amp;sid=' . $session_id
                     . '&amp;refresh=' . $refresh_rate . '&amp;limit=' . $item_limit;
        $next_button = $LANG_MG01['next'];
        // create the meta tag for refresh
        $T->set_var("META", '<meta http-equiv="refresh" content="' . $refresh_rate . ';url=' . $form_action . '"' . XHTML . '>');
    } else {
        if ($item_limit == 0) {
            echo COM_refresh($session['session_origin']);
            exit;
        }
        $next_button = $LANG_MG01['finished'];
        $processing_messages .= '<p>' . $LANG_MG01['all_done'] . '</p>';
        $T->set_var("META", '');
        $refresh_rate = -1;
        $form_action = $session['session_origin'];
        $result = DB_query("SELECT * FROM {$_TABLES['mg_session_log']} "
                         . "WHERE session_id='" . addslashes($session_id) . "'");
        while ($row = DB_fetchArray($result)) {
            $processing_messages .= '<p>' . $row['session_log'] . '</p>';
        }
        MG_endSession($session_id);
    }

    $session_percent = ($session_items_processed / $session_items_processing) * 100;
    $session_time    = $cycle_end_time - $session['session_start_time'];

    $T->set_var(array(
        'L_BATCH_PROCESS'      => $label,
        'L_BATCH'              => $LANG_MG01['batch_sessions'],
        'L_NEXT'               => $next_button,
        'L_PROCESSING'         => $LANG_MG01['processing'],
        'L_CANCEL'             => $LANG_MG01['cancel'],
        'L_PROCESSING_DETAILS' => $LANG_MG01['processing_details'],
        'L_STATUS'             => $LANG_MG01['status'],
        'L_TOTAL_ITEMS'        => $LANG_MG01['total_items'],
        'L_ITEMS_PROCESSED'    => $LANG_MG01['processed_items'],
        'L_ITEMS_REMAINING'    => $LANG_MG01['items_remaining'],
        'L_POSTS_LAST_CYCLE'   => $LANG_MG01['items_last_cycle'],
        'L_TIME_LIMIT'         => $LANG_MG01['time_limit'],
        'L_REFRESH_RATE'       => $LANG_MG01['refresh_rate'],
        'L_ITEM_RATE'          => $LANG_MG01['item_rate'],
        'L_ACTIVE_PARAMETERS'  => $LANG_MG01['batch_parameters'],
        'L_ITEMS_PER_CYCLE'    => $LANG_MG01['items_per_cycle'],
        'TOTAL_ITEMS'          => $session_items_processing,
        'ITEMS_PROCESSED'      => $session_items_processed,
        'ITEMS_REMAINING'      => $session_items_processing - $session_items_processed,
        'ITEM_RATE'            => sprintf($LANG_MG01['seconds_per_item'],round(@($last_cycle_time / $num_rows))),
        'PROCESSING_MESSAGES'  => $processing_messages,
        'SESSION_PERCENT'      => round($session_percent, 2) . ' %',
        'POST_LIMIT'           => $num_rows,
        'ITEM_LIMIT'           => $item_limit,
        'TIME_LIMIT'           => $time_limit,
        'REFRESH_RATE'         => $refresh_rate,
        'S_BATCH_ACTION'       => $form_action
    ));
    $retval .= $T->finish($T->parse('output', 'batch'));
    return $retval;
}

function MG_registerSession($info=array())
{
    global $_TABLES;

    $session_id = addslashes($info['session_id']);
    $mid    = isset($info['mid'])    ? addslashes($info['mid'])   : '';
    $aid    = isset($info['aid'])    ? intval($info['aid'])       : 0;
    $data   = isset($info['data'])   ? addslashes($info['data'])  : '';
    $data2  = isset($info['data2'])  ? addslashes($info['data2']) : '';
    $data3  = isset($info['data3'])  ? addslashes($info['data3']) : '';
    $status = isset($info['status']) ? intval($info['status'])    : 0;
    if (!empty($session_id)) {
        DB_query("INSERT INTO {$_TABLES['mg_session_items']} "
               . "(session_id, mid, aid, data, data2, data3, status) "
               . "VALUES('$session_id', '$mid', $aid, '$data', '$data2', '$data3', $status)");
    }
}

function MG_endSession($session_id)
{
    global $_TABLES;

    $session_id = addslashes($session_id);
    DB_delete($_TABLES['mg_sessions'],      'session_id', $session_id);
    DB_delete($_TABLES['mg_session_items'], 'session_id', $session_id);
    DB_delete($_TABLES['mg_session_log'],   'session_id', $session_id);

    return true;
}

function MG_setSessionLog($session_id, $msg)
{
    global $_TABLES;

    $session_id = addslashes($session_id);
    $msg = addslashes($msg);
    DB_query("INSERT INTO {$_TABLES['mg_session_log']} "
           . "(session_id, session_log) "
           . "VALUES "
           . "('$session_id','$msg')");
}

function mg_batch_session_watermark($row)
{
    global $_CONF;
    require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-upload.php';
    require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-watermark.php';
    MG_watermarkBatchProcess($row['aid'], $row['mid']);
    return;
}

// create the thumbnail image
function mg_batch_session_rebuildthumb($row)
{
    global $_CONF;
    require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-upload.php';
    $srcImage = $row['data'];
    $imageThumb = $row['data2'];
    $mimeType = $row['mid'];
    $aid = $row['aid'];
    list($rc, $msg) = MG_createThumbnail($srcImage, $imageThumb, $mimeType, $aid);
    return;
}

// create the display image
function mg_batch_session_rebuilddisplay($row)
{
    global $_CONF;
    require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-upload.php';
    $srcImage = $row['data'];
    $imageDisplay = $row['data2'];
    $mimeExt = $row['data3'];
    $mimeType = $row['mid'];
    $aid = $row['aid'];
    list($rc, $msg) = MG_createDisplayImage($srcImage, $imageDisplay, $mimeExt, $mimeType, $aid);
    return;
}

function mg_batch_session_droporiginal($row)
{
    global $_CONF;
    require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-upload.php';
    if ($_MG_CONF['jhead_enabled'] == 1) {
        MG_execWrapper('"' . $_MG_CONF['jhead_path'] . "/jhead" . '"' . " -te " . $row['data'] . " " . $row['data2']);
    }
    @unlink($row['data']);
    return;
}

function mg_batch_session_rotate($row)
{
    global $_CONF;
    require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-media.php';
    MG_rotateMedia($row['aid'], $row['mid'], $row['data'], -1);
    return;
}

function mg_batch_session_ftpimport($row)
{
    global $_CONF, $_TABLES, $LANG_MG02;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-upload.php';
    require_once $_CONF['path'] . 'plugins/mediagallery/include/sort.php';
    $srcFile     = $row['data'];     // full path
    $album_id    = $row['aid'];
    $purgefiles  = intval($row['data2']);
    $baseSrcFile = $row['data3'];    // basefilename
    $directory   = $row['mid'];
    $album_data  = MG_getAlbumData($album_id, array('max_filesize'));
    $session_id  = $row['session_id'];

    if ($directory == 1) {
        require_once $_CONF['path'] . 'plugins/mediagallery/include/albumedit.php';
        $new_aid = MG_quickCreate($album_id, $baseSrcFile);

        $dir = $srcFile; // COM_stripslashes($srcFile);
        if (!$dh = @opendir($dir)) {
            COM_errorLog("Media Gallery: Error - unable process FTP import directory " . $dir);
        } else {
            while (($file = readdir($dh)) != false) {
                if ($file == '..' || $file == '.') {
                    continue;
                }
                if ($file == 'Thumbs.db' || $file == 'thumbs.db') {
                    continue;
                }
                $filetmp = $dir . '/' . $file;
                $mid = is_dir($filetmp) ? 1 : 0;
                $filename = basename($file);
                MG_registerSession(array(
                    'session_id' => $session_id,
                    'mid'        => $mid,
                    'aid'        => $new_aid,
                    'data'       => $filetmp,
                    'data2'      => $purgefiles,
                    'data3'      => $filename
                ));
                if (DB_error()) {
                    COM_errorLog("Media Gallery: Error - SQL error on inserting record into session_items table");
                }
            }
        }
    } else {

        if ($album_data['max_filesize'] != 0 && filesize($srcFile) > $album_data['max_filesize']) {
            COM_errorLog("MediaGallery: File " . $baseSrcFile . " exceeds maximum filesize for this album.");
            $statusMsg = addslashes(sprintf($LANG_MG02['upload_exceeds_max_filesize'], $baseSrcFile));
            MG_setSessionLog($session_id, $statusMsg);
            continue;
        }

        $filetype = "application/force-download";
        $opt = array(
            'upload'     => 0,
            'purgefiles' => $purgefiles,
            'filetype'   => $filetype,
        );
        list($rc, $msg) = MG_getFile($srcFile, $baseSrcFile, $album_id, $opt);
        $statusMsg = addslashes($baseSrcFile . " " . $msg);
        MG_setSessionLog($session_id, $statusMsg);
        MG_SortMedia($album_id);
        @set_time_limit($time_limit + 20);
    }
    return;
}
?>