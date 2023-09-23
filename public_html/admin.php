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

use Geeklog\Input;

require_once '../lib-common.php';

if (!in_array('mediagallery', $_PLUGINS)) {
    COM_redirect($_CONF['site_url'] . '/index.php');
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

function MG_navbar($selected, $album_id)
{
    global $_CONF, $_MG_CONF, $LANG_MG01, $LANG_MG03;

    include_once $_CONF['path'] . 'system/classes/navbar.class.php';
    $navbar = new navbar;
    $navbar->add_menuitem($LANG_MG01['browser_upload'], $_MG_CONF['site_url'] . '/admin.php?mode=browser&amp;album_id=' . $album_id); // This supports regular tabs for some themes
	$navbar->set_onclick($LANG_MG01['browser_upload'], 'location.href="' . "{$_MG_CONF['site_url']}/admin.php?mode=browser&amp;album_id={$album_id}" . '";'); // Added as a fix for the navbar class (since uikit tabs do not support urls)
	
    if (SEC_hasRights('mediagallery.admin')) {
        $navbar->add_menuitem($LANG_MG01['ftp_media'], $_MG_CONF['site_url'] . '/admin.php?mode=import&amp;album_id=' . $album_id);  // This supports regular tabs for some themes
		$navbar->set_onclick($LANG_MG01['ftp_media'], 'location.href="' . "{$_MG_CONF['site_url']}/admin.php?mode=import&amp;album_id={$album_id}" . '";'); // Added as a fix for the navbar class (since uikit tabs do not support urls)
    }
	
	$navbar->add_menuitem($LANG_MG01['remote_media'], $_MG_CONF['site_url'] . '/admin.php?mode=remote&amp;album_id=' . $album_id);  // This supports regular tabs for some themes
	$navbar->set_onclick($LANG_MG01['remote_media'], 'location.href="' . "{$_MG_CONF['site_url']}/admin.php?mode=remote&amp;album_id={$album_id}" . '";'); // Added as a fix for the navbar class (since uikit tabs do not support urls)
	
    $navbar->set_selected($selected);
    $retval = $navbar->generate();
	
    return $retval;
}

/**
* Main
*/

$display = '';
$mode = Input::fRequest('mode', '');
if ($mode === 'search') {
    COM_redirect($_MG_CONF['site_url'] . '/search.php');
}
$include = $_CONF['path'] . 'plugins/mediagallery/include/';

if ($mode === 'edit') {
    $album_id = (int) Input::fGet('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'albumedit.php';
    $actionURL = $_MG_CONF['site_url'] . '/admin.php';
    $display = MG_editAlbum('edit', $actionURL, $album_id);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'browser') {
    $album_id = (int) Input::fGet('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'newmedia.php';
    $display .= MG_userUpload($album_id);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'import') {
    $album_id = (int) Input::fGet('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'ftpmedia.php';
    $display .= MG_ftpUpload($album_id);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'globalattr') {
    $album_id = (int) Input::fGet('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'global.php';
    $admin_menu = (int) Input::fGet('a', 0);
    $display = MG_globalAlbumAttributeEditor($admin_menu);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'globalperm') {
    $album_id = (int) Input::fGet('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'global.php';
    $admin_menu = (int) Input::fGet('a', 0);
    $display = MG_globalAlbumPermEditor($admin_menu);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'wmmanage') {
    require_once $include . 'lib-upload.php';
    require_once $include . 'lib-watermark.php';
    $display = MG_watermarkManage();
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode == $LANG_MG01['save_exit']) {
    $album_id = (int) Input::fGet('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'batch.php';
    if ($album_id == 0) {
        $actionURL = $_MG_CONF['site_url'] . '/index.php';
    } else {
        $actionURL = $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id;
    }
    $display = MG_batchCaptionSave($album_id, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'create') {
    $album_id = (int) Input::fGet('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'albumedit.php';
    $actionURL = $_MG_CONF['site_url'] . '/admin.php';
    $display = MG_editAlbum('create', $actionURL, $album_id);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode == $LANG_MG01['reset_rating'] && !empty($LANG_MG01['reset_rating'])) {
    require_once $include . 'mediamanage.php';
    $album_id = (int) Input::fGet('album_id', 0);
    $mid      = Input::fPost('mid', '');
    $mqueue   = Input::fPost('queue');
    $display = MG_mediaResetRating($album_id, $mid, $mqueue);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode == $LANG_MG01['reset_views'] && !empty($LANG_MG01['reset_views'])) {
    require_once $include . 'mediamanage.php';
    $album_id = (int) Input::fGet('album_id', 0);
    $mid      = Input::fPost('mid', '');
    $mqueue   = Input::fPost('queue');
    $display = MG_mediaResetViews($album_id, $mid, $mqueue);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'list') {
    require_once $include . 'ftpmedia.php';
    $album_id = (int) Input::fGet('album_id', 0);
    $dir        = urldecode($_GET['dir']);
    $purgefiles = (int) Input::fGet('purgefiles', array());
    if (strstr($dir, '..')) {
        $display .= COM_showMessageText('Invalid input received'
                  . '  [ <a href=\'javascript:history.go(-1)\'>' . $LANG_MG02['go_back'] . '</a> ]');
    } else {
        $display .= MG_FTPpickFiles($album_id, $dir, $purgefiles, $recurse);
    }
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode == $LANG_MG01['save'] && !empty($LANG_MG01['save'])) {    // save the album...
    // OK, we have a save, now we need to see what we are saving...
    $action   = Input::fPost('action');
    $album_id = (int) Input::fPost('album_id', -1);
    if (empty($action) ) {
        MG_invalidRequest();
    }
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
            $dir        = Input::request('directory');
            $purgefiles = Input::request('purgefiles');
            $recurse    = Input::request('recurse');
            if (strstr($dir, '..')) {
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

        case 'albumsort':
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
            $media_id = Input::Post('mid');
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
} elseif ($mode == $LANG_MG01['delete'] && !empty($LANG_MG01['delete'])) {
    $action   = Input::fPost('action');
    $album_id = (int) Input::fPost('album_id', -1);
    if (empty($action) ) {
        MG_invalidRequest();
    }
    $display = '';

    switch ($action) {
        case 'media' :
            require_once $include . 'batch.php';
            $media_id_array = array();
            $sels = Input::fPost('sel', array());
            $numItems = count($sels);
            for ($i = 0; $i < $numItems; $i++) {
                if (isset($sels[$i])) {
                    $media_id_array[] =  $sels[$i];
                }
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
            $target_id = (int) Input::fPost('target', -1);
            if ($target_id >= 0) {
                require_once $include . 'batch.php';
                $actionURL = $_MG_CONF['site_url'] . '/index.php';
                $display .= MG_deleteAlbum($album_id, $target_id, $actionURL);
            } else {
                $display .= COM_showMessageText($LANG_MG02['no_target_album']
                          . '  [ <a href=\'javascript:history.go(-1)\'>' . $LANG_MG02['go_back'] . '</a> ]');
            }
            break;

        case 'savemedia' :
            $mid = Input::fPost('mid', '');
            if (empty($mid)) {
                MG_invalidRequest();
            }

            require_once $include . 'batch.php';
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
} elseif (($mode == $LANG_MG01['upload'] && !empty($LANG_MG01['upload'])) || ($mode === 'upload')) {
    $action = Input::Post('action');
    $album_id = (int) Input::fGet('album_id', -1);

    if ($action === 'watermark') {
        require_once $include . 'lib-upload.php';
        require_once $include . 'lib-watermark.php';
        $display = MG_watermarkUpload();
        $display = MG_createHTMLDocument($display);
    } elseif ($album_id > 0) {
        require_once $include . 'newmedia.php';
        $form = MG_userUpload($album_id);
        if (empty($form)) {
            COM_redirect($_MG_CONF['site_url'] . '/index.php');
        }
        $display .= $form;
        $display = MG_createHTMLDocument($display);
    } else {
        MG_invalidRequest();
    }

    COM_output($display);
} elseif ($mode === 'remote') {
    $album_id = (int) Input::fGet('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'remote.php';
    $display .= MG_remoteUpload($album_id);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'media') { // manage media items
    $album_id = (int) Input::fGet('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    $_SCRIPTS->setJavaScriptFile('mediamanage', '/mediagallery/js/mediagallery.js');

    require_once $include . 'mediamanage.php';
    $page = (int) Input::fGet('page', 0);
    $actionURL = $_MG_CONF['site_url'] . '/admin.php?aid=' . $album_id;
    $display = MG_imageAdmin($album_id, $page, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'resize') {
    $album_id = (int) Input::fGet('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'batch.php';
    $actionURL = $_MG_CONF['site_url'] . '/admin.php?aid=' . $album_id;
    $display = MG_albumResizeConfirm($album_id, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'rebuild') {
    $album_id = (int) Input::fGet('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'batch.php';
    $actionURL = $_MG_CONF['site_url'] . '/admin.php?aid=' . $album_id;
    $display = MG_albumRebuildConfirm($album_id, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif (($mode == $LANG_MG01['process'] && !empty($LANG_MG01['process'])) ||
           ($mode == $LANG_MG01['next'] && !empty($LANG_MG01['next']))) {
    $action = Input::post('action');
    if (empty($action)) {
        MG_invalidRequest();
    }

    require_once $include . 'batch.php';

    $album_id = (int) Input::fPost('aid', -1);
    if ($action === 'doresize') {
        if ($album_id > 0) {
            $actionURL = $_MG_CONF['site_url'] . '/admin.php?aid=' . $album_id;
            MG_albumResizeDisplay($album_id, $actionURL);
        }
    } elseif ($action === 'dorebuild') {
        if ($album_id > 0) {
            $actionURL = $_MG_CONF['site_url'] . '/admin.php?aid=' . $album_id;
            MG_albumRebuildThumbs($album_id, $actionURL);
        }
    }
    exit;
} elseif ($mode === 'mediaedit') { // edit a media item...
    $album_id = (int) Input::fGet('album_id', -1);
    $media_id = (int) Input::fGet('mid', -1);
    if (($album_id < 0) || ($media_id < 0)) {
        MG_invalidRequest();
    }

    require_once $include . 'mediamanage.php';
    $actionURL = $_MG_CONF['site_url'] . '/admin.php';
    $back = '';

    if (isset($_GET['s'])) {
        $back = $_MG_CONF['site_url'] . '/media.php?f=0&sort=0&s=' . $media_id;
    }
    $display = MG_mediaEdit($album_id, $media_id, $actionURL, 0, 0, $back);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'mediaeditq') { // edit a media item...
    $album_id = (int) Input::fGet('album_id', -1);
    $media_id = (int) Input::fGet('mid', -1);
    if (($album_id < 0) || ($media_id < 0)) {
        MG_invalidRequest();
    }

    require_once $include . 'mediamanage.php';
    $actionURL = $_MG_CONF['site_url'] . '/admin.php?album_id=1&mode=moderate';
    $display = MG_mediaEdit($album_id, $media_id, $actionURL, 1);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode == $LANG_MG01['batch_process'] && !empty($LANG_MG01['batch_process'])) {
    $album_id = (int) Input::fPost('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'batch.php';
    $action   = Input::fPost('batchOption');
    $media_id_array = array();
    $sels = Input::fPost('sel', array());
    $numItems = count($sels);

    for ($i = 0; $i < $numItems; $i++) {
        if (isset($sels[$i])) {
            $media_id_array[] = $sels[$i];
        }
    }

    $actionURL = $_MG_CONF['site_url'] . '/admin.php?mode=media&album_id=' . $album_id;
    MG_batchProcess($album_id, $media_id_array, $action, $actionURL);
    exit;
} elseif ($mode == $LANG_MG01['move'] && !empty($LANG_MG01['move'])) {
    $album_id = (int) Input::fPost('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'batch.php';
    $destination = (int) Input::fPost('album', -1);
    $actionURL = $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id;

    if ($destination == 0) { // deny move to the root album
        COM_redirect($actionURL);
    }

    $media_id_array = array();
    $sels = Input::fPost('sel', array());
    $numItems = count($sels);

    for ($i=0; $i < $numItems; $i++) {
        if (isset($sels[$i])) {
            $media_id_array[] = $sels[$i];
        }
    }

    $display = MG_batchMoveMedia($album_id, $destination, $media_id_array, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'albumsort') {
    $album_id = (int) Input::fGet('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'sort.php';
    $actionURL = $_MG_CONF['site_url'] . '/admin.php';
    $display = MG_sortAlbums($album_id, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'staticsort') {
    $album_id = (int) Input::fGet('album_id', -1);
    if ($album_id < 0) {
        MG_invalidRequest();
    }

    require_once $include . 'sort.php';
    $actionURL = $_MG_CONF['site_url'] . '/admin.php';
    $display = MG_staticSortMedia($album_id, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'rotate') {
    $album_id = (int) Input::fGet('album_id', -1);
    $media_id = (int) Input::fGet('media_id', -1);
    $direction = Input::fGet('action');

    if (($album_id < 0) || ($media_id < 0) ||
            empty($direction) || (($direction !== 'left') && ($direction !== 'right'))) {
        MG_invalidRequest();
    }

    require_once $include . 'lib-media.php';
    $actionURL = $_MG_CONF['site_url'] . '/admin.php?mode=mediaedit&mid=' . $media_id . '&album_id=' . $album_id;
    $display = MG_rotateMedia($album_id, $media_id, $direction, $actionURL);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
} elseif ($mode === 'cancel') {
    if (Input::post('admin_menu') == 1) {
        COM_redirect($_MG_CONF['admin_url'] . 'index.php');
    } else {
        $album_id = (int) Input::fPost('album_id', -1);

        if ($album_id > 0) {
            COM_redirect($_MG_CONF['site_url'] . '/album.php?aid=' . $album_id);
        } else {
            COM_redirect($_MG_CONF['site_url'] . '/index.php');
        }
    }
} else {
    $album_id = (int) Input::fPost('album_id', -1);
    $action   = Input::fPost('action');
    $queue = (int) Input::fPost('queue', -1);

    if (($album_id > 0) && !empty($action)) {
        if ($action === 'savemedia') {
            if ($queue == 1) {
                COM_redirect($_MG_CONF['site_url'] . '/admin.php?album_id=0&mode=moderate');
            } else {
                COM_redirect($_MG_CONF['site_url'] . '/admin.php?mode=media&album_id=' . $album_id);
            }
        }
    }

    if ($queue !== -1) {
        COM_redirect($_MG_CONF['site_url'] . '/admin.php?album_id=1&mode=moderate');
    }

    if (isset($_POST['origaid'])) {
        $album_id = (int) Input::fPost('origaid', -1);

        if ($album_id < 0) {
            COM_redirect($_MG_CONF['site_url'] . '/index.php');
        } else {
            COM_redirect($_MG_CONF['site_url'] . '/album.php?aid=' . $album_id);
        }
    } elseif ($album_id != 0) {
        $album_id = (int) Input::fPost('album_id', 0);
        COM_redirect($_MG_CONF['site_url'] . '/album.php?aid=' . $album_id);
    } elseif (isset($_GET['aid']) && $_GET['aid'] != 0) {
        $album_id = (int) Input::fGet('aid', 0);
        COM_redirect($_MG_CONF['site_url'] . '/album.php?aid=' . $album_id);
    } else {
        COM_redirect($_MG_CONF['site_url'] . '/index.php');
    }
}
