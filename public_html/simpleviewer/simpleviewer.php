<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | simpleviewer.php                                                         |
// |                                                                          |
// | Generates XML feed for Flash SimpleViewer                                |
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

require_once '../../lib-common.php';

if (!in_array('mediagallery', $_PLUGINS)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

if (COM_isAnonUser() && $_MG_CONF['loginrequired'] == 1) {
    exit;
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';

/*
 * SimpleViewer Theme Layout Options
 *
 * textColor - Color of title and caption text (hexidecimal color value e.g 0xff00ff).
 * frameColor - Color of image frame, navigation buttons and thumbnail frame (hexidecimal color value e.g 0xff00ff).
 * frameWidth - Width of image frame in pixels.

 * stagePadding - Distance between image and thumbnails and around gallery edge in pixels.

 * thumbColumns - number of thumbnail rows. (To disable thumbnails completely set this value to 0.)
 * thumbRows - number of thumbnail columns. (To disable thumbnails completely set this value to 0.)
 * thumbPosition - Position of thumbnails relative to image. Can be "top", "bottom", "left", "right" or "none".
 *
 */

$_MG_CONF['simpleviewer']['galleryStyle'] = 'MODERN'; // MODERN, COMPACT, CLASSIC
$_MG_CONF['simpleviewer']['maxImageWidth'] = 1024;
$_MG_CONF['simpleviewer']['maxImageHeight'] = 768;
$_MG_CONF['simpleviewer']['textColor'] = '#FFFFFF';
$_MG_CONF['simpleviewer']['frameColor'] = '#FFFFFF';
$_MG_CONF['simpleviewer']['frameWidth'] = 1;
$_MG_CONF['simpleviewer']['thumbColumns'] = 5;
$_MG_CONF['simpleviewer']['thumbRows'] = 1;
$_MG_CONF['simpleviewer']['thumbPosition'] = 'BOTTOM'; // TOP, BOTTOM, RIGHT, LEFT, NONE
$_MG_CONF['simpleviewer']['showOpenButton'] = 'true';
$_MG_CONF['simpleviewer']['showFullscreenButton'] = 'true';

function MG_getItems(&$album_data)
{
    global $_TABLES, $_MG_CONF;

    $retval = '';

    if (!isset($album_data['album_id']) || $album_data['access'] < 1) return '';

    $aid = $album_data['album_id'];

    $src = isset($_REQUEST['src'])  ? COM_applyFilter($_REQUEST['src']) : 'orig';
    if ($src != 'disp' && $src != 'orig') {
        $src = 'orig';
    }

    $sql = MG_buildMediaSql(array(
        'album_id'  => $aid,
        'fields'    => array('media_type', 'media_filename', 'remote_url',
                             'media_id', 'media_title', 'media_desc')
    ));
    $result = DB_query($sql);
    while ($A = DB_fetchArray($result)) {
        if ($A['media_type'] != 0) continue;
        $PhotoPath = MG_getFilePath($src, $A['media_filename']);
        $ext = pathinfo($PhotoPath, PATHINFO_EXTENSION);

        $RelativePath   = $_MG_CONF['mediaobjects_url'] . "/$src/";
        $RelativeTNPath = $_MG_CONF['mediaobjects_url'] . '/tn/';

        $RelativePath   .= $A['media_filename'][0] . '/' . $A['media_filename'] . '.' . $ext;
        $RelativeTNPath .= $A['media_filename'][0] . '/' . $A['media_filename'] . '_150x150.' . $ext;

        $imgsize = @getimagesize($PhotoPath);
        if ($imgsize == false) continue;

        $title = '<p><b><font color="#ffffff" size="20">' . strip_tags($A['media_title']) . '</font></b></p>';
        $desc = '<p>' . strip_tags($A['media_desc']) . '</p>';

        $retval .= '<image '
                 . 'imageURL="' . $RelativePath . '" '
                 . 'thumbURL="' . $RelativeTNPath . '" '
                 . 'linkURL="'  . $RelativePath . '" linkTarget="" >' . LB;
        $retval .= '<caption><![CDATA[' . $title . $desc . ']]></caption>' . LB;
        $retval .= '</image>' . LB;
    }

    return $retval;
}

$aid = isset($_REQUEST['aid']) ? COM_applyFilter($_REQUEST['aid'], true) : 0;

$album_data = MG_getAlbumData($aid, array('album_id', 'display_image_size', 'album_title'), true);

list($dImageWidth, $dImageHeight) = MG_getImageSize($album_data['display_image_size']);
$dImageWidth = $dImageWidth - 70;

$title = strip_tags($album_data['album_title']);

$xml = '';
$charset = COM_getCharset();
$xml .= '<?xml version="1.0" encoding="' . $charset . '"?>' . LB;
$xml .= '<simpleviewergallery title="' . $title
      . '" galleryStyle="'         . $_MG_CONF['simpleviewer']['galleryStyle']
      . '" maxImageWidth="'        . $_MG_CONF['simpleviewer']['maxImageWidth']
      . '" maxImageHeight="'       . $_MG_CONF['simpleviewer']['maxImageHeight']
      . '" textColor="'            . $_MG_CONF['simpleviewer']['textColor']
      . '" frameColor="'           . $_MG_CONF['simpleviewer']['frameColor']
      . '" frameWidth="'           . $_MG_CONF['simpleviewer']['frameWidth']
      . '" thumbColumns="'         . $_MG_CONF['simpleviewer']['thumbColumns']
      . '" thumbRows="'            . $_MG_CONF['simpleviewer']['thumbRows']
      . '" thumbPosition="'        . $_MG_CONF['simpleviewer']['thumbPosition']
      . '" showOpenButton="'       . $_MG_CONF['simpleviewer']['showOpenButton']
      . '" showFullscreenButton="' . $_MG_CONF['simpleviewer']['showFullscreenButton']
      . '">' . LB;
$xml .= MG_getItems($album_data);
$xml .= '</simpleviewergallery>' . LB;
header("Content-type: text/xml; charset=" . $charset);
echo $xml;
?>