<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | Sort albums based on user selected field.                                |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2015 by the following authors:                             |
// |                                                                          |
// | Yoshinori Tahara       taharaxp AT gmail DOT com                         |
// |                                                                          |
// | Based on the Media Gallery Plugin for glFusion CMS                       |
// | Copyright (C) 2005-2010 by the following authors:                        |
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
//

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

// Only let admin users access this page
if (!SEC_hasRights('mediagallery.config')) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the Media Gallery Configuration page. "
               . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: " . $_SERVER['REMOTE_ADDR'], 1);
    $display = COM_startBlock($LANG_MG00['access_denied']);
    $display .= $LANG_MG00['access_denied_msg'];
    $display .= COM_endBlock();
    $display = COM_createHTMLDocument($display);
    COM_output($display);
    exit;
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/classAlbum.php';
require_once $_MG_CONF['path_admin'] . 'navigation.php';

function MG_staticSortAlbumChildren($startaid, $sql_order, $sql_sort_by)
{
    global $_TABLES;

    $sql = "SELECT album_id FROM {$_TABLES['mg_albums']} "
         . "WHERE album_parent=" . $startaid . " " . $sql_sort_by . $sql_order;
    $result = DB_query($sql);
    $order = 10;
    while ($row = DB_fetchArray($result)) {
        DB_change($_TABLES['mg_albums'], 'album_order', $order, 'album_id', $row['album_id']);
        $order += 10;
    }

    $children = MG_getAlbumChildren($startaid);
    foreach ($children as $child) {
        MG_staticSortAlbumChildren($child, $sql_order, $sql_sort_by);
    }
}

function MG_staticSortAlbumsSave()
{
    global $_TABLES, $_MG_CONF;

    $startaid     = !empty($_POST['startaid'])   ? COM_applyFilter($_POST['startaid']  ,true) : 0;
    $sortfield    = !empty($_POST['sortfield'])  ? COM_applyFilter($_POST['sortfield'] ,true) : 0;
    $sortorder    = !empty($_POST['sortorder'])  ? COM_applyFilter($_POST['sortorder'] ,true) : 0;
    $process_subs = !empty($_POST['processsub']) ? COM_applyFilter($_POST['processsub'],true) : 0;

    switch ($sortfield) {
        case '0' :  // album title
            $sql_sort_by = " ORDER BY album_title ";
            break;
        case '1' :  // media_count
            $sql_sort_by = " ORDER BY media_count ";
            break;
        case '2' : // last_update
            $sql_sort_by = " ORDER BY last_update ";
            break;
        default :
            $sql_sort_by = " ORDER BY album_title ";
            break;
    }

    switch($sortorder) {
        case '0' :  // ascending
            $sql_order = " DESC";
            break;
        case '1' :  // descending
            $sql_order = " ASC";
            break;
    }

    if ($process_subs == 0) {
        $sql = "SELECT album_id FROM {$_TABLES['mg_albums']} "
             . "WHERE album_parent=" . $startaid . " " . $sql_sort_by . $sql_order;
        $result = DB_query($sql);
        $order = 10;
        while ($row = DB_fetchArray($result)) {
            DB_change($_TABLES['mg_albums'], 'album_order', $order, 'album_id', $row['album_id']);
            $order += 10;
        }

    } else {
        MG_staticSortAlbumChildren($startaid, $sql_order, $sql_sort_by);
    }
    header("Location: " . $_MG_CONF['admin_url'] . 'index.php?msg=1');
}

function MG_staticSortAlbumOptions()
{
    global $_CONF, $_MG_CONF, $_TABLES, $LANG_MG01, $LANG_MG03;

    $retval = '';
    $T = new Template($_MG_CONF['template_path']);
    $T->set_file('admin', 'staticsortalbums.thtml');
    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('site_admin_url', $_CONF['site_admin_url']);

    // build album list for starting point...

    $root_album = new mgAlbum(0);
    $album_jumpbox  = '<select name="startaid">';
    $album_jumpbox .= '<option value="0">------</option>';
    $root_album->buildJumpBox($album_jumpbox, 0, 3);
    $album_jumpbox .= '</select>';

    // build sort fields select

    $sort_field     = '<select name="sortfield">';
    $sort_field    .= '<option value="0">' . $LANG_MG01['album_title'] . '</option>';
    $sort_field    .= '<option value="1">' . $LANG_MG01['media_count'] . '</option>';
    $sort_field    .= '<option value="2">' . $LANG_MG01['last_update'] . '</option>';
    $sort_field    .= '</select>';

    $T->set_var(array(
        's_form_action'          => $_MG_CONF['admin_url'] . 'staticsortalbums.php',
        'album_select'           => $album_jumpbox,
        'sort_field_select'      => $sort_field,
        'lang_starting_album'    => $LANG_MG01['starting_album'],
        'lang_sort_by'           => $LANG_MG03['sort_by'],
        'lang_sort_order'        => $LANG_MG01['order'],
        'lang_ascending'         => $LANG_MG01['ascending'],
        'lang_descending'        => $LANG_MG01['descending'],
        'lang_process_subs'      => $LANG_MG01['process_subs'],
        'lang_save'              => $LANG_MG01['save'],
        'lang_cancel'            => $LANG_MG01['cancel'],
        'lang_static_album_sort' => $LANG_MG01['static_sort_albums'],
    ));

    $retval .= $T->finish($T->parse('output', 'admin'));
    return $retval;
}

/**
* Main
*/

$mode = '';
if (isset($_POST['mode'])) {
    $mode = COM_applyFilter($_POST['mode']);
} else if (isset($_GET['mode'])) {
    $mode = COM_applyFilter($_GET['mode']);
}

$T = new Template($_MG_CONF['template_path']);
$T->set_file('admin', 'administration.thtml');
$T->set_var(array(
    'site_admin_url' => $_CONF['site_admin_url'],
    'site_url'       => $_MG_CONF['site_url'],
    'lang_admin'     => $LANG_MG00['admin'],
    'xhtml'          => XHTML,
));

if ($mode == $LANG_MG01['save'] && !empty($LANG_MG01['save'])) {
    $T->set_var(array(
        'admin_body' => MG_staticSortAlbumsSave(),
    ));
} elseif ($mode == $LANG_MG01['cancel']) {
    echo COM_refresh ($_MG_CONF['admin_url'] . 'index.php');
    exit;
} else {
    $T->set_var(array(
        'admin_body' => MG_staticSortAlbumOptions(),
        'title'      => $LANG_MG01['static_sort_albums'],
        'lang_help'  => '<img src="' . MG_getImageFile('button_help.png') . '" border="0" alt="?">',
        'help_url'   => $_MG_CONF['site_url'] . '/docs/usage.html#Static_Sort_Albums',
    ));
}

$T->parse('output', 'admin');

$display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
$display .= MG_showAdminMenu('batch_sessions');
$display .= $T->finish($T->get_var('output'));
$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
$display = COM_createHTMLDocument($display);

COM_output($display);
?>