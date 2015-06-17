<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | playall.php                                                              |
// |                                                                          |
// | Displays MP3 player with full album feed                                 |
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
* Main
*/

COM_setArgNames(array('aid', 'f', 'sort'));
$album_id = COM_applyFilter(COM_getArgument('aid'), true);

$album_data = MG_getAlbumData($album_id, array('skin', 'album_title', 'album_desc'), true);

if ($album_data['access'] == 0) {
    $display = COM_startBlock($LANG_ACCESS['accessdenied'], '', COM_getBlockTemplate('_msg_block', 'header'))
             . '<br' . XHTML . '>' . $LANG_MG00['access_denied_msg']
             . COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
    $title = strip_tags($album_data['album_title']);
    $display = MG_createHTMLDocument($display, $title);
    COM_output($display);
    exit;
}

if ($_MG_CONF['usage_tracking']) {
    MG_updateUsage('playalbum', $album_data['album_title'], '', '');
}

$pagination = '<a href="' . $_MG_CONF['site_url'] . '/album.php?aid='
            . $album_id . '&amp;page=1&amp;sort=' . '0' . '">'
            . $LANG_MG03['return_to_album'] .'</a>';

$T = COM_newTemplate(MG_getTemplatePath($album_id));
$T->set_file('page', 'playall_xspf.thtml');
$T->set_var(array(
    'site_url'        => $_MG_CONF['site_url'],
    'pagination'      => $pagination,
    'album_title'     => $album_data['album_title'],
    'album_desc'      => $album_data['album_desc'],
    'aid'             => $album_id,
    'home'            => $LANG_MG03['home'],
    'return_to_album' => $LANG_MG03['return_to_album'],
));

/*
 * Need to handle empty albums a little better
 */

MG_getThemePublicJSandCSS($album_data['skin']);
$display = $T->finish($T->parse('output', 'page'));
$title = strip_tags($album_data['album_title']);
$display = MG_createHTMLDocument($display, $title);

COM_output($display);
?>