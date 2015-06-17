<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | popup.php                                                                |
// |                                                                          |
// | Displays media in pop-up window                                          |
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

/*
* Main
*/

function MG_access_denied()
{
    global $LANG_MG00, $LANG_ACCESS;

    $display = COM_startBlock($LANG_ACCESS['accessdenied'], '', COM_getBlockTemplate('_msg_block', 'header'))
             . '<br' . XHTML . '>' . $LANG_MG00['access_denied_msg']
             . COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
    $display = MG_createHTMLDocument($display);
    COM_output($display);
    exit;
}

if (!isset($_USER['uid'])) {
    $_USER['uid'] = 1;
}

$s   = COM_applyFilter($_GET['s']);
$aid = DB_getItem($_TABLES['mg_media_albums'], 'album_id', 'media_id="' . addslashes($s) . '"');

$album_data = MG_getAlbumData($aid, array('full_display'), true);

if ($album_data['access'] == 0) {
    MG_access_denied();
    exit;
}
if ($album_data['full_display'] == 2 || $_MG_CONF['discard_original'] == 1 || ($album_data['full_display'] == 1 && $_USER['uid'] < 2)) {
    MG_access_denied();
    exit;
}

$sql = "SELECT media_filename, media_mime_ext, media_title "
     . "FROM {$_TABLES['mg_media']} WHERE media_id='" . addslashes($s) . "'";
$result = DB_query($sql);
$A = DB_fetchArray($result);
if (empty($A)) exit;

$src = MG_getFileUrl('orig', $A['media_filename'], $A['media_mime_ext']);

$T = COM_newTemplate(MG_getTemplatePath($aid));
$T->set_file('property', 'property.thtml');
$T->set_var(array(
    'media_thumbnail' => '<img src="' . $src . '" alt="' . $A['media_title'] . '">',
    'media_title'     => $A['media_title'],
    'lang_close'      => $LANG_MG03['close'],
));
$display .= $T->finish($T->parse('output', 'property'));

header('Content-Type: text/html; charset=' . COM_getCharset());

echo $display;
?>