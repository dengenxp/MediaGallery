<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | Media Gallery Maintenance Routines                                       |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2015 by the following authors:                             |
// |                                                                          |
// | Yoshinori Tahara       taharaxp AT gmail DOT com                         |
// |                                                                          |
// | Based on the Media Gallery Plugin for glFusion CMS                       |
// | Copyright (C) 2005-2010 by the following authors:                        |
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
//

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

// Only let admin users access this page
if (!SEC_hasRights('mediagallery.config')) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the Media Gallery Configuration page. "
               . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: " . $_SERVER['REMOTE_ADDR'], 1);
    $display = COM_startBlock($LANG_MG00['access_denied'])
             . $LANG_MG00['access_denied_msg']
             . COM_endBlock();
    $display = COM_createHTMLDocument($display);
    COM_output($display);
    exit;
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-batch.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/classAlbum.php';
require_once $_MG_CONF['path_admin'] . 'navigation.php';

function MG_rebuildThumbConfirm()
{
    global $_CONF, $_MG_CONF, $LANG_MG00, $LANG_MG01;

    $retval = '';

    $B = new Template($_MG_CONF['template_path']);
    $B->set_file('admin', 'thumbs.thtml');
    $B->set_var(array(
        'site_admin_url' => $_CONF['site_admin_url'],
        'site_url'       => $_CONF['site_url'],
        'xhtml'          => XHTML,
        'lang_title'     => $LANG_MG01['rebuild_thumb'],
        's_form_action'  => $_MG_CONF['admin_url'] . 'maint.php?mode=thumbs&amp;step=two',
        'lang_next'      => $LANG_MG01['next'],
        'lang_cancel'    => $LANG_MG01['cancel'],
        'lang_help'      => $LANG_MG01['rebuild_thumb_help'],
        'lang_details'   => $LANG_MG01['rebuild_thumb_details'],
    ));

    $T = new Template($_MG_CONF['template_path']);
    $T->set_file('admin', 'administration.thtml');
    $T->set_var(array(
        'site_admin_url' => $_CONF['site_admin_url'],
        'site_url'       => $_MG_CONF['site_url'],
        'xhtml'          => XHTML,
        'admin_body'     => $B->finish($B->parse('output', 'admin')),
        'title'          => $LANG_MG01['rebuild_thumb'],
        'lang_admin'     => $LANG_MG00['admin'],
        'lang_help'      => '<img src="' . MG_getImageFile('button_help.png') . '" style="border:none;" alt="?"' . XHTML . '>',
        'help_url'       => $_MG_CONF['site_url'] . '/docs/usage.html#Rebuild_Thumbs',
    ));
    $retval .= $T->finish($T->parse('output', 'admin'));

    return $retval;
}

function MG_rebuildThumb()
{
    global $_MG_CONF, $LANG_MG01;

    $sql = MG_buildMediaSql(array(
        'where'     => "m.media_type = 0",
        'sortorder' => -1
    ));
    $result = DB_query($sql);
    $nRows = DB_numRows($result);
    if ($nRows > 0) {
        $actionURL = $_MG_CONF['admin_url'] . 'index.php';
        $session_description = $LANG_MG01['rebuild_thumb'];
        $session_id = MG_beginSession('rebuildthumb', $actionURL, $session_description);
        for ($x=0; $x<$nRows; $x++) {
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
        $display = COM_createHTMLDocument($display);
        COM_output($display);
        exit;
    } else {
        echo COM_refresh($_MG_CONF['admin_url'] . 'index.php?msg=7');
        exit;
    }
}


$mode = COM_applyFilter($_GET['mode']);

if (isset($_POST['submit'])) {
    $submit = COM_applyFilter($_POST['submit']);
    if ($submit == $LANG_MG01['cancel']) {
        echo COM_refresh($_MG_CONF['admin_url'] . 'index.php');
        exit;
    }
}

if ($mode == 'thumbs') {
    $step = COM_applyFilter($_GET['step']);
    switch ($step) {
        case 'one' :
            $display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
            $display .= MG_showAdminMenu('batch_sessions');
            $display .= MG_rebuildThumbConfirm();
            $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
            $display = COM_createHTMLDocument($display);
            COM_output($display);
            exit;
            break;
        case 'two' :
            MG_rebuildThumb();
            break;
        default :
            header("Location: " . $_MG_CONF['admin_url'] . 'index.php');
            exit;
    }
} else if ($mode == 'resize') {
    $step = COM_applyFilter($_GET['step']);
    switch ($step) {
        case 'one' :
            $B = new Template($_MG_CONF['template_path']);
            $B->set_file('admin', 'thumbs.thtml');
            $B->set_var(array(
                'lang_title'     => $LANG_MG01['resize_display'],
                's_form_action'  => $_MG_CONF['admin_url'] . 'maint.php?mode=resize&amp;step=two',
                'lang_next'      => $LANG_MG01['next'],
                'lang_cancel'    => $LANG_MG01['cancel'],
                'lang_help'      => $LANG_MG01['resize_help'],
                'lang_details'   => $LANG_MG01['resize_details'],
                'site_url'       => $_CONF['site_url'],
                'site_admin_url' => $_CONF['site_admin_url'],
                'xhtml'          => XHTML,
            ));
            $B->parse('output', 'admin');

            $T = new Template($_MG_CONF['template_path']);
            $T->set_file('admin', 'administration.thtml');
            $T->set_var(array(
                'site_admin_url' => $_CONF['site_admin_url'],
                'site_url'       => $_MG_CONF['site_url'],
                'xhtml'          => XHTML,
                'admin_body'     => $B->finish($B->get_var('output')),
                'title'          => $LANG_MG01['resize_display'],
                'lang_admin'     => $LANG_MG00['admin'],
                'lang_help'      => '<img src="' . MG_getImageFile('button_help.png') . '" style="border:none;" alt="?"' . XHTML . '>',
                'help_url'       => $_MG_CONF['site_url'] . '/docs/usage.html#Resize_Images',
            ));
            $T->parse('output', 'admin');
            $display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
            $display .= MG_showAdminMenu('batch_sessions');
            $display .= $T->finish($T->get_var('output'));
            $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
            $display = COM_createHTMLDocument($display);
            COM_output($display);
            exit;
            break;
        case 'two' :
            $sql = MG_buildMediaSql(array(
                'where'     => "m.media_type = 0",
                'sortorder' => -1
            ));
            $result = DB_query($sql);
            $nRows = DB_numRows($result);
            if ($nRows > 0) {
                $actionURL = $_MG_CONF['admin_url'] . 'index.php';
                $session_description = $LANG_MG01['resize_display'];
                $session_id = MG_beginSession('rebuilddisplay', $actionURL, $session_description);
                for ($x=0; $x<$nRows; $x++) {
                    @set_time_limit(30);
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
                $display = COM_createHTMLDocument($display);
                COM_output($display);
                exit;

            } else {
                echo COM_refresh($_MG_CONF['admin_url'] . 'index.php?msg=7');
                exit;
            }
            break;
        default :
            header("Location: " . $_MG_CONF['admin_url'] . 'index.php');
            exit;

    }
} else if ($mode == 'remove') {
    $step = COM_applyFilter($_GET['step']);
    switch ($step) {
        case 'one' :
            if ($_MG_CONF['discard_original'] != 1) {
                $display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
                $display .= MG_showAdminMenu('batch_sessions');
                $display .= COM_showMessageText($LANG_MG01['remove_error']
                          . '  [ <a href=\'javascript:history.go(-1)\'>' . $LANG_MG02['go_back'] . '</a> ]');
                $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
                $display = COM_createHTMLDocument($display);
                COM_output($display);
                exit;
            }

            $B = new Template($_MG_CONF['template_path']);
            $B->set_file('admin', 'thumbs.thtml');
            $B->set_var(array(
                'lang_title'     => $LANG_MG01['remove_originals'],
                's_form_action'  => $_MG_CONF['admin_url'] . 'maint.php?mode=remove&amp;step=two',
                'lang_next'      => $LANG_MG01['next'],
                'lang_cancel'    => $LANG_MG01['cancel'],
                'lang_help'      => $LANG_MG01['remove_help'],
                'lang_details'   => $LANG_MG01['remove_details'],
                'site_url'       => $_CONF['site_url'],
                'site_admin_url' => $_CONF['site_admin_url'],
                'xhtml'          => XHTML,
            ));
            $B->parse('output', 'admin');

            $T = new Template($_MG_CONF['template_path']);
            $T->set_file('admin', 'administration.thtml');
            $T->set_var(array(
                'site_admin_url' => $_CONF['site_admin_url'],
                'site_url'       => $_MG_CONF['site_url'],
                'xhtml'          => XHTML,
                'admin_body'     => $B->finish($B->get_var('output')),
                'title'          => $LANG_MG01['discard_originals'],
                'lang_admin'     => $LANG_MG00['admin'],
                'lang_help'      => '<img src="' . MG_getImageFile('button_help.png') . '" style="border:none;" alt="?"' . XHTML . '>',
                'help_url'       => $_MG_CONF['site_url'] . '/docs/usage.html#Discard_Original_Images',
            ));
            $T->parse('output', 'admin');

            $display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
            $display .= MG_showAdminMenu('batch_sessions');
            $display .= $T->finish($T->get_var('output'));
            $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
            $display = COM_createHTMLDocument($display);
            COM_output($display);
            exit;
            break;
        case 'two' :
            $sql = MG_buildMediaSql(array(
                'where'     => "m.media_type = 0",
                'sortorder' => -1
            ));
            $result = DB_query($sql);
            $nRows = DB_numRows($result);
            if ($nRows > 0) {
                $actionURL = $_MG_CONF['admin_url'] . 'index.php';
                $session_description = $LANG_MG01['discard_originals'];
                $session_id = MG_beginSession('droporiginal', $actionURL, $session_description);
                $mfn = $row['media_filename'][0] . '/' . $row['media_filename'];
                for ($x=0; $x<$nRows; $x++) {
                    $row = DB_fetchArray($result);
                    $srcImage = $_MG_CONF['path_mediaobjects'] . 'orig/' . $mfn . '.' . $row['media_mime_ext'];
                    if (!file_exists($srcImage)) continue;
                    $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . 'disp/' . $mfn);
                    $imageDisplay = $_MG_CONF['path_mediaobjects'] . 'disp/' . $mfn . $ext;
                    MG_registerSession(array(
                        'session_id' => $session_id,
                        'mid'        => '',
                        'aid'        => $row['album_id'],
                        'data'       => $srcImage,
                        'data2'      => $imageDisplay,
                        'data3'      => $row['media_mime_ext']
                    ));
                }
                $display = MG_continueSession($session_id, 0, $_MG_CONF['def_refresh_rate']);
                $display = COM_createHTMLDocument($display);
                COM_output($display);
                exit;
            } else {
                echo COM_refresh($_MG_CONF['admin_url'] . 'index.php?msg=7');
                exit;
            }
            break;
    }
} else if ($mode == 'continue') {

    if (isset($_POST['cancel_button'])) {
        $session_id = COM_applyFilter($_GET['sid']);
        // Pull the session status info
        $sql = "SELECT * FROM {$_TABLES['mg_sessions']} WHERE session_id='" . addslashes($session_id) . "'";
        $result = DB_query($sql);
        if (DB_error()) {
            COM_errorLog("Media Gallery Error - Unable to retrieve batch session data");
            echo COM_refresh($_MG_CONF['site_url'] . '/index.php');
            exit;
        }
        $nRows = DB_numRows($result);
        if ($nRows > 0) {
            $session = DB_fetchArray($result);
        } else {
            COM_errorLog("Media Gallery Error: Unable to find batch session id");
            echo COM_refresh($_MG_CONF['site_url'] . '/index.php');
            exit; // no session found
        }
        echo COM_refresh($session['session_origin']);
        exit;
    }

    $display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
    $display .= MG_showAdminMenu('batch_sessions');
    if (isset($_GET['sid'])) {
        $sid = COM_applyFilter($_GET['sid']);
        if (isset($_POST['refresh_rate'])) {
            $refresh_rate = COM_applyFilter($_POST['refresh_rate'], true);
        } else {
            $refresh_rate = $_MG_CONF['def_refresh_rate'];
            if (isset($_GET['refresh'])) {
                $refresh_rate = COM_applyFilter($_GET['refresh'], true);
            }
        }
        if (isset($_POST['item_limit'])) {
            $item_limit = intval(COM_applyFilter($_POST['item_limit'], true));
        } else {
            $item_limit = $_MG_CONF['def_item_limit'];
            if (isset($_GET['limit'])) {
                $item_limit = intval(COM_applyFilter($_GET['limit'], true));
            }
        }
        $display .= MG_continueSession($sid, $item_limit, $refresh_rate);
    }
    $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    $display = COM_createHTMLDocument($display);
    COM_output($display);
    exit;

} else {
    echo COM_refresh($_MG_CONF['admin_url'] . 'index.php');
    exit;
}

?>
