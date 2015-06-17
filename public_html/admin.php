<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | admin.php                                                                |
// |                                                                          |
// | traffic controller for maint/admin functions                             |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2015 by the following authors:                             |
// |                                                                          |
// | Yoshinori Tahara       taharaxp AT gmail DOT com                         |
// |                                                                          |
// | Based on the Media Gallery Plugin for glFusion CMS                       |
// | Copyright (C) 2002-2010 by the following authors:                        |
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

require_once '../lib-common.php';

if (!in_array('mediagallery', $_PLUGINS)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

if (COM_isAnonUser() && $_MG_CONF['loginrequired'] == 1) {
    $display = SEC_loginRequiredForm();
    $display = MG_createHTMLDocument($display);
    COM_output($display);
    exit;
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/classAlbum.php';

function MG_invalidRequest()
{
    global $LANG_MG02;

    $display = COM_showMessageText($LANG_MG02['generic_error']);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
    exit;
}

function MG_navbar($selected='', $album_id)
{
    global $_CONF, $_MG_CONF, $LANG_MG01, $LANG_MG03;

    include_once $_CONF['path'] . 'system/classes/navbar.class.php';
    $navbar = new navbar;
    $navbar->add_menuitem($LANG_MG01['swfupload_media'], $_MG_CONF['site_url'] . '/admin.php?mode=upload&amp;album_id=' . $album_id);
    $navbar->add_menuitem($LANG_MG01['browser_upload'], $_MG_CONF['site_url'] . '/admin.php?mode=browser&amp;album_id=' . $album_id);
    if (SEC_hasRights('mediagallery.admin')) {
        $navbar->add_menuitem($LANG_MG01['ftp_media'], $_MG_CONF['site_url'] . '/admin.php?mode=import&amp;album_id=' . $album_id);
    }
    $navbar->add_menuitem($LANG_MG01['remote_media'], $_MG_CONF['site_url'] . '/admin.php?mode=remote&amp;album_id=' . $album_id);
    $navbar->set_selected($selected);
    $retval .= $navbar->generate();
    return $retval;
}


/**
* Main
*/

$display = '';
$mode = isset($_REQUEST['mode']) ? COM_applyFilter($_REQUEST['mode']) : '';
if ($mode == 'search') {
    echo COM_refresh($_MG_CONF['site_url'] . "/search.php");
    exit;
}
$include = $_CONF['path'] . 'plugins/mediagallery/include/';


if ($mode == 'edit') {
    if (!isset($_GET['album_id'])) MG_invalidRequest();
    require_once $include . 'albumedit.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $actionURL = $_MG_CONF['site_url'] . '/admin.php';
    $display = MG_editAlbum('edit', $actionURL, $album_id);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'browser') {
    if (!isset($_GET['album_id'])) MG_invalidRequest();
    require_once $include . 'newmedia.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $display .= MG_userUpload($album_id);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'import') {
    if (!isset($_GET['album_id'])) MG_invalidRequest();
    require_once $include . 'ftpmedia.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $display .= MG_ftpUpload($album_id);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'globalattr') {
    if (!isset($_GET['album_id'])) MG_invalidRequest();
    require_once $include . 'global.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $admin_menu = COM_applyFilter($_GET['a'], true);
    $display = MG_globalAlbumAttributeEditor($admin_menu);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'globalperm') {
    if (!isset($_GET['album_id'])) MG_invalidRequest();
    require_once $include . 'global.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $admin_menu = COM_applyFilter($_GET['a'], true);
    $display = MG_globalAlbumPermEditor($admin_menu);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'wmmanage') {
    require_once $include . 'lib-upload.php';
    require_once $include . 'lib-watermark.php';
    $display = MG_watermarkManage();
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} else if ($mode == $LANG_MG01['save_exit']) {
    if (!isset($_GET['album_id'])) MG_invalidRequest();
    require_once $include . 'batch.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    if ($album_id == 0) {
        $actionURL = $_MG_CONF['site_url'] . '/index.php';
    } else {
        $actionURL = $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id;
    }
    $display = MG_batchCaptionSave($album_id, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'create') {
    if (!isset($_GET['album_id'])) MG_invalidRequest();
    require_once $include . 'albumedit.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $actionURL = $_MG_CONF['site_url'] . '/admin.php';
    $display = MG_editAlbum('create', $actionURL, $album_id);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == $LANG_MG01['reset_rating'] && !empty($LANG_MG01['reset_rating'])) {
    require_once $include . 'mediamanage.php';
    $album_id = COM_applyFilter($_POST['album_id'], true);
    $mid      = COM_applyFilter($_POST['mid']);
    $mqueue   = COM_applyFilter($_POST['queue']);
    $display = MG_mediaResetRating($album_id, $mid, $mqueue);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == $LANG_MG01['reset_views'] && !empty($LANG_MG01['reset_views'])) {
    require_once $include . 'mediamanage.php';
    $album_id = COM_applyFilter($_POST['album_id'], true);
    $mid      = COM_applyFilter($_POST['mid']);
    $mqueue   = COM_applyFilter($_POST['queue']);
    $display = MG_mediaResetViews($album_id, $mid, $mqueue);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'list') {
    require_once $include . 'ftpmedia.php';
    $album_id   = COM_applyFilter($_GET['album_id'], true);
    $dir        = urldecode($_GET['dir']);
    $purgefiles = COM_applyFilter($_GET['purgefiles'], true);
    if (strstr($dir, "..")) {
        $display .= COM_showMessageText('Invalid input received'
                  . '  [ <a href=\'javascript:history.go(-1)\'>' . $LANG_MG02['go_back'] . '</a> ]');
    } else {
        $display .= MG_FTPpickFiles($album_id, $dir, $purgefiles, $recurse);
    }
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == $LANG_MG01['save'] && !empty ($LANG_MG01['save'])) {    // save the album...
    // OK, we have a save, now we need to see what we are saving...
    if (!isset($_POST['action']) || !isset($_POST['album_id'])) {
        MG_invalidRequest();
    }
    $action   = COM_applyFilter($_POST['action']);
    $album_id = COM_applyFilter($_POST['album_id'], true);
    $display = '';
    switch ($action) {
        case 'album' :
            require_once $include . 'albumedit.php';
            $display .= MG_saveAlbum($album_id);
            break;

        case 'remoteupload' :
            require_once $include . 'remote.php';
            $display .= MG_saveRemoteUpload($album_id);
            break;

        case 'upload' :
            require_once $include . 'newmedia.php';
            $display .= MG_saveUserUpload($album_id);
            break;

        case 'ftp' :
            require_once $include . 'ftpmedia.php';
            $dir        = $_REQUEST['directory'];
            $purgefiles = $_REQUEST['purgefiles'];
            $recurse    = $_REQUEST['recurse'];
            if (strstr($dir, "..")) {
                $display .= COM_showMessageText('Invalid input received'
                          . '  [ <a href=\'javascript:history.go(-1)\'>' . $LANG_MG02['go_back'] . '</a> ]');
            } else {
                $display .= MG_FTPpickFiles($album_id, $dir, $purgefiles, $recurse);
            }
            break;

        case 'ftpprocess' :
            require_once $include . 'ftpmedia.php';
            MG_ftpProcess($album_id);
            break;

        case 'media' :
            require_once $include . 'mediamanage.php';
            $actionURL = $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id;
            $display .= MG_saveMedia($album_id, $actionURL);
            break;

        case 'albumsort' :
            require_once $include . 'sort.php';
            $actionURL = $_MG_CONF['site_url'] . '/index.php';
            $display .= MG_saveAlbumSort($album_id, $actionURL);
            break;

        case 'staticsort' :
            require_once $include . 'sort.php';
            $actionURL = $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id;
            $display .= MG_saveStaticSortMedia($album_id, $actionURL);
            break;

        case 'savemedia' :
            require_once $include . 'mediamanage.php';
            $media_id = $_POST['mid'];
            $actionURL = $_MG_CONF['site_url'] . '/admin.php?mode=media&album_id=' . $album_id;
            $display .= MG_saveMediaEdit($album_id, $media_id, $actionURL);
            break;

        case 'globalattr' :
            require_once $include . 'global.php';
            $display .= MG_saveGlobalAlbumAttr();
            break;

        case 'globalperm' :
            require_once $include . 'global.php';
            $display .= MG_saveGlobalAlbumPerm();
            break;

        case 'watermark' :
            require_once $include . 'lib-upload.php';
            require_once $include . 'lib-watermark.php';
            $display .= MG_watermarkSave();
            break;

        case 'wm_upload' :
            require_once $include . 'lib-upload.php';
            require_once $include . 'lib-watermark.php';
            $display .= MG_watermarkUploadSave();
            break;
    }
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == $LANG_MG01['delete'] && !empty($LANG_MG01['delete'])) {
    if (!isset($_POST['action']) || !isset($_POST['album_id'])) {
        MG_invalidRequest();
    }
    $action   = COM_applyFilter($_POST['action']);
    $album_id = COM_applyFilter($_POST['album_id'], true);
    $display = '';
    switch ($action) {
        case 'media' :
            require_once $include . 'batch.php';
            $media_id_array = array();
            $numItems = count($_POST['sel']);
            for ($i=0; $i < $numItems; $i++) {
                $media_id_array[] = COM_applyFilter($_POST['sel'][$i]);
            }
            $actionURL = $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id;
            $display .= MG_batchDeleteMedia($album_id, $media_id_array, $actionURL);
            break;

        case 'album' :
            require_once $include . 'batch.php';
            $actionURL = $_MG_CONF['site_url'] . '/admin.php';
            $display .= MG_deleteAlbumConfirm($album_id, $actionURL);
            break;

        case 'confalbum' :
            if (isset($_POST['target'])) {
                require_once $include . 'batch.php';
                $target_id = COM_applyFilter($_POST['target'], true);
                $actionURL = $_MG_CONF['site_url'] . '/index.php';
                $display .= MG_deleteAlbum($album_id, $target_id, $actionURL);
            } else {
                $display .= COM_showMessageText($LANG_MG02['no_target_album']
                          . '  [ <a href=\'javascript:history.go(-1)\'>' . $LANG_MG02['go_back'] . '</a> ]');
            }
            break;

        case 'savemedia' :
            require_once $include . 'batch.php';
            if (!isset($_POST['mid'])) MG_invalidRequest();
            $mid = COM_applyFilter($_POST['mid']);
            $media_id_array = array($mid);
            $actionURL = $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id;
            $display .= MG_batchDeleteMedia($album_id, $media_id_array, $actionURL);
            break;

        case 'watermark' :
            require_once $include . 'lib-upload.php';
            require_once $include . 'lib-watermark.php';
            $display .= MG_watermarkDelete();
            break;
    }
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if (($mode == $LANG_MG01['upload'] && !empty($LANG_MG01['upload'])) || $mode == 'upload') {
    $action = '';
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
    }
    if ($action == 'watermark') {
        require_once $include . 'lib-upload.php';
        require_once $include . 'lib-watermark.php';
        $display = MG_watermarkUpload();
        $display = MG_createHTMLDocument($display);

    } else if (isset($_GET['album_id'])) {
        require_once $include . 'newmedia.php';
        $album_id = COM_applyFilter($_GET['album_id'], true);
        $form = MG_SWFUpload($album_id);
        if (empty($form)) {
            echo COM_refresh($_MG_CONF['site_url'] . '/index.php');
            exit;
        }
        $display .= $form;
        $display = MG_createHTMLDocument($display);

    } else {
        MG_invalidRequest();
    }
    COM_output($display);

} else if ($mode == 'remote') {
    if (!isset($_GET['album_id'])) MG_invalidRequest();
    require_once $include . 'remote.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $display .= MG_remoteUpload($album_id);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'media') { // manage media items
    if (!isset($_GET['album_id'])) MG_invalidRequest();

    $_SCRIPTS->setJavaScriptFile('mediamanage', '/mediagallery/js/mediagallery.js');

    require_once $include . 'mediamanage.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $page = isset($_GET['page']) ? COM_applyFilter($_GET['page'], true) : 0;
    $actionURL = $_MG_CONF['site_url'] . '/admin.php?aid=' . $album_id;
    $display = MG_imageAdmin($album_id, $page, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'resize') {
    if (!isset($_GET['album_id'])) MG_invalidRequest();
    require_once $include . 'batch.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $actionURL = $_MG_CONF['site_url'] . '/admin.php?aid=' . $album_id;
    $display = MG_albumResizeConfirm($album_id, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'rebuild') {
    if (!isset($_GET['album_id'])) MG_invalidRequest();
    require_once $include . 'batch.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $actionURL = $_MG_CONF['site_url'] . '/admin.php?aid=' . $album_id;
    $display = MG_albumRebuildConfirm($album_id, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if (($mode == $LANG_MG01['process'] && !empty($LANG_MG01['process'])) ||
           ($mode == $LANG_MG01['next'] && !empty($LANG_MG01['next']))) {
    if (!isset($_POST['action'])) MG_invalidRequest();
    $action = $_POST['action'];
    require_once $include . 'batch.php';
    if ($action == 'doresize') {
        if (isset($_POST['aid'])) {
            $album_id = COM_applyFilter($_POST['aid'], true);
            $actionURL = $_MG_CONF['site_url'] . '/admin.php?aid=' . $album_id;
            MG_albumResizeDisplay($album_id, $actionURL);
        }
    } else if ($action == 'dorebuild') {
        if (isset($_POST['aid'])) {
            $album_id = COM_applyFilter($_POST['aid'], true);
            $actionURL = $_MG_CONF['site_url'] . '/admin.php?aid=' . $album_id;
            MG_albumRebuildThumbs($album_id, $actionURL);
        }
    }
    exit;

} else if ($mode == 'mediaedit') { // edit a media item...
    if (!isset($_GET['album_id']) || !isset($_GET['mid'])) {
        MG_invalidRequest();
    }
    require_once $include . 'mediamanage.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $media_id = COM_applyFilter($_GET['mid'], true);
    $actionURL = $_MG_CONF['site_url'] . '/admin.php';
    $back = '';
    if (isset($_GET['s'])) {
        $back = $_MG_CONF['site_url'] . '/media.php?f=0&sort=0&s=' . $media_id;
    }
    $display = MG_mediaEdit($album_id, $media_id, $actionURL, 0, 0, $back);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'mediaeditq') { // edit a media item...
    if (!isset($_GET['album_id']) || !isset($_GET['mid'])) {
        MG_invalidRequest();
    }
    require_once $include . 'mediamanage.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $media_id = COM_applyFilter($_GET['mid'], true);
    $actionURL = $_MG_CONF['site_url'] . '/admin.php?album_id=1&mode=moderate';
    $display = MG_mediaEdit($album_id, $media_id, $actionURL, 1);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == $LANG_MG01['batch_process'] && !empty($LANG_MG01['batch_process'])) {
    if (!isset($_POST['album_id'])) MG_invalidRequest();
    require_once $include . 'batch.php';
    $album_id = COM_applyFilter($_POST['album_id'], true);
    $action   = COM_applyFilter($_POST['batchOption']);
    $media_id_array = array();
    $numItems = count($_POST['sel']);
    for ($i=0; $i < $numItems; $i++) {
        $media_id_array[] = COM_applyFilter($_POST['sel'][$i]);
    }
    $actionURL = $_MG_CONF['site_url'] . '/admin.php?mode=media&album_id=' . $album_id;
    MG_batchProcess($album_id, $media_id_array, $action, $actionURL);
    exit;

} else if ($mode == $LANG_MG01['move'] && !empty($LANG_MG01['move'])) {
    if (!isset($_POST['album_id'])) MG_invalidRequest();
    require_once $include . 'batch.php';
    $album_id = COM_applyFilter($_POST['album_id'], true);
    $destination = COM_applyFilter($_POST['album'], true);
    $actionURL = $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id;
    if ($destination == 0) { // deny move to the root album
        echo COM_refresh($actionURL);
        exit;
    }
    $media_id_array = array();
    $numItems = count($_POST['sel']);
    for ($i=0; $i < $numItems; $i++) {
        $media_id_array[] = COM_applyFilter($_POST['sel'][$i]);
    }
    $display = MG_batchMoveMedia($album_id, $destination, $media_id_array, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'albumsort') {
    if (!isset($_GET['album_id'])) MG_invalidRequest();
    require_once $include . 'sort.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $actionURL = $_MG_CONF['site_url'] . '/admin.php';
    $display = MG_sortAlbums($album_id, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'staticsort') {
    if (!isset($_GET['album_id'])) MG_invalidRequest();
    require_once $include . 'sort.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $actionURL = $_MG_CONF['site_url'] . '/admin.php';
    $display = MG_staticSortMedia($album_id, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if ($mode == 'rotate') {
    if (!isset($_GET['album_id']) || !isset($_GET['media_id']) || !isset($_GET['action'])) {
        MG_invalidRequest();
    }
    require_once $include . 'lib-media.php';
    $album_id = COM_applyFilter($_GET['album_id'], true);
    $media_id = COM_applyFilter($_GET['media_id']);
    $direction = COM_applyFilter($_GET['action']);
    $actionURL = $_MG_CONF['site_url'] . '/admin.php?mode=mediaedit&mid=' . $media_id . '&album_id=' . $album_id;
    $display = MG_rotateMedia($album_id, $media_id, $direction, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);

} else if (mode == 'cancel') {
    if (isset($_POST['admin_menu']) && $_POST['admin_menu'] == 1) {
        echo COM_refresh($_MG_CONF['admin_url'] . 'index.php');
        exit;
    } else {
        if (isset($_POST['album_id']) && $_POST['album_id'] > 0) {
            echo COM_refresh($_MG_CONF['site_url'] . '/album.php?aid=' . COM_applyFilter($_POST['album_id']), true);
        }
        echo COM_refresh($_MG_CONF['site_url'] . '/index.php');
        exit;
    }

} else {
    if (isset($_POST['album_id']) && isset($_POST['action'])) {
        $album_id = COM_applyFilter($_POST['album_id'], true);
        $action   = COM_applyFilter($_POST['action']);
        $queue = COM_applyFilter($_POST['queue'], true);
        switch ($action) {
            case 'savemedia' :
                if ($queue == 1) {
                    echo COM_refresh($_MG_CONF['site_url'] . '/admin.php?album_id=0&mode=moderate');
                } else {
                    echo COM_refresh($_MG_CONF['site_url'] . '/admin.php?mode=media&album_id=' . $album_id);
                }
                exit;
        }
    }

    if (isset($_POST['queue'])) {
        echo COM_refresh($_MG_CONF['site_url'] . '/admin.php?album_id=1&mode=moderate');
    }
    if (isset($_POST['origaid'])) {
        $album_id = COM_applyFilter($_POST['origaid'],true);
        if ($album_id == 0) {
            echo COM_refresh($_MG_CONF['site_url'] . '/index.php');
        } else {
            echo COM_refresh($_MG_CONF['site_url'] . '/album.php?aid=' . $album_id);
        }
        exit;
    } else if (isset($_POST['album_id']) && $_POST['album_id'] != 0) {
        $album_id = COM_applyFilter($_POST['album_id'], true);
        echo COM_refresh($_MG_CONF['site_url'] . '/album.php?aid=' . $album_id);
        exit;

    } else if (isset($_GET['aid']) && $_GET['aid'] != 0) {
        $album_id = COM_applyFilter($_GET['aid'], true);
        echo COM_refresh($_MG_CONF['site_url'] . '/album.php?aid=' . $album_id);
        exit;

    } else {
        echo COM_refresh($_MG_CONF['site_url'] . '/index.php');
        exit;
    }
}
?>
