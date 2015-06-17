<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | xspf.php                                                                 |
// |                                                                          |
// | Generates feed for XSPF players                                          |
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
    exit;
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';

function MG_getMP3Items(&$album_data)
{
    global $_TABLES, $_MG_CONF;

    $retval = '';

    if (isset($album_data['album_id'])) {
        $aid = $album_data['album_id'];
        if ($album_data['access'] >= 1) {
            $albumCover = MG_getAlbumCover($aid);
            if ($albumCover != '') {
                if (substr($albumCover,0,3) == 'tn_') {
                    $offset = 3;
                } else {
                    $offset = 0;
                }
                foreach ($_MG_CONF['validExtensions'] as $ext) {
                    if (file_exists($_MG_CONF['path_mediaobjects'] . 'tn/' . $albumCover[$offset] .'/' . $albumCover . $ext)) {
                        $image = $_MG_CONF['mediaobjects_url'] . '/tn/' . $albumCover[$offset] .'/' . $albumCover . $ext;
                        break;
                    }
                }
            } else {
                $image = '';
            }

            if ($album_data['tn_attached'] == 1) {
                foreach ($_MG_CONF['validExtensions'] as $ext) {
                    if (file_exists($_MG_CONF['path_mediaobjects'] . 'covers/cover_' . $aid . $ext)) {
                        $image = $_MG_CONF['mediaobjects_url'] . '/covers/cover_' . $aid . $ext;
                        break;
                    }
                }
            }

            $sql = MG_buildMediaSql(array(
                'album_id'  => $aid,
                'fields'    => array('media_type', 'media_filename', 'media_mime_ext',
                                     'media_tn_attached', 'media_title', 'artist',
                                     'album', 'media_id'),
                'where'     => "m.media_type = 2 AND m.mime_type = 'audio/mpeg'"
            ));
            $result = DB_query($sql);
            while ($row = DB_fetchArray($result)) {
                if ($row['media_type'] == 0) {
                    $PhotoURL = MG_getFileUrl($src, $row['media_filename']);
                } else {
                    $PhotoURL = MG_getFileUrl('orig', $row['media_filename'], $row['media_mime_ext']);
                }

                if ($row['media_tn_attached'] == 1) {
                    foreach ($_MG_CONF['validExtensions'] as $ext) {
                        if (file_exists($_MG_CONF['path_mediaobjects'] . 'tn/'.  $row['media_filename'][0] . '/tn_' . $row['media_filename'] . $ext)) {
                            $media_thumbnail = $_MG_CONF['mediaobjects_url'] . '/tn/'.  $row['media_filename'][0] . '/tn_' . $row['media_filename'] . $ext;
                            $media_thumbnail_file = $_MG_CONF['path_mediaobjects'] . 'tn/'.  $row['media_filename'][0] . '/tn_' . $row['media_filename'] . $ext;
                            break;
                        }
                    }
                } else {
                    $media_thumbnail = '';
                }
                if ($media_thumbnail != '') {
                    if (!file_exists($media_thumbnail_file)) {
                        $medai_thumbnail = '';
                    }
                }

                $retval .= "        <track>\n";
                $retval .= "            <title>" . MG_escape($row['media_title']) . "</title>\n";
                $retval .= "            <annotation>" . MG_escape($row['media_title']) . "</annotation>\n";
                if ($row['artist'] != '') {
                    $retval .= "            <creator>" . MG_escape($row['artist']) . "</creator>\n";
                }
                if ($row['album'] != '') {
                    $retval .= "            <album>" . MG_escape($row['album']) . "</album>\n";
                }
                $retval .= "            <identifier>" . $row['media_id'] . "</identifier>\n";
                $retval .= "            <location>" . $PhotoURL . "</location>\n";
                if ($media_thumbnail != '') {
                    $retval .= "            <image>" . $media_thumbnail . "</image>\n";
                } else {
                    if ($image != '') {
                        $retval .= "            <image>" . $image . "</image>\n";
                    }
                }
                $retval .= "        </track>\n";
            }
        }
        return $retval;
    }
}

/*
 * Main processing
 */

$aid = isset($_REQUEST['aid']) ? COM_applyFilter($_REQUEST['aid'], true) : 0;

$album_data = MG_getAlbumData($aid, array('skin', 'album_id', 'album_title', 'tn_attached'), true);

$xml = '';
$charset = COM_getCharset();
$xml .= "<?xml version=\"1.0\" encoding=\"" . $charset . "\"?>\n";
$xml .= "<playlist version=\"1\" xmlns=\"http://xspf.org/ns/0/\">\n";
$xml .= "<title>" . MG_escape($album_data['album_title']) . "</title>";
$xml .= "    <trackList>\n";
$xml .= MG_getMP3Items($album_data);
$xml .= "    </trackList>\n";
$xml .= "</playlist>\n";
header("Content-type: text/xml; charset=" . $charset);
echo $xml;
?>