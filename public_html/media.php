<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | media.php                                                                |
// |                                                                          |
// | Handles the display of various media types                               |
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
    COM_redirect($_CONF['site_url'] . '/index.php');
}

if (COM_isAnonUser() && $_MG_CONF['loginrequired'] == 1) {
    $display = SEC_loginRequiredForm();
    $display = MG_createHTMLDocument($display);
    COM_output($display);
    exit;
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-media.php';

$msg       = isset($_REQUEST['msg'])  ? COM_applyFilter($_REQUEST['msg'], true) : '';
$full      = isset($_REQUEST['f'])    ? COM_applyFilter($_REQUEST['f'],   true) : 0;
$mid       = isset($_REQUEST['s'])    ? COM_applyFilter($_REQUEST['s'],   true) : 0;
$sortOrder = isset($_REQUEST['sort']) ? COM_applyFilter($_REQUEST['sort'],true) : 0;
$page      = isset($_REQUEST['p'])    ? COM_applyFilter($_REQUEST['p'],   true) : 0;

list($ptitle, $content, $album_id) = MG_displayMedia($mid, $full, $sortOrder, 1, $page);

$skin = DB_getItem($_TABLES['mg_albums'], 'skin', "album_id = ". intval($album_id));
MG_getThemePublicJSandCSS($skin);
$display = '';
if ($msg != '') {
    $display .= COM_showMessage($msg, 'mediagallery');
}
$display .= $content;
$display = MG_createHTMLDocument($display, $ptitle);

COM_output($display);
?>