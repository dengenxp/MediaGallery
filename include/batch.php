<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | batch.php                                                                |
// |                                                                          |
// | Batch processing administration                                          |
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

require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-media.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-batch.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/sort.php';

function MG_batchProcess($album_id, $media_id_array, $action, $actionURL = '')
{
    global $_CONF, $_TABLES, $_MG_CONF, $LANG_MG01;

    $numItems = count($media_id_array);

    $album_data = MG_getAlbumData($album_id, array('album_title', 'wm_id'), false);

    switch ($action) {
        case 'rrt' :
        case 'rlt' :
            $direction = ($action == 'rrt') ? 'right' : 'left';
            $session_description = sprintf($LANG_MG01['batch_rotate_images'], $album_data['album_title']);
            $session_id = MG_beginSession('rotate', $actionURL, $session_description);
            for ($i=0; $i < $numItems; $i++) {
                $media_id = COM_applyFilter($media_id_array[$i]);
                MG_registerSession(array(
                    'session_id' => $session_id,
                    'mid'        => $media_id,
                    'aid'        => $album_id,
                    'data'       => $direction
                ));
            }
            $display = MG_continueSession($session_id, 0, $_MG_CONF['def_refresh_rate']);
            $display = MG_createHTMLDocument($display);
            echo $display;
            exit;
            break;
        case 'watermark' :
            if ($album_data['wm_id'] == 0) break;
            $session_description = sprintf($LANG_MG01['batch_watermark_images'], $album_data['album_title']);
            $session_id = MG_beginSession('watermark', $actionURL, $session_description);
            for ($i=0; $i < $numItems; $i++) {
                $media_id = COM_applyFilter($media_id_array[$i]);
                MG_registerSession(array(
                    'session_id' => $session_id,
                    'mid'        => $media_id,
                    'aid'        => $album_id
                ));
            }
            $display = MG_continueSession($session_id, 0, $_MG_CONF['def_refresh_rate']);
            $display = MG_createHTMLDocument($display);
            echo $display;
            exit;
            break;

    }
    echo COM_refresh($actionURL  . '&t=' . time());
    exit;
}


function MG_albumResizeConfirm($aid, $actionURL)
{
    global $_CONF, $_TABLES, $_MG_CONF, $LANG_MG01;

    $retval = '';

    $album_data = MG_getAlbumData($aid, array('album_title'), true);

    if ($album_data['access'] != 3) {
        echo COM_refresh($_MG_CONF['site_url'] . '/album.php?aid=' . $aid);
        exit;
    }

    if ($_MG_CONF['discard_original'] == 1) {
        $message = 'Original images are no longer stored on the server, so the display images cannot be resized';
        return $message;
    }

    $title = sprintf($LANG_MG01['batch_resize_images'], $album_data['album_title']);

    $T = COM_newTemplate(MG_getTemplatePath($aid));
    $T->set_file('admin', 'confirm.thtml');
    $T->set_var(array(
        'site_url'      => $_MG_CONF['site_url'],
        'aid'           => $aid,
        'message'       => $LANG_MG01['resize_confirm'],
        'lang_title'    => $title,
        'lang_cancel'   => $LANG_MG01['cancel'],
        'lang_next'     => $LANG_MG01['next'],
        'action'        => 'doresize',
        's_form_action' => $actionURL,
    ));

    $retval .= $T->finish($T->parse('output', 'admin'));
    return $retval;
}

function MG_albumResizeDisplay($aid, $actionURL)
{
    global $_CONF, $_TABLES, $_MG_CONF, $LANG_MG01;

    $album_data = MG_getAlbumData($aid, array('album_title'), true);

    if ($album_data['access'] != 3) {
        echo COM_refresh($actionURL);
        exit;
    }

    if ($_MG_CONF['discard_original'] == 1) {
        echo COM_refresh($actionURL);
        exit;
    }

    require_once $_CONF['path'].'plugins/mediagallery/include/lib-upload.php';

    $sql = MG_buildMediaSql(array(
        'album_id'  => $aid,
        'where'     => "m.media_type = 0",
        'sortorder' => -1
    ));
    $result = DB_query($sql);
    $nRows = DB_numRows($result);

    $session_description = sprintf($LANG_MG01['batch_resize_images'], $album_data['album_title']);
    $session_id = MG_beginSession('rebuilddisplay', $actionURL, $session_description);

    for ($x=0; $x < $nRows; $x++) {
        $row = DB_fetchArray($result);
        $imageDisplay = '';
        $srcImage     = '';
        $mfn = $row['media_filename'][0] . '/' . $row['media_filename'];
        if ($_MG_CONF['discard_original'] == 1) {
            $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . 'disp/' . $mfn);
            if (!empty($ext)) {
                $imageDisplay = $_MG_CONF['path_mediaobjects'] . 'disp/' . $mfn . $ext;
                $srcImage = $imageDisplay;
            }
        } else {
            $srcImage = $_MG_CONF['path_mediaobjects'] . 'orig/' . $mfn . '.' . $row['media_mime_ext'];
            $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . 'disp/' . $mfn);
            if (!empty($ext)) {
                $imageDisplay = $_MG_CONF['path_mediaobjects'] . 'disp/' . $mfn . $ext;
            }
        }
        if ($imageDisplay == '') continue;
        MG_registerSession(array(
            'session_id' => $session_id,
            'mid'        => $row['mime_type'],
            'aid'        => $row['album_id'],
            'data'       => $srcImage,
            'data2'      => $imageDisplay,
            'data3'      => $row['media_mime_ext']
        ));
    }
    $display = MG_continueSession($session_id, 0, $_MG_CONF['def_refresh_rate']);
    $display = MG_createHTMLDocument($display, 'album_resize_display');
    echo $display;
    exit;
}

function MG_albumRebuildConfirm($aid, $actionURL)
{
    global $_CONF, $_TABLES, $_MG_CONF, $LANG_MG01;

    $retval = '';

    $album_data = MG_getAlbumData($aid, array('album_title'), true);

    if ($album_data['access'] != 3) {
        echo COM_refresh($_MG_CONF['site_url'] . '/album.php?aid=' . $aid);
        exit;
    }

    $title = sprintf($LANG_MG01['batch_rebuild_thumbs'], $album_data['album_title']);

    $T = COM_newTemplate(MG_getTemplatePath($aid));
    $T->set_file('admin', 'confirm.thtml');
    $T->set_var(array(
        'site_url'      => $_MG_CONF['site_url'],
        'aid'           => $aid,
        'message'       => $LANG_MG01['rebuild_confirm'],
        'lang_title'    => $title,
        'lang_cancel'   => $LANG_MG01['cancel'],
        'lang_next'     => $LANG_MG01['next'],
        'action'        => 'dorebuild',
        's_form_action' => $actionURL,
    ));

    $retval .= $T->finish($T->parse('output', 'admin'));

    return $retval;
}

function MG_albumRebuildThumbs($aid, $actionURL)
{
    global $_CONF, $_TABLES, $_MG_CONF, $LANG_MG01;

    $album_data = MG_getAlbumData($aid, array('album_title'), true);

    if ($album_data['access'] != 3) {
        echo COM_refresh($actionURL);
        exit;
    }

    require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-upload.php';

    $sql = MG_buildMediaSql(array(
        'album_id'  => $aid,
        'where'     => "m.media_type = 0",
        'sortorder' => -1
    ));
    $result = DB_query($sql);
    $nRows = DB_numRows($result);
    if ($nRows <= 0) {
        echo COM_refresh($actionURL);
        exit;
    }

    $session_description = sprintf($LANG_MG01['batch_rebuild_thumbs'], $album_data['album_title']);
    $session_id = MG_beginSession('rebuildthumb', $actionURL, $session_description);
    for ($x=0; $x < $nRows; $x++) {
        $row = DB_fetchArray($result);
        $srcImage = '';
        $imageDisplay = '';
        $mfn = $row['media_filename'][0] . '/' . $row['media_filename'];
        if ($_MG_CONF['discard_original'] == 1) {
            $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . 'disp/' . $mfn);
            if (!empty($ext)) {
                $srcImage = $_MG_CONF['path_mediaobjects'] . 'disp/' . $mfn . $ext;
                $imageDisplay = $_MG_CONF['path_mediaobjects'] . 'tn/' . $mfn . $ext;
                $row['mime_type'] = '';
            }
        } else {
            $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . 'orig/' . $mfn);
            if (!empty($ext)) {
                $srcImage = $_MG_CONF['path_mediaobjects'] . 'orig/' . $mfn . $ext;
                $imageDisplay = $_MG_CONF['path_mediaobjects'] . 'tn/' . $mfn . $ext;
            }
        }
        if ($srcImage == '' || !file_exists($srcImage)) {
            $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . 'disp/' . $mfn);
            if (!empty($ext)) {
                $srcImage = $_MG_CONF['path_mediaobjects'] . 'disp/' . $mfn . $ext;
                $imageDisplay = $_MG_CONF['path_mediaobjects'] . 'tn/' . $mfn . $ext;
                $row['mime_type'] = '';
                $row['media_mime_ext'] = $ext;
            }
        }
        if ($srcImage == '') continue;
        MG_registerSession(array(
            'session_id' => $session_id,
            'mid'        => $row['mime_type'],
            'aid'        => $row['album_id'],
            'data'       => $srcImage,
            'data2'      => $imageDisplay,
            'data3'      => $row['media_mime_ext']
        ));
    }
    $display = MG_continueSession($session_id, 0, $_MG_CONF['def_refresh_rate']);
    $display = MG_createHTMLDocument($display, 'album_rebuild_thumbs');
    echo $display;
    exit;
}

function MG_batchDeleteMedia($album_id, $media_id_array, $actionURL = '')
{
    global $_USER, $_CONF, $_TABLES, $LANG_MG00;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/classMedia.php';

    // check permissions...

    $sql = "SELECT * FROM {$_TABLES['mg_albums']} WHERE album_id=" . intval($album_id);
    $result = DB_query($sql);
    $A = DB_fetchArray($result);

    $access = SEC_hasAccess($A['owner_id'], $A['group_id'], $A['perm_owner'],
                            $A['perm_group'], $A['perm_members'], $A['perm_anon']);

    if ($access != 3 && !SEC_hasRights('mediagallery.admin')) {
        COM_errorLog("Someone has tried to illegally delete items from album in Media Gallery. "
                   . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }
    $mediaCount = $A['media_count'];

    $numItems = count($media_id_array);
    for ($i=0; $i < $numItems; $i++) {
        MG_deleteMedia($media_id_array[$i]);
        $mediaCount--;
    }

    DB_change($_TABLES['mg_albums'], 'media_count', $mediaCount, 'album_id', $album_id);

    MG_resetAlbumCover($album_id);

    // reset the last_update field...
    MG_updateAlbumLastUpdate($album_id);

    // update the disk usage after delete...
    MG_updateQuotaUsage($album_id);

    MG_SortMedia($album_id);

    require_once $_CONF['path'] . 'plugins/mediagallery/include/rssfeed.php';
    MG_buildFullRSS();
    MG_buildAlbumRSS($album_id);
    echo COM_refresh($actionURL);
    exit;
}

function MG_batchMoveMedia($album_id, $destination, $media_id_array, $actionURL = '')
{
    global $_USER, $_CONF, $_TABLES, $LANG_MG00;

    // check permissions...

    $sql = "SELECT * FROM {$_TABLES['mg_albums']} WHERE album_id=" . intval($album_id);
    $result = DB_query($sql);
    $A = DB_fetchArray($result);

    $access = SEC_hasAccess($A['owner_id'], $A['group_id'], $A['perm_owner'],
                            $A['perm_group'], $A['perm_members'], $A['perm_anon']);

    if ($access != 3 && !SEC_hasRights('mediagallery.admin')) {
        COM_errorLog("Someone has tried to illegally delete items from album in Media Gallery. "
                   . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    // make sure they are not the same...
    if ($album_id == $destination) {
        echo COM_refresh($actionURL);
        exit;
    }

    // check permissions...

    $sql = "SELECT * FROM {$_TABLES['mg_albums']} WHERE album_id=" . intval($destination);
    $result = DB_query($sql);
    $D = DB_fetchArray($result);

    $access = SEC_hasAccess($D['owner_id'], $D['group_id'], $D['perm_owner'],
                            $D['perm_group'], $D['perm_members'], $D['perm_anon']);

    if ($access != 3 && !SEC_hasRights('mediagallery.admin')) {
        COM_errorLog("Someone has tried to illegally move items from album in Media Gallery. "
                   . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    // get max order for destination album....

    $sql = "SELECT MAX(media_order) + 10 AS media_seq "
         . "FROM {$_TABLES['mg_media_albums']} WHERE album_id = " . intval($destination);
    $result = DB_query($sql);
    $row = DB_fetchArray($result);
    $media_seq = $row['media_seq'];
    if ($media_seq < 10) {
        $media_seq = 10;
    }

    // ok to move media objects, we will need a destination album.
    // we will also need to get the max order value so we can put all of these at the top
    // of the new album.

    $aMediaCount = $A['media_count'];
    $dMediaCount = $D['media_count'];

    $numItems = count($media_id_array);

    for ($i=0; $i < $numItems; $i++) {
        $media_id = $media_id_array[$i];
        $sql = "UPDATE {$_TABLES['mg_media_albums']} "
             . "SET album_id=" . intval($destination) . ", media_order=" . intval($media_seq)
             . " WHERE album_id=" . intval($album_id) . " AND media_id='" . addslashes($media_id) . "'";
        DB_query($sql);
        $media_seq += 10;

        // update the media count in both albums...
        $aMediaCount--;
        $dMediaCount++;
    }

    DB_change($_TABLES['mg_albums'], 'media_count', $aMediaCount, 'album_id', intval($album_id));
    DB_change($_TABLES['mg_albums'], 'media_count', $dMediaCount, 'album_id', intval($destination));

    MG_resetAlbumCover($album_id);
    MG_resetAlbumCover($destination);

    // reset the last_update field...
    MG_updateAlbumLastUpdate($album_id);
    MG_updateAlbumLastUpdate($destination);

    MG_SortMedia($album_id);
    MG_SortMedia($destination);

    require_once $_CONF['path'] . 'plugins/mediagallery/include/rssfeed.php';
    MG_buildFullRSS();
    MG_buildAlbumRSS($album_id);
    MG_buildAlbumRSS($destination);

    echo COM_refresh($actionURL);
    exit;
}


function MG_deleteAlbumConfirm($album_id, $actionURL = '')
{
    global $_USER, $_CONF, $LANG_MG00, $LANG_MG01;

    $album = new mgAlbum($album_id);

    if ($actionURL == '') {
        $actionURL = $_CONF['site_admin_url'] . '/plugins/mediagallery/index.php';
    }

    $retval = '';
    $retval .= COM_startBlock($LANG_MG01['delete_album'], '',
                              COM_getBlockTemplate('_admin_block', 'header'));

    $T = COM_newTemplate(MG_getTemplatePath($album_id));
    $T->set_file('admin', 'deletealbum.thtml');
    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('site_admin_url', $_CONF['site_admin_url']);
    $T->set_var('album_id', $album_id);

    if ($album->access != 3) {
        COM_errorLog("MediaGallery: Someone has tried to delete a album they do not have permissions. "
                   . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    if (!isset($album->id)) {
        COM_errorLog("MediaGallery: Someone has tried to delete a album to non-existent parent album. "
                   . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    $album_selectbox = '<select name="target"><option value="0">' . $LANG_MG01['delete_all_media'] . '</option>';
    $root_album = new mgAlbum(0);
    $root_album->buildAlbumBox($album_selectbox, -1, 3, $album_id, 'upload');
    $album_selectbox .= '</select>';

    $T->set_var(array(
        'album_id'               => $album_id,
        'album_title'            => strip_tags($album->title),
        'album_desc'             => $album->description,
        's_form_action'          => $actionURL,
        'select_destination'     => $album_selectbox,
        'lang_delete'            => $LANG_MG01['delete'],
        'lang_cancel'            => $LANG_MG01['cancel'],
        'lang_delete_album'      => $LANG_MG01['delete_album'],
        'lang_title'             => $LANG_MG01['title'],
        'lang_description'       => $LANG_MG01['description'],
        'lang_move_all_media'    => $LANG_MG01['move_all_media'],
        'lang_album_delete_help' => $LANG_MG01['album_delete_help']
    ));

    $retval .= $T->finish($T->parse('output', 'admin'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $retval;
}

/**
* deletes specified album and moves contents if target_id not 0
*
* @param    int     album_id    album_id to delete
* @param    int     target_id   album id of where to move the delted albums contents
* @return   string              HTML
*
*/
function MG_deleteAlbum($album_id, $target_id, $actionURL='')
{
    global $_USER, $_CONF, $_TABLES, $_MG_CONF, $LANG_MG00, $LANG_MG01;

    $album = new mgAlbum($album_id);

    if ($actionURL == '') {
        $actionURL = $_CONF['site_admin_url'] . '/plugins/mediagallery/index.php';
    }

    // need to check perms here...

    if ($album->access != 3) {
        COM_errorLog("MediaGallery: Someone has tried to illegally delete an album in Media Gallery. "
                   . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    if ($target_id == 0) {     // Delete all images  -- need to recurse through all sub-albums...
        MG_deleteChildAlbums($album_id);

    } else { // move the stuff to another album...

        //  add a check to make sure we have edit rights to the target album...
        $sql = "SELECT * FROM {$_TABLES['mg_albums']} WHERE album_id=" . intval($target_id);
        $result = DB_query($sql);
        $nRows = DB_numRows($result);
        if ($nRows <= 0) {
            COM_errorLog("MediaGallery: Deleting Album - ERROR - Target albums does not exist");
            return COM_showMessageText($LANG_MG00['access_denied_msg']);
        }

        $row = DB_fetchArray($result);

        $access = SEC_hasAccess($row['owner_id'],$row['group_id'],$row['perm_owner'],
                                $row['perm_group'],$row['perm_members'],$row['perm_anon']);
        if ($access != 3 && !SEC_hasRights('mediagallery.admin')) {
            COM_errorLog("MediaGallery: User attempting to move to an album that user does not have privelges too!");
            return COM_showMessageText($LANG_MG00['access_denied_msg']);
        }

        DB_change($_TABLES['mg_media_albums'], 'album_id', intval($target_id), 'album_id', intval($album_id));
        DB_change($_TABLES['mg_albums'], 'album_parent', intval($target_id), 'album_parent', intval($album_id));
        DB_delete($_TABLES['mg_albums'], 'album_id', intval($album_id));

        // update the media_count and thumbnail image for this album....
        $dbCount = DB_count($_TABLES['mg_media_albums'], 'album_id', intval($target_id));
        DB_change($_TABLES['mg_albums'], 'media_count', $dbCount, 'album_id', intval($target_id));

        MG_resetAlbumCover($target_id);
    }

    // check and see if we need to reset the member_gallery flag...
    if ($_MG_CONF['member_albums'] == 1 && $album->parent == $_MG_CONF['member_album_root']) {
        $c = DB_count($_TABLES['mg_albums'], array('owner_id', 'album_parent'),
            array($album->owner_id, $album->parent));
        if ($c == 0) {
            DB_change($_TABLES['mg_userprefs'], 'member_gallery', 0, 'uid', $album->owner_id);
        }
    }
    require_once $_CONF['path'] . 'plugins/mediagallery/include/rssfeed.php';
    MG_buildFullRSS();
    if ($target_id != 0) MG_buildAlbumRSS($target_id);

    echo COM_refresh($actionURL);
    exit;
}



/**
* Recursivly deletes all albums and child albums
*
* @param    int     album_id    album id to delete
* @return   int     true for success or false for failure
*
*/
function MG_deleteChildAlbums($album_id) {

    global $_MG_CONF, $_TABLES;

    $sql = "SELECT album_id "
         . "FROM {$_TABLES['mg_albums']} "
         . "WHERE album_parent=" . intval($album_id);
    $result = DB_query($sql);
    while ($row = DB_fetchArray($result)) {
        MG_deleteChildAlbums($row['album_id']);
    }

    $sql = "SELECT media_id "
         . "FROM {$_TABLES['mg_media_albums']} "
         . "WHERE album_id = " . intval($album_id);
    $result = DB_query($sql);
    while ($row = DB_fetchArray($result)) {
        MG_deleteMedia($row['media_id']);
    }

    DB_delete($_TABLES['mg_media_albums'], 'album_id', intval($album_id));
    DB_delete($_TABLES['mg_albums'], 'album_id', intval($album_id));

    $feedname = sprintf($_MG_CONF['rss_feed_name'] . "%06d", $album_id);
    $feedpath = MG_getFeedPath();
    @unlink($feedpath . '/' . $feedname . '.rss');
}


function MG_batchCaptionSave($album_id, $actionURL)
{
    global $_CONF, $_TABLES, $_MG_CONF;

    $media_title = array();
    $media_desc  = array();
    $media_id    = array();

    $media_title = $_POST['media_title'];
    $media_desc  = $_POST['media_desc'];
    $media_id    = $_POST['media_id'];

    $total_media = count($media_id);

    $table = $_TABLES['mg_media'];
    $id = DB_getItem($table, 'media_id', 'media_id="' . addslashes($media_id[0]) . '"');
    if (empty($id)) {
        $table = $_TABLES['mg_mediaqueue'];
    }

    for ($i=0; $i < $total_media; $i++) {
        if ($_MG_CONF['htmlallowed']) {
            $title = addslashes(COM_checkWords(COM_stripslashes($media_title[$i])));
            $desc  = addslashes(COM_checkWords(COM_stripslashes($media_desc[$i])));
        } else {
            $title = addslashes(htmlspecialchars(strip_tags(COM_checkWords(COM_stripslashes($media_title[$i])))));
            $desc  = addslashes(htmlspecialchars(strip_tags(COM_checkWords(COM_stripslashes($media_desc[$i])))));
        }

        $media_time = time();
        $sql = "UPDATE " . $table
            . " SET media_title='" . $title . "', media_time='" . $media_time
            . "', media_upload_time='" . $media_time  . "', media_desc='" . $desc
            . "' WHERE media_id='" . addslashes(COM_applyFilter($media_id[$i])) . "'";

        DB_query($sql);
        PLG_itemSaved($media_id[$i], 'mediagallery');

    }
    require_once $_CONF['path'] . 'plugins/mediagallery/include/rssfeed.php';
    MG_buildAlbumRSS($album_id);

    echo COM_refresh($actionURL);
    exit;
}
?>