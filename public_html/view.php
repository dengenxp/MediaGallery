<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | view.php                                                                 |
// |                                                                          |
// | Displays video in pop-up window                                          |
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

require_once $_CONF['path'] . 'plugins/mediagallery/include/classMedia.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-media.php';

$media_id = isset($_GET['n']) ? COM_applyFilter($_GET['n']) : '';
$source   = isset($_GET['s']) ? COM_applyFilter($_GET['s']) : '';

if ($media_id == '') {
    COM_errorLog("MediaGallery: No media id passed to view.php");
    die("Invalid ID");
}

// -- get the movie info...

if ($mediaQueue == 'q') {
    $sql = "SELECT * FROM {$_TABLES['mg_mediaqueue']} AS m "
         . "LEFT JOIN {$_TABLES['mg_media_album_queue']} AS ma ";
} else {
    $sql = "SELECT * FROM {$_TABLES['mg_media']} AS m "
         . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ";
}
$sql .= "ON m.media_id=ma.media_id WHERE m.media_id='" . addslashes($media_id) . "'";
$result = DB_query($sql);
$nRows = DB_numRows($result);
if ($nRows <= 0) exit;

$row = DB_fetchArray($result);

$aid = $row['album_id'];

$album_data = MG_getAlbumData($aid,
    array('skin', 'display_skin', 'album_id', 'playback_type', 'allow_download', 'full_display'), true);

if ($album_data['access'] == 0) {
    $display = COM_startBlock($LANG_ACCESS['accessdenied'], '', COM_getBlockTemplate('_msg_block', 'header'))
             . '<br' . XHTML . '>' . $LANG_MG00['access_denied_msg']
             . COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
    $display = MG_createHTMLDocument($display);
    COM_output($display);
    exit;
}

$themeCSS = '';
if (!empty($album_data['skin'])) {
    $skin = $album_data['skin'];
    if (file_exists($_MG_CONF['path_html'] . 'themes/' . $skin . '/javascript.js')) {
        $themeCSS .= '<script type="text/javascript" src="' . $_MG_CONF['site_url']
                  . '/themes/' . $skin . '/javascript.js"></script>' . LB;
    }
    if (file_exists($_MG_CONF['path_html'] . 'themes/' . $skin . '/style.css')) {
        $themeCSS .= '<link rel="stylesheet" type="text/css" href="' . $_MG_CONF['site_url']
                  . '/themes/' . $skin . '/style.css"'.XHTML.'>' . LB;
    }
}

$opt = array(
    'playback_type'  => 2, // inline mode
    'skin'           => $album_data['skin'],
    'display_skin'   => $album_data['display_skin'],
    'allow_download' => $album_data['allow_download'],
    'full_display'   => $album_data['full_display'],
);
$object = MG_buildContent($row, $opt);

$T = COM_newTemplate(MG_getTemplatePath($aid));
$T->set_file('video', 'view_window.thtml');
$T->set_var(array(
    'site_url' => $_MG_CONF['site_url'],
    'themeCSS' => $themeCSS,
    'charset'  => COM_getCharset(),
    'object'   => $object[0],
));

if (!SEC_hasRights('mediagallery.admin')) {
    $media_views = $row['media_views'] + 1;
    DB_change($_TABLES['mg_media'], 'media_views', $media_views,
        'media_id', addslashes($row['media_id']));
}

$display = $T->finish($T->parse('output', 'video'));

COM_output($display);
?>