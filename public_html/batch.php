<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | batch.php                                                                |
// |                                                                          |
// | Batch system interface                                                   |
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
require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-batch.php';

if (COM_isAnonUser() && $_MG_CONF['loginrequired'] == 1) {
    $display = SEC_loginRequiredForm();
    $display = MG_createHTMLDocument($display);
    COM_output($display);
    exit;
}

/**
* Main
*/

$mode       = isset($_REQUEST['mode']) ? COM_applyFilter($_REQUEST['mode']) : '';
$session_id = isset($_GET['sid'])      ? COM_applyFilter($_GET['sid'])      : '';

if (isset($_POST['cancel_button'])) {
    $session_origin = DB_getItem($_TABLES['mg_sessions'], 'session_origin', 'session_id = ' . addslashes($session_id));
    if (empty($session_origin)) { // no session found
        COM_errorLog("Media Gallery Error - Unable to retrieve batch session data");
        echo COM_refresh($_MG_CONF['site_url'] . '/index.php');
        exit;
    }
    echo COM_refresh($session_origin);
    exit;
}

if ($mode != 'continue' || empty($session_id)) {
    echo COM_refresh($_MG_CONF['site_url'] . '/index.php');
    exit;
}

$refresh_rate = $_MG_CONF['def_refresh_rate'];
if (isset($_POST['refresh_rate'])) {
    $refresh_rate = COM_applyFilter($_POST['refresh_rate'], true);
} else if (isset($_GET['refresh'])) {
    $refresh_rate = COM_applyFilter($_GET['refresh'], true);
}

$item_limit = $_MG_CONF['def_item_limit'];
if (isset($_POST['item_limit'])) {
    $item_limit = COM_applyFilter($_POST['item_limit'], true);
} else if (isset($_GET['limit'])) {
    $item_limit = COM_applyFilter($_GET['limit'], true);
}

$display = MG_continueSession($session_id, $item_limit, $refresh_rate);
$display = MG_createHTMLDocument($display);
COM_output($display);
exit;

?>