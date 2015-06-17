<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | xml.php                                                                  |
// |                                                                          |
// | Generates XML feed of album elements                                     |
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

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';

$aid  = isset($_GET['aid'])  ? COM_applyFilter($_GET['aid'], true) : 0;
$src  = isset($_GET['src'])  ? COM_applyFilter($_GET['src'])       : 'tn';
$type = isset($_GET['type']) ? COM_applyFilter($_GET['type'])      : 'mini';

if ($src != 'disp' && $src != 'orig') {
    $src = 'tn';
}

if ($type != 'full' || $type != 'mini') {
    $type = 'mini';
}

$album_data = MG_getAlbumData($aid, array('album_id', 'album_title', 'album_parent'), true);

$xml = '';
$charset = COM_getCharset();
$xml .= "<?xml version=\"1.0\" encoding=\"" . $charset . "\"?>\n";
$xml .= "<rss version=\"2.0\">\n";
$xml .= "    <channel>\n";
$xml .= "        <title><![CDATA[ XML for Media Gallery ]]></title>\n";
$xml .= "        <link>" . $_MG_CONF['site_url'] . "</link>\n";
$xml .= "        <description>XML Mini SlideShow for Media Gallery</description>\n";
$xml .= "        <language>en-us</language>\n";
$xml .= "        <generator>Media Gallery</generator>\n";
$xml .= "        <lastBuildDate>" . date('r', time()) . "</lastBuildDate>\n";
$xml .= "        <ttl>120</ttl>\n";


if (isset($album_data['album_id'])) {
    $xml .="    <album>\n";
    $xml .= "        <title><![CDATA[" . $album_data['album_title'] . "]]></title>\n";
    $xml .= "        <parentId><![CDATA[" . $album_data['album_parent'] . "]]></parentId>\n";
    $xml .= "        <owner><![CDATA[" . $album_data['owner_id'] . "]]></owner>\n";
    $xml .= "        <id><![CDATA[" . $album_data['album_id'] . "]]></id>\n";
    $xml .="    </album>\n";
    $children = MG_getAlbumChildren($aid);
    foreach ($children as $child) {
        $child_data = MG_getAlbumData($child, array('album_id', 'album_title', 'album_parent'), true);
        if ($child_data['access'] >= 1) {
            $xml .="    <album>\n";
            $xml .= "        <title><![CDATA[" . $child_data['album_title'] . "]]></title>\n";
            $xml .= "        <parentId><![CDATA[" . $child_data['album_parent'] . "]]></parentId>\n";
            $xml .= "        <owner><![CDATA[" . $child_data['owner_id'] . "]]></owner>\n";
            $xml .= "        <id><![CDATA[" . $child_data['album_id'] . "]]></id>\n";
            $xml .="    </album>\n";
        }
    }
}


if (isset($album_data['album_id']) && $album_data['access'] >= 1) {
    $sql = MG_buildMediaSql(array(
        'album_id'  => $aid,
        'fields'    => array('media_type', 'media_filename', 'remote_url', 'media_id',
                             'media_title', 'mime_type', 'media_upload_time'),
        'where'     => 'm.include_ss = 1'
    ));
    $result = DB_query($sql);
    while ($row = DB_fetchArray($result)) {
        if ($row['media_type'] == 0) {
            foreach ($_MG_CONF['validExtensions'] as $ext) {
                if (file_exists($_MG_CONF['path_mediaobjects'] . $src . '/' . $row['media_filename'][0] . '/' . $row['media_filename'] . $ext)) {
                    $PhotoURL  = $_MG_CONF['mediaobjects_url'] . '/' . $src . '/' . $row['media_filename'][0] .'/' . $row['media_filename'] . $ext;
                    $PhotoPath = $_MG_CONF['path_mediaobjects'] . $src . '/' . $row['media_filename'][0] .'/' . $row['media_filename'] . $ext;
                    break;
                }
            }
            if ($type == 'mini') {
                $ThumbURL = $_MG_CONF['mediaobjects_url'] . '/' . $src . '/' . $row['media_filename'][0] .'/' . $row['media_filename'] . $ext;
            } else {
                foreach ($_MG_CONF['validExtensions'] as $ext) {
                    if (file_exists($_MG_CONF['path_mediaobjects'] . 'tn/' . $row['media_filename'][0] . '/' . $row['media_filename'] . $ext)) {
                        $ThumbURL  = $_MG_CONF['mediaobjects_url'] . '/tn/' . $row['media_filename'][0] .'/' . $row['media_filename'] . $ext;
                        break;
                    }
                }
            }
            if ($row['remote_url'] != '') {
                $viewURL = $row['remote_url'];
            } else {
                $viewURL   = $_MG_CONF['site_url']  . "/media.php?s=" . $row['media_id'];
            }
            $imgsize   = @getimagesize($PhotoPath);
            if ($imgsize == false) {
                continue;
            }
            $xml .= "        <item>\n";
            $xml .= "            <title>" . $row['media_title'] . "</title>\n";;
            $xml .= "            <id>" . $row['media_id'] . "</id>\n";
            $xml .= "            <link>" . $viewURL . "</link>\n";
            $xml .= "            <view>" . $PhotoURL . "</view>\n";
            $xml .= "            <thumbUrl>" . $ThumbURL . "</thumbUrl>\n";
            $xml .= "            <width>" . $imgsize[0] . "</width>\n";
            $xml .= "            <height>" . $imgsize[1] . "</height>\n";
            $xml .= "            <mime>" . $row['mime_type'] . "</mime>\n";
            $xml .= "            <guid isPermaLink=\"false\">" . $viewURL . "</guid>\n";
            $xml .= "            <pubDate>" . date('r', $row['media_upload_time']) . "</pubDate>\n";
            $xml .= "            <media:content url=\"" . $PhotoURL . "\" type=\"" . $row['mime_type'] . "\" width=\"" . $imgsize[0] . "\" height=\"" . $imgsize[1] . "\">\n";
            $xml .= "               <media:title type=\"plain\"><![CDATA[" . $row['media_title'] . "]]></media:title>\n";
            $xml .= "               <media:thumbnail url=\"" . $ThumbURL . "\" width=\"" . $imgsize[0] . "\" height=\"" . $imgsize[1] . "\" time=\"" . date('r', $row['media_upload_time']) . "\"/>\n";
            $xml .= "            </media:content>\n";
            $xml .= "        </item>\n";
        }
    }
}

$xml .= "    </channel>\n";
$xml .= "</rss>\n";
header("Content-type: text/xml; charset=" . $charset);
echo $xml;
?>