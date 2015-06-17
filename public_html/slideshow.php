<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | slideshow.php                                                            |
// |                                                                          |
// | JavaScript based slideshow                                               |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2015 by the following authors:                             |
// |                                                                          |
// | Yoshinori Tahara       taharaxp AT gmail DOT com                         |
// |                                                                          |
// | Based on the Media Gallery Plugin for glFusion CMS                       |
// | Copyright (C) 2002-2010 by the following authors:                        |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// |                                                                          |
// | Based on prior work by the Gallery Project                               |
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

COM_setArgNames(array('aid', 'f', 'sort'));
$album_id  = COM_applyFilter(COM_getArgument('aid'),  true);
$full      = COM_applyFilter(COM_getArgument('f'),    true);
$sortOrder = COM_applyFilter(COM_getArgument('sort'), true);

$album_data = MG_getAlbumData($album_id, array('skin', 'album_title', 'album_desc', 'album_parent', 'full_display', 'display_image_size'), true);

MG_getThemePublicJSandCSS($album_data['skin']);

$T = COM_newTemplate(MG_getTemplatePath($album_id));
$T->set_file('page', 'slideshow.thtml');
$T->set_block('page', 'slideItems', 'sItems');
$T->set_block('page', 'noItems', 'nItems');

$T->set_var('header', $LANG_MG00['plugin']);
$T->set_var('site_url', $_MG_CONF['site_url']);
$T->set_var('plugin', 'mediagallery');
$T->set_var('using_jquery', 'jquery');

if ($album_data['access'] == 0) {
    $display = COM_startBlock($LANG_ACCESS['accessdenied'], '', COM_getBlockTemplate('_msg_block', 'header'))
             . '<br' . XHTML . '>' . $LANG_MG00['access_denied_msg']
             . COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
    $title = strip_tags($album_data['album_title']);
    $display = MG_createHTMLDocument($display, $title);
    COM_output($display);
    exit;
}

$album_title  = $album_data['album_title'];
$album_desc   = $album_data['album_desc'];
$album_parent = $album_data['album_parent'];

list($dImageWidth, $dImageHeight) = MG_getImageSize($album_data['display_image_size']);

if ($_MG_CONF['usage_tracking']) {
    MG_updateUsage('slideshow', $album_title, '', '');
}

$sql = MG_buildMediaSql(array(
    'album_id'  => $album_id,
    'sortorder' => $sortOrder
));
$result = DB_query($sql);
$total_media = 0;
$mediaObject = array();
while ($row = DB_fetchArray($result)) {
    if ($row['media_type'] != 0 || $row['media_filename'] == '') continue;
    $mediaObject[] = $row;
    $total_media++;
}
$noFullOption = 0;

if ($album_data['full_display'] == 2 || $_MG_CONF['discard_original'] == 1 ||
    ($album_data['full_display'] == 1 && (!isset($_USER['uid']) || $_USER['uid'] < 2 ))) {
    $full = 0;
    $noFullOption = 1;
}

$photoCount = 0;

// default settings ---

if ($total_media > 0) {
    $defaultLoop       = 0;
    $defaultTransition = 0;
    $defaultPause      = 3;
    $defaultFull       = 0;

    $y = 1;
    $T->set_block('page', 'photo_url', 'purl');
    for ($i=0; $i<$total_media; $i++) {
        $filename = $mediaObject[$i]['media_filename'];
        $mime_ext = $mediaObject[$i]['media_mime_ext'];
        if ($full == 1) {
            $PhotoPath = MG_getFilePath('orig', $filename, $mime_ext);
            $PhotoURL  = MG_getFileUrl ('orig', $filename, $mime_ext);
            $imgsize = @getimagesize($PhotoPath);
            if ($imgsize == false) continue;
        } else {
            if ($mediaObject[$i]['remote_media'] != 1) {
                $PhotoPath = MG_getFilePath('disp', $filename);
                $ext = pathinfo($PhotoPath, PATHINFO_EXTENSION);
                $PhotoURL  = MG_getFileUrl('disp',  $filename, $ext);

                $imgsize = @getimagesize($PhotoPath);
                if ($imgsize == false) continue;
            } else {
                $PhotoURL = $mediaObject[$i]['remote_url'];
            }
        }

        $PhotoCaption = $mediaObject[$i]['media_title'];
        $PhotoCaption = str_replace(";",  " ", $PhotoCaption);
        $PhotoCaption = str_replace("\"", " ", $PhotoCaption);
        $PhotoCaption = str_replace("\n", " ", $PhotoCaption);
        $PhotoCaption = str_replace("\r", " ", $PhotoCaption);

        $T->set_var(array(
            'URL'     => 'photo_urls[' . $y . '] = "' . $PhotoURL . '";',
            'CAPTION' => 'photo_captions[' . $y . '] = "' . $PhotoCaption . '";',
        ));
        $T->parse('photo_info', 'photo_url', true);
        $y++;
        $photoCount++;
    }
    $T->set_var('photo_count', $total_media);
} else {
    $T->set_var('no_images', '<br' . XHTML . '>' . $LANG_MG03['no_media_objects']);
}

$full_toggle = '';
if ($noFullOption == 0) {
    $full_toggle = '<a href="' . $_MG_CONF['site_url'] . '/slideshow.php?aid=' . $album_id . '&amp;f=' . ($full ? '0' : '1') .
                   '&amp;sort=' . $sortOrder . '">' . ($full ? $LANG_MG03['normal_size'] : $LANG_MG03['full_size']) . '</a>';
}

$T->set_var(array(
    'pagination'        => '<a href="' . $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id . '&amp;page=1&amp;sort=' . $sortOrder . '">' . $LANG_MG03['return_to_album'] .'</a>',
    'slideshow'         => $_MG_CONF['site_url'] . '/slideshow.php?aid=' . $album_id . '&amp;f=' . ($full ? '0' : '1') . '&amp;sort=' . $sortOrder ,
    'slideshow_size'    => ($full ? $LANG_MG03['normal_size'] : $LANG_MG03['full_size']),
    'full_toggle'       => $full_toggle,
    'album_title'       => $album_title,
    'max_image_height'  => $dImageHeight,
    'max_image_width'   => $dImageWidth,
    'home'              => $LANG_MG03['home'],
    'return_to_album'   => $LANG_MG03['return_to_album'],
    'normal_size'       => $LANG_MG03['normal_size'],
    'full_size'         => $LANG_MG03['full_size'],
    'play'              => $LANG_MG03['play'],
    'stop'              => $LANG_MG03['stop'],
    'ss_running'        => $LANG_MG03['ss_running'],
    'ss_stopped'        => $LANG_MG03['ss_stopped'],
    'reverse'           => $LANG_MG03['reverse'],
    'forward'           => $LANG_MG03['forward'],
    'picture_loading'   => $LANG_MG03['picture_loading'],
    'please_wait'       => $LANG_MG03['please_wait'],
    'transition'        => $LANG_MG03['transition'],
    'delay'             => $LANG_MG03['delay'],
    'loop'              => $LANG_MG03['loop'],
    'seconds'           => $LANG_MG03['seconds'],
    'lang_of'           => $LANG_MG03['of'],
    'lang_blend'        => $LANG_MG05['blend'],
    'lang_blinds'       => $LANG_MG05['blinds'],
    'lang_checkerboard' => $LANG_MG05['checkerboard'],
    'lang_diagonal'     => $LANG_MG05['diagonal'],
    'lang_doors'        => $LANG_MG05['doors'],
    'lang_gradient'     => $LANG_MG05['gradient'],
    'lang_iris'         => $LANG_MG05['iris'],
    'lang_pinwheel'     => $LANG_MG05['pinwheel'],
    'lang_pixelate'     => $LANG_MG05['pixelate'],
    'lang_radial'       => $LANG_MG05['radial'],
    'lang_rain'         => $LANG_MG05['rain'],
    'lang_slide'        => $LANG_MG05['slide'],
    'lang_snow'         => $LANG_MG05['snow'],
    'lang_spiral'       => $LANG_MG05['spiral'],
    'lang_stretch'      => $LANG_MG05['stretch'],
    'lang_random'       => $LANG_MG05['random']
));

if ($total_media > 0) {
    $T->parse('sItems', 'slideItems');
} else {
    $T->parse('nItems', 'noItems');
}

$T->parse('output','page');
$display = $T->finish($T->get_var('output'));
$title = strip_tags($album_title);
$display = MG_createHTMLDocument($display, $title);

COM_output($display);
?>