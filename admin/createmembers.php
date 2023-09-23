<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | Batch Create Member Albums                                               |
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

function MG_selectUsers($page)
{
    global $glversion, $_CONF, $_MG_CONF, $_TABLES, $_USER, $LANG_MG00, $LANG_MG01;

    $retval = '';
    $T = new Template($_MG_CONF['template_path']);
    $T->set_file('admin','createmembers.thtml');
    $T->set_var(array(
        'site_admin_url' => $_CONF['site_admin_url'],
        'site_url'       => $_CONF['site_url'],
        'xhtml'          => XHTML,
    ));
    $T->set_block('admin', 'UserRow', 'uRow');

    $start = $page * 50;
    $end   = 50;

    $sql = "SELECT COUNT(gl.uid) AS count "
         . "FROM {$_TABLES['users']} AS gl "
         . "LEFT JOIN {$_TABLES['mg_userprefs']} AS mg ON gl.uid=mg.uid "
         . "WHERE gl.status = 3 AND gl.uid > 2 AND (mg.member_gallery IS NULL OR mg.member_gallery < 1)";
    $result = DB_query($sql);
    list($total_records) = DB_fetchArray($result);

    $sql = "SELECT gl.uid, gl.status, gl.username, gl.fullname, mg.member_gallery "
         . "FROM {$_TABLES['users']} AS gl "
         . "LEFT JOIN {$_TABLES['mg_userprefs']} AS mg ON gl.uid=mg.uid "
         . "WHERE gl.status = 3 AND gl.uid > 2 AND (mg.member_gallery IS NULL OR mg.member_gallery < 1) "
         . "ORDER BY gl.username ASC LIMIT $start,$end";
    $result = DB_query($sql);
    while ($row = DB_fetchArray($result)) {
        if (isset($glversion[1]) && ($glversion[1] < 4)) {
            $row['status'] = 3;
        }
        $uid = $row['uid'];
        $remote = (SEC_inGroup("Remote Users",$uid) ? '(r)' : '');
        $username = $row['username'];
        $member_gallery = $row['member_gallery'];
        $T->set_var(array(
            'uid'      => $uid,
            'username' => $username . ' ' . $remote . ' - ' . $row['fullname'],
            'select'   => '<input type="checkbox" name="user[]" value="' . $uid . '"' . XHTML . '>',
        ));
        $T->parse('uRow', 'UserRow', true);
    }

    $T->set_var(array(
        'lang_userid'     => $LANG_MG01['userid'],
        'lang_username'   => $LANG_MG01['username'],
        'lang_select'     => $LANG_MG01['select'],
        'lang_checkall'   => $LANG_MG01['check_all'],
        'lang_uncheckall' => $LANG_MG01['uncheck_all'],
        'lang_save'       => $LANG_MG01['save'],
        'lang_cancel'     => $LANG_MG01['cancel'],
        'lang_reset'      => $LANG_MG01['reset'],
        's_form_action'   => $_MG_CONF['admin_url'] . 'createmembers.php',
        'pagenav'         => COM_printPageNavigation($_MG_CONF['admin_url'] . 'createmembers.php', $page+1,ceil($total_records  / 50)),
    ));

    $retval .= $T->finish($T->parse('output', 'admin'));
    return $retval;
}

function MG_createUsers()
{
    global $_MG_CONF;

    if (!empty($_POST['user'])) {
        $numItems = count($_POST['user']);
        for ($i=0; $i < $numItems; $i++) {
            plugin_user_create_mediagallery($_POST['user'][$i], 1);
        }
    }
    COM_redirect($_MG_CONF['admin_url'] . 'createmembers.php');
}

/**
* Main
*/

$mode = '';
if (isset ($_POST['mode'])) {
    $mode = COM_applyFilter($_POST['mode']);
} else if (isset ($_GET['mode'])) {
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

if ($mode == $LANG_MG01['save'] && !empty ($LANG_MG01['save'])) {
    MG_createUsers();
    exit;
} elseif ($mode == $LANG_MG01['cancel']) {
    COM_redirect($_MG_CONF['admin_url'] . 'index.php');
} else {
    if ( isset($_REQUEST['page']) ) {
        $page = COM_applyFilter($_REQUEST['page'],true) - 1;
        if ( $page < 0 ) {
            $page = 0;
        }
    } else {
        $page = 0;
    }
    $T->set_var(array(
        'admin_body' => MG_selectUsers($page),
        'title'      => $LANG_MG01['batch_create_members'],
        'lang_help'  => '<img src="' . MG_getImageFile('button_help.png') . '" style="border:none;" alt="?"' . XHTML . '>',
        'help_url'   => $_MG_CONF['site_url'] . '/docs/usage.html#Batch_Create_Member_Albums',
    ));
}

$T->parse('output', 'admin');

$display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
$display .= MG_showAdminMenu('member_albums');
$display .= $T->finish($T->get_var('output'));
$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
$display = COM_createHTMLDocument($display);

COM_output($display);
?>