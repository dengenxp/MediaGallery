<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | Media Gallery Maintenance Routines                                       |
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

$step = isset($_REQUEST['step']) ? COM_applyFilter($_REQUEST['step']) : '';
$mode = isset($_POST['submit'])  ? COM_applyFilter($_POST['submit'])  : '';

if ($mode == $LANG_MG01['cancel']) {
    echo COM_refresh($_MG_CONF['admin_url'] . 'index.php');
    exit;
}

switch ($step) {
    case 'two' :
        // pull all users from the Geeklog user table
        $result = DB_query("SELECT * FROM {$_TABLES['users']}");
        while ( $U = DB_fetchArray($result) ) {
            $result2 = DB_query("SELECT album_id FROM {$_TABLES['mg_albums']} WHERE owner_id=" . $U['uid']
                              . " AND album_parent=" . $_MG_CONF['member_album_root']);
            $nRows = DB_numRows($result2);
            // see if they have an entry in userprefs
            $result3 = DB_query("SELECT uid FROM {$_TABLES['mg_userprefs']} WHERE uid=" . $U['uid']);
            $pRows = DB_numRows($result3);
            $member_gallery = ($nRows > 0) ? 1 : 0;
            if ($pRows > 0) {
                DB_change($_TABLES['mg_userprefs'], 'member_gallery', $member_gallery, 'uid', $U['uid']);
                DB_change($_TABLES['mg_userprefs'], 'active', 1, 'uid', $U['uid']);
            } else {
                $sql = "INSERT INTO {$_TABLES['mg_userprefs']} "
                     . "(uid, active, display_rows, display_columns, mp3_player, playback_mode, tn_size, quota, member_gallery) "
                     . "VALUES (" . $U['uid'] . ",1,0,0,-1,-1,-1," . $_MG_CONF['member_quota'] . "," . $member_gallery . ")";
                DB_query($sql);
            }
        }
        echo COM_refresh($_MG_CONF['admin_url'] . 'index.php?msg=14');
        exit;
    default :
        $B = new Template($_MG_CONF['template_path']);
        $B->set_file('admin', 'thumbs.thtml');
        $B->set_var(array(
            'site_admin_url' => $_CONF['site_admin_url'],
            'site_url'       => $_CONF['site_url'],
            'xhtml'          => XHTML,
            'lang_title'     => $LANG_MG01['reset_members'],
            's_form_action'  => $_MG_CONF['admin_url'] . 'resetmembers.php?step=two',
            'lang_next'      => $LANG_MG01['next'],
            'lang_cancel'    => $LANG_MG01['cancel'],
            'lang_details'   => $LANG_MG01['reset_members_details'],
        ));
        $B->parse('output', 'admin');

        $T = new Template($_MG_CONF['template_path']);
        $T->set_file('admin', 'administration.thtml');
        $T->set_var(array(
            'site_admin_url' => $_CONF['site_admin_url'],
            'site_url'       => $_MG_CONF['site_url'],
            'xhtml'          => XHTML,
            'admin_body'     => $B->finish($B->get_var('output')),
            'title'          => $LANG_MG01['reset_members'],
            'lang_admin'     => $LANG_MG00['admin'],
            'lang_help'      => '<img src="' . MG_getImageFile('button_help.png') . '" style="border:none;" alt="?"' . XHTML . '>',
            'help_url'       => $_MG_CONF['site_url'] . '/docs/usage.html#Reset_Member_Album_Create_Flag',
        ));
        $T->parse('output', 'admin');
        $display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
        $display .= MG_showAdminMenu('member_albums');
        $display .= $T->finish($T->get_var('output'));
        $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
        $display = COM_createHTMLDocument($display);
        COM_output($display);
        break;
}
?>