<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | property.php                                                             |
// |                                                                          |
// | Displays media meta data in pop-up window                                |
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
    $display = COM_startBlock($LANG_ACCESS['accessdenied'], '',
               COM_getBlockTemplate('_msg_block', 'header'))
             . COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
    echo $display;
    exit;
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-exif.php';

$mid = COM_applyFilter($_REQUEST['mid']);

$aid = DB_getItem($_TABLES['mg_media_albums'], 'album_id', 'media_id="' . addslashes($mid) . '"');
$result = DB_query("SELECT * FROM {$_TABLES['mg_albums']} WHERE album_id=" . intval($aid));
$row    = DB_fetchArray($result);
$access = SEC_hasAccess($row['owner_id'], $row['group_id'],
                        $row['perm_owner'], $row['perm_group'],
                        $row['perm_members'], $row['perm_anon']);
if ($access == 0) {
    $display = COM_startBlock($LANG_ACCESS['accessdenied'], '',
               COM_getBlockTemplate('_msg_block', 'header'))
             . $LANG_MG00['access_denied']
             . COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
    echo $display;
    exit;
}

$display = '';

$media_filename = DB_getItem($_TABLES['mg_media'], 'media_filename', "media_id='" . addslashes($mid) . "'");

if ($media_filename == '') {
    $display = COM_startBlock($LANG_ACCESS['accessdenied'], '',
               COM_getBlockTemplate('_msg_block', 'header'))
             . $LANG_MG00['access_denied']
             . COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
    echo $display;
    exit;
}
$media_mime_ext = DB_getItem($_TABLES['mg_media'], 'media_mime_ext', "media_id='" . addslashes($mid) . "'");

$exifInfo = MG_readEXIF($mid, 1);

$src = MG_getFileUrl('tn', $media_filename, $media_mime_ext);
$p = pathinfo($src);
$src = $p['dirname'] . '/' . $p['filename'] . '_200.' . $p['extension'];

$T = COM_newTemplate(MG_getTemplatePath($aid));
$T->set_file('property', 'property.thtml');
$T->set_var(array(
    'media_thumbnail' => '<img src="' . $src . '" alt=""' . XHTML . '>',
    'exif_info'       => $exifInfo,
    'lang_close'      => $LANG_MG03['close'],
));

$display .= $T->finish($T->parse('output', 'property'));

header('Content-Type: text/html; charset=' . COM_getCharset());

echo $display;
?>