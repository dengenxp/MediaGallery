<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | thumbnail.php                                                            |
// |                                                                          |
// | AJAX component to retrieve image thumbnail                               |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2015 by the following authors:                             |
// |                                                                          |
// | Yoshinori Tahara       taharaxp AT gmail DOT com                         |
// |                                                                          |
// | Based on the Media Gallery Plugin for glFusion CMS                       |
// | Copyright (C) 2009 by the following authors:                             |
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
require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/classMedia.php';

$id = isset($_GET['id']) ? COM_applyFilter($_GET['id']) : '';
if (empty($id)) exit(0);

$is_queue = false;
$aid = DB_getItem($_TABLES['mg_media_albums'], 'album_id', 'media_id="' . addslashes($id) . '"');
if (empty($aid)) {
    $is_queue = true;
    $aid = DB_getItem($_TABLES['mg_media_album_queue'], 'album_id', 'media_id="' . addslashes($id) . '"');
}

$album_data = MG_getAlbumData($aid, array('album_id'), true);

if ($album_data['access'] == 0) {
    COM_errorLog("access was denied to the album");
    header("HTTP/1.1 500 Internal Server Error");
    echo "Access Error";
    exit(0);
}
if ($is_queue) {
    $sql = "SELECT * FROM {$_TABLES['mg_mediaqueue']} WHERE media_id='" . addslashes($id) . "'";
} else {
    $sql = "SELECT * FROM {$_TABLES['mg_media']} WHERE media_id='" . addslashes($id) . "'";
}
$tn_size = 11; // include:150x150
$result = DB_query($sql);
while ($A = DB_fetchArray($result)) {
    $default_thumbnail = Media::getDefaultThumbnail($A, $tn_size);
    $tn_file = $_MG_CONF['path_mediaobjects'] . $default_thumbnail;
    header("Content-type: image/jpeg") ;
    header("Content-Length: " . filesize($tn_file));
    $buffer = '';
    $fp = fopen($tn_file, 'rb');
    while (!feof($fp)) {
        $buffer.= fread($fp, 8192);
    }
    echo $buffer;
}
exit(0);
?>