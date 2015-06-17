<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | Set configuration options for Media Gallery Plugin.                      |
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
    $display = COM_startBlock($LANG_MG00['access_denied']);
    $display .= $LANG_MG00['access_denied_msg'];
    $display .= COM_endBlock();
    $display = COM_createHTMLDocument($display);
    COM_output($display);
    exit;
}

require_once $_MG_CONF['path_admin'] . 'navigation.php';

// main menu for media gallery administration

$sub  = isset($_GET['s'])    ? COM_applyFilter($_GET['s'])         : '';
$msg  = isset($_GET['msg'])  ? COM_applyFilter($_GET['msg'], true) : 0;
$mode = isset($_GET['mode']) ? COM_applyFilter($_GET['mode'])      : '';

$sub_menu = '';
switch ($sub) {
    case 'm': $sub_menu = 'member_albums';  break;
    case 'b': $sub_menu = 'batch_sessions'; break;
    case 'c': $sub_menu = 'miscellaneous';  break;
}

$home_url = $_CONF['site_url'] . '/admin/moderation.php';

if ($mode == 'editsubmission') {
    $media_id = COM_applyFilter($_GET['id']);
    $album_id = DB_getItem($_TABLES['mg_media_album_queue'], 'album_id', 'media_id="' . $media_id . '"');
    if (empty($media_id) || empty($album_id)) {
        echo COM_refresh($home_url);
        exit;
    }
    require_once $_CONF['path'] . 'plugins/mediagallery/include/mediamanage.php';
    $actionURL = $_CONF['site_url'] . '/admin/plugins/mediagallery/index.php?mode=savemedia';
    $display = MG_mediaEdit($album_id, $media_id, $actionURL, 1);
    $display = COM_createHTMLDocument($display);
    COM_output($display);
    exit;
} else if ($mode == 'savemedia') {
    $post_mode = COM_applyFilter($_POST['mode']);
    if (!empty($LANG_MG01['save']) && $post_mode == $LANG_MG01['save']) {
        $album_id = COM_applyFilter($_POST['album_id'], true);
        $media_id = COM_applyFilter($_POST['mid']);
        if (!empty($media_id) && !empty($album_id)) {
            require_once $_CONF['path'] . 'plugins/mediagallery/include/mediamanage.php';
            MG_saveMediaEdit($album_id, $media_id, $home_url);
        }
    }
    echo COM_refresh($home_url);
    exit;
}

$display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
$display .= MG_showAdminMenu($sub_menu);
if ($msg > 0) {
    $display .= COM_showMessageText($LANG_MG09[$msg]);
}
$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
if (empty($sub_menu)) {
    $display .= plugin_showstats_mediagallery(0);
}
$display = COM_createHTMLDocument($display);

COM_output($display);
?>