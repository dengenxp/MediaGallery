<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | Administer Media Gallery categories.                                     |
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
require_once $_MG_CONF['path_admin'] . 'navigation.php';

function MG_editCategory($cat_id, $mode)
{
    global $_CONF, $_TABLES, $_MG_CONF, $LANG_MG01;

    $retval = '';

    if ($cat_id==0 && $mode == 'create') {
        // set the album_id
        $sql = "SELECT MAX(cat_id) + 1 AS nextcat_id FROM " . $_TABLES['mg_category'];
        $result = DB_query( $sql );
        $row = DB_fetchArray( $result );
        $A['cat_id'] = $row['nextcat_id'];
        if ($A['cat_id'] < 1) {
            $A['cat_id'] = 1;
        }
        if ($A['cat_id'] == 0) {
            COM_errorLog("Media Gallery Error - Returned 0 as cat_id");
            $A['cat_id'] = 1;
        }
        $A['cat_name'] = '';
        $A['cat_description'] = '';
    } else {
        $A['cat_id'] = $cat_id;
        // pull info from DB
        $sql = "SELECT * FROM {$_TABLES['mg_category']} WHERE cat_id=" . $cat_id;
        $result = DB_query($sql);
        $numRows = DB_numRows($result);
        if ($numRows > 0) {
            $A = DB_fetchArray($result);
        }
    }

    $T = new Template($_MG_CONF['template_path']);
    $T->set_file('admin', 'editcategory.thtml');
    $T->set_var(array(
        'site_url'            => $_CONF['site_url'],
        'site_admin_url'      => $_CONF['site_admin_url'],
        'xhtml'               => XHTML,
        'action'              => 'edit_category',
        'cat_id'              => $A['cat_id'],
        'cat_name'            => $A['cat_name'],
        'cat_description'     => $A['cat_description'],
        'lang_save'           => $LANG_MG01['save'],
        'lang_edit_category'  => ($mode=='create' ? $LANG_MG01['create_category'] : $LANG_MG01['edit_category']),
        's_form_action'       => $_MG_CONF['admin_url'] . 'category.php',
        'lang_cat_edit_help'  => $LANG_MG01['cat_edit_help'],
        'lang_title'          => $LANG_MG01['title'],
        'lang_description'    => $LANG_MG01['description'],
        'lang_cancel'         => $LANG_MG01['cancel'],
        'lang_delete'         => $LANG_MG01['delete'],
        'lang_delete_confirm' => $LANG_MG01['delete_item_confirm'],
    ));
    if ($_MG_CONF['htmlallowed'] == 1) {
        $T->set_var('allowed_html', COM_allowedHTML());
    }
    $retval .= $T->finish($T->parse('output', 'admin'));

    return $retval;
}

function MG_saveCategory($cat_id)
{
    global $_USER, $_CONF, $_TABLES, $_MG_CONF, $LANG_MG00, $LANG_MG01, $LANG_MG02;

    $update = 0;

    $A['cat_id'] = COM_applyFilter($_POST['cat_id'], true);

    if ($_MG_CONF['htmlallowed'] == 1) {
        $A['cat_name']        = addslashes(COM_checkHTML(COM_killJS($_POST['cat_name'])));
        $A['cat_description'] = addslashes(COM_checkHTML(COM_killJS($_POST['cat_desc'])));
    } else {
        $A['cat_name']        = addslashes(htmlspecialchars(strip_tags(COM_checkWords(COM_killJS($_POST['cat_name'])))));
        $A['cat_description'] = addslashes(htmlspecialchars(strip_tags(COM_checkWords(COM_killJS($_POST['cat_desc'])))));
    }

    if (empty($A['cat_name'])) {
        return COM_showMessageText($LANG_MG01['category_error']
            . '  [ <a href=\'javascript:history.go(-1)\'>' . $LANG_MG02['go_back'] . '</a> ]');
    }

    $sql = "SELECT MAX(cat_order) + 1 AS nextcat_order FROM " . $_TABLES['mg_category'];
    $result = DB_query($sql);
    $row = DB_fetchArray($result);
    if ($row == NULL || $result == NULL) {
        $A['cat_order'] = 10;
    } else {
        $A['cat_order'] = $row['nextcat_order'];
        if ($A['cat_order'] < 0) {
            $A['cat_order'] = 10;
        }
    }
    if ($A['cat_order'] == NULL)
        $A['cat_order'] = 10;

    //
    //  -- Let's make sure we don't have any SQL overflows...
    //

    $A['cat_name'] = substr($A['cat_name'], 0, 254);

    if ($A['cat_id'] == 0) {
        COM_errorLog("Media Gallery Internal Error - cat_id = 0 - Contact mark@gllabs.org  ");
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    DB_save($_TABLES['mg_category'], "cat_id,cat_name,cat_description,cat_order",
            "'{$A['cat_id']}','{$A['cat_name']}','{$A['cat_description']}',{$A['cat_order']}");

    echo COM_refresh($_MG_CONF['admin_url'] . 'category.php');
    exit;
}

function MG_batchDeleteCategory()
{
    global $_MG_CONF, $_CONF, $_TABLES;

    $numItems = !empty($_POST['sel']) ? count($_POST['sel']) : 0;
    for ($i=0; $i < $numItems; $i++) {
        DB_delete($_TABLES['mg_category'], 'cat_id', COM_applyFilter($_POST['sel'][$i],true));
        if (DB_error()) {
            COM_errorLog("Media Gallery: Error removing category");
        }
        // now remove it from all the media items...
        DB_change($_TABLES['mg_media'], 'media_category', 0,
                  'media_category', COM_applyFilter($_POST['sel'][$i],true));
        if (DB_error()) {
            COM_errorLog("Media Gallery: Error removing category from media table");
        }
    }

    echo COM_refresh($_MG_CONF['admin_url'] . 'category.php');
    exit;
}

function MG_displayCategories()
{
    global $_CONF, $_MG_CONF, $_TABLES, $LANG_MG01;

    $retval = '';

    $sql = "SELECT * FROM {$_TABLES['mg_category']} ORDER BY cat_id ASC";
    $result = DB_query($sql);
    $numRows = DB_numRows($result);

    $T = COM_newTemplate($_MG_CONF['template_path']);
    $T->set_file('category', 'category.thtml');
    $T->set_block('category', 'catRow', 'cRow');

    $rowclass = 1;
    for ($x = 0; $x < $numRows; $x++) {
        $row = DB_fetchArray($result);
        $T->set_var(array(
            'row_class'       => ($rowclass % 2) ? '1' : '2',
            'cat_id'          => $row['cat_id'],
            'edit_cat_id'     => '<a href="' . $_MG_CONF['admin_url'] . 'category.php?mode=edit'
                               . '&amp;id=' . $row['cat_id'] . '">' . $row['cat_id'] . '</a>',
            'cat_name'        => $row['cat_name'],
            'cat_description' => $row['cat_description'],
            'cat_order'       => $row['cat_order'],
        ));
        $T->parse('cRow', 'catRow', true);
        $rowclass++;
    }

    $T->set_var(array(
        's_form_action'             => $_MG_CONF['admin_url'] . 'category.php',
        'mode'                      => 'category',
        'noitems'                   => ($numRows == 0) ? true : false,
        'lang_no_cat'               => $LANG_MG01['no_cat'],
        'lang_category_manage_help' => $LANG_MG01['category_manage_help'],
        'lang_catid'                => $LANG_MG01['cat_id'],
        'lang_cat_name'             => $LANG_MG01['cat_name'],
        'lang_cat_description'      => $LANG_MG01['cat_description'],
        'lang_order'                => $LANG_MG01['order'],
        'lang_save'                 => $LANG_MG01['save'],
        'lang_cancel'               => $LANG_MG01['cancel'],
        'lang_delete'               => $LANG_MG01['delete'],
        'lang_create'               => $LANG_MG01['create'],
        'lang_select'               => $LANG_MG01['select'],
        'lang_checkall'             => $LANG_MG01['check_all'],
        'lang_uncheckall'           => $LANG_MG01['uncheck_all'],
        'lang_batch'                => $LANG_MG01['batch_process'],
        'lang_delete_confirm'       => $LANG_MG01['delete_item_confirm'],
        'site_url'                  => $_CONF['site_url'],
        'site_admin_url'            => $_CONF['site_admin_url'],
        'xhtml'                     => XHTML,
    ));
    $retval .= $T->finish($T->parse('output', 'category'));

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
$action = '';
if (isset($_POST['action'])) {
    $action = COM_applyFilter($_POST['action']);
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
    $cat_id = COM_applyFilter($_POST['cat_id'],true);
    $T->set_var(array(
        'admin_body' => MG_saveCategory($cat_id),
    ));
} elseif ($mode == $LANG_MG01['cancel']) {
    $page = 'index.php';
    if ($action == 'edit_category') {
        $page = 'category.php';
    }
    echo COM_refresh($_MG_CONF['admin_url'] . $page);
    exit;
} elseif ($mode == $LANG_MG01['create'] && !empty($LANG_MG01['create'])) {
    $T->set_var(array(
        'admin_body' => MG_editCategory(0,'create'),
        'title'      => $LANG_MG01['create_category'],
    ));
} elseif ($mode == 'edit') {
    $cat_id = COM_applyFilter($_GET['id'], true);
    $T->set_var(array(
        'admin_body' => MG_editCategory($cat_id, 'edit'),
        'title'      => $LANG_MG01['edit_category'],
    ));

} elseif ($mode == $LANG_MG01['delete'] && !empty($LANG_MG01['delete'])) {
    if (isset($_POST['cat_id'])) {
        $cat_id = $_POST['cat_id'];
        MG_batchDeleteCategory($cat_id);
    } else {
        MG_batchDeleteCategory(0);
    }
} else {
    $T->set_var(array(
        'admin_body' => MG_displayCategories(),
        'title'      => $LANG_MG01['category_manage_help'],
        'lang_help'  => '<img src="' . MG_getImageFile('button_help.png') . '" style="border:none;" alt="?"' . XHTML . '>',
        'help_url'   => $_MG_CONF['site_url'] . '/docs/usage.html#Category_Maintenance',
    ));
}

$display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
$display .= MG_showAdminMenu();
$display .= $T->finish($T->parse('output', 'admin'));
$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
$display = COM_createHTMLDocument($display);

COM_output($display);
?>