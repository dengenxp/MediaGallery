<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | fslideshow.php                                                           |
// |                                                                          |
// | Flash slideshow                                                          |
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

/*
* Main Function
*/

$album_id  = isset($_GET['aid'])  ? COM_applyFilter($_GET['aid'],  true) : NULL;
$src       = isset($_GET['src'])  ? COM_applyFilter($_GET['src'])        : '';
$sortOrder = isset($_GET['sort']) ? COM_applyFilter($_GET['sort'], true) : 0;
$full      = isset($_GET['f'])    ? COM_applyFilter($_GET['f'],    true) : 0;

if ($src != 'disp' && $src != 'orig') {
    $src = 'disp';
}

$album_data = MG_getAlbumData(
    $album_id, 
    array(
        'skin', 
        'album_title', 
        'album_desc', 
        'album_parent', 
        'full_display', 
        'display_image_size'),
    true
);

$noFullOption = 0;
if ($album_data['full_display'] == 2 || $_MG_CONF['discard_original'] == 1 ||
    ($album_data['full_display'] == 1 && empty($_USER['username']))) {
    $src = 'disp';
    $noFullOption = 1;
}

MG_getThemePublicJSandCSS($album_data['skin']);

$T = COM_newTemplate(MG_getTemplatePath($album_id));
$T->set_file('page', 'fslideshow.thtml');
$T->set_block('page', 'slideItems', 'sItems');
$T->set_block('page', 'noItems', 'nItems');

if ($album_data['access'] == 0) {
    $display = COM_startBlock($LANG_ACCESS['accessdenied'], '', COM_getBlockTemplate('_msg_block', 'header'))
             . '<br' . XHTML . '>' . $LANG_MG00['access_denied_msg']
             . COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
    $title = strip_tags($album_data['album_title']);
    $display = MG_createHTMLDocument($display, $title);
    COM_output($display);
    exit;
}

$sql = MG_buildMediaSql(array(
    'album_id'  => $album_id,
    'sortorder' => $sortOrder
));
$result = DB_query($sql);
$total_media = DB_numRows($result);

$album_title  = $album_data['album_title'];
$album_desc   = $album_data['album_desc'];
$album_parent = $album_data['album_parent'];

if ($_MG_CONF['usage_tracking']) {
    MG_updateUsage('slideshow', $album_title, '', '');
}

list($dImageWidth, $dImageHeight) = MG_getImageSize($album_data['display_image_size']);

$T->set_var(array(
    'header'          => $LANG_MG00['plugin'],
    'site_url'        => $_MG_CONF['site_url'],
    'plugin'          => 'mediagallery',
    'pagination'      => '<a href="' . $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id . '&amp;page=1&amp;sort=' . $sortOrder . '">' . $LANG_MG03['return_to_album'] .'</a>',
    'slideshow'       => $_MG_CONF['site_url'] . '/slideshow.php?aid=' . $album_id . '&amp;f=' . ($full ? '0' : '1') . '&amp;sort=' . $sortOrder ,
    'slideshow_size'  => ($full ? $LANG_MG03['normal_size'] : $LANG_MG03['full_size']),
    'album_title'     => $album_title,
    'aid'             => $album_id,
    'site_url'        => $_MG_CONF['site_url'],
    'home'            => $LANG_MG03['home'],
    'return_to_album' => $LANG_MG03['return_to_album'],
    'no_flash'        => $LANG_MG03['no_flash'],
    'src'             => $src,
    'fullscreen'      => ($noFullOption == 1) ? 'false' : 'true',
    'height'          => $dImageHeight,
    'width'           => $dImageWidth,
    'no_images'       => '<br' . XHTML . '>' . $LANG_MG03['no_media_objects']
));

if ($total_media > 0) {
    $T->parse('sItems', 'slideItems');
} else {
    $T->parse('nItems', 'noItems');
}

$display = $T->finish($T->parse('output', 'page'));
$title = strip_tags($album_title);
$display = MG_createHTMLDocument($display, $title);

COM_output($display);
?>