<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | Quota report.                                                            |
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
    COM_errorLog("Someone has tried to illegally access the Media Gallery Quota Report. "
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

function MG_quotaReport($page, $quotaquery, $usedquery)
{
    global $glversion, $_TABLES, $_MG_CONF, $_CONF, $LANG_MG01, $LANG_MG03;

    $counter = 0;
    $rowcounter = 0;

    $retval = '';

    $start = $page * 50;
    $end   = 50;

    $T = new Template($_MG_CONF['template_path']);
    $T->set_file('report', 'quotareport.thtml');
    $T->set_var(array(
        'lang_username' => $LANG_MG01['username'],
        'lang_active'   => $LANG_MG01['active'],
        'lang_quota'    => $LANG_MG01['quota'],
        'lang_used'     => $LANG_MG01['used'],
        'lang_select'   => $LANG_MG01['select'],
        'xhtml'         => XHTML,
    ));
    $T->set_block('report', 'UserRow', 'uRow');

    if ($quotaquery > 0) {
        $quotaselect = $quotaquery * 1048676;
    } else {
        $quotaselect = 0;
    }

    $tres = DB_query("SELECT COUNT(gl.uid) AS count "
                   . "FROM {$_TABLES['users']} AS gl LEFT JOIN {$_TABLES['mg_userprefs']} AS mg ON gl.uid=mg.uid "
                   . "WHERE gl.status = 3 AND gl.uid > 2 AND mg.member_gallery=1 AND mg.quota >= " .$quotaselect);
    $trow = DB_fetchArray($tres);
    $total_records = $trow['count'];

    $sql = "SELECT gl.uid,  gl.status, gl.username, gl.fullname, mg.member_gallery, mg.quota "
         . "FROM {$_TABLES['users']} AS gl LEFT JOIN {$_TABLES['mg_userprefs']} AS mg ON gl.uid=mg.uid "
         . "WHERE gl.status = 3 AND gl.uid > 2 AND mg.member_gallery=1 AND mg.quota >= " . $quotaselect
         . " ORDER BY gl.username ASC LIMIT $start,$end";

    $result = DB_query($sql);

    while ($userRow = DB_fetchArray($result)) {
        $uid = $userRow['uid'];
        $active = DB_getItem($_TABLES['mg_userprefs'], 'active', "uid=" . intval($uid));
        $user_quota = DB_getItem($_TABLES['mg_userprefs'], 'quota', "uid=" . intval($uid));
        $quota_mb = $user_quota / 1048676;
        $quota = number_format(($quota_mb), 2);
        $used_mb = (float) MG_quotaUsage($uid) / 1048576;
        $used  = number_format(($used_mb), 2);

        $show = 1;
        if ($quotaquery > 0) { // limit based on quota
            $show = ($quota_mb >= $quotaquery) ? 1 : 0;
        }

        if ($show) {
            $T->set_var(array(
                'result_row' => $rowcounter,
                'rowclass'   => ($rowcounter % 2) ? '2' : '1',
                'username'   => '<a href="' . $_MG_CONF['admin_url'] . 'edituser.php?uid=' . $uid . '">' . $userRow['username'] . " (" . $userRow['fullname'] . ")</a>",
                'uid'        => $uid,
                'quota'      => ($quota == 0 ? 'Unlimited' : $quota),
                'used'       => $used, // COM_numberFormat($used),
                'active'     => $active,
            ));
            $T->parse('uRow', 'UserRow', true);
            $rowcounter++;
            $counter++;
        }
    }

    $T->set_var(array(
        'site_admin_url'    => $_MG_CONF['admin_url'],
        'used'              => $usedquery,
        'quota'             => $quotaquery,
        'lang_go'           => $LANG_MG03['go'],
        'lang_quota'        => $LANG_MG01['quota'],
        'lang_used'         => $LANG_MG01['used'],
        'lang_batch_update' => $LANG_MG01['batch_quota_update'],
        'lang_update'       => $LANG_MG01['update'],
        'pagenav'           => COM_printPageNavigation($_MG_CONF['admin_url'] . 'quotareport.php', $page+1,ceil($total_records / 50)),
    ));

    $retval .= $T->finish($T->parse('output', 'report'));
    return $retval;
}

if (isset($_POST['mode'])) {
    $mode = COM_applyFilter($_POST['mode']);
    $bquota = COM_applyFilter($_POST['bquota'], true);
    $bquota = $bquota * 1048576;
    $numItems = count($_POST['uid']);

    for ($i=0; $i < $numItems; $i++) {
        DB_change($_TABLES['mg_userprefs'], 'quota', $bquota, 'uid', $_POST['uid'][$i]);
        if (DB_error()) {
            $sql = "INSERT INTO {$_TABLES['mg_userprefs']} (uid, active, display_rows, display_columns, mp3_player, playback_mode, tn_size, quota, member_gallery) "
                 . "VALUES (" . $uid . ",1,0,0,-1,-1,-1," . $bquota . ",0)";
            DB_query($sql,1);
        }
    }
}

$page = isset($_GET['page']) ? COM_applyFilter($_GET['page'],true) : 0;
if ($page <= 0) {
    $page = 0;
} else {
    $page--;
}

$quota = isset($_POST['quota']) ? COM_applyFilter($_POST['quota'], true) : 0;
$used  = isset($_POST['used'])  ? COM_applyFilter($_POST['used'],  true) : 0;

$T = new Template($_MG_CONF['template_path']);
$T->set_file('admin', 'administration.thtml');
$T->set_var(array(
    'site_admin_url' => $_MG_CONF['admin_url'],
    'site_url'       => $_MG_CONF['site_url'],
    'lang_admin'     => $LANG_MG00['admin'],
    'xhtml'          => XHTML,
));

$T->set_var(array(
    'admin_body' => MG_quotaReport($page,$quota,$used),
    'title'      => $LANG_MG01['quota_report'],
    'lang_help'  => '<img src="' . MG_getImageFile('button_help.png') . '" border="0" alt="?">',
    'help_url'   => $_MG_CONF['site_url'] . '/docs/usage.html#Member_Album_User_list',
));

$T->parse('output', 'admin');

$display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
$display .= MG_showAdminMenu('member_albums');
$display .= $T->finish($T->get_var('output'));
$display .= COM_endBlock(COM_getBlockTemplate ('_admin_block', 'footer'));
$display = COM_createHTMLDocument($display, array('pagetitle' => $LANG_MG01['quota_report']));

COM_output($display);
?>