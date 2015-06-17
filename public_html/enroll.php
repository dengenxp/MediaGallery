<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | enroll.php                                                               |
// |                                                                          |
// | Self-enrollment for Member Albums                                        |
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

if (COM_isAnonUser()) {
    $display = SEC_loginRequiredForm();
    $display = MG_createHTMLDocument($display);
    COM_output($display);
    exit;
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';

function MG_enroll()
{
    global $_CONF, $_MG_CONF, $_TABLES, $_USER, $LANG_MG03;

    // let's make sure this user does not already have a member album

    if ($_MG_CONF['member_albums'] != 1) {
        echo COM_refresh($_MG_CONF['site_url'] . '/index.php');
        exit;
    }

    $sql = "SELECT album_id FROM {$_TABLES['mg_albums']} "
         . "WHERE owner_id=" . intval($_USER['uid'])
         . " AND album_parent=" . intval($_MG_CONF['member_album_root']);
    $result = DB_query($sql);
    $nRows = DB_numRows($result);
    if ($nRows > 0) {
        $display = COM_startBlock('', '', COM_getBlockTemplate('_msg_block', 'header'));
        $display .= $LANG_MG03['existing_member_album'];
        $display .= COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
        $display = MG_createHTMLDocument($display);
        COM_output($display);
        exit;
    }

    $T = COM_newTemplate(MG_getTemplatePath(0));
    $T->set_file('enroll', 'enroll.thtml');
    $T->set_var(array(
        's_form_action'              => $_MG_CONF['site_url'] . '/enroll.php',
        'lang_title'                 => $LANG_MG03['enroll_title'],
        'lang_overview'              => $LANG_MG03['overview'],
        'lang_terms'                 => $LANG_MG03['terms'],
        'lang_member_album_overview' => $LANG_MG03['member_album_overview'],
        'lang_member_album_terms'    => $LANG_MG03['member_album_terms'],
        'lang_agree'                 => $LANG_MG03['agree'],
        'lang_cancel'                => $LANG_MG03['cancel'],
    ));

    $retval .= $T->finish($T->parse('output', 'enroll'));
    return $retval;
}

function MG_saveEnroll()
{
    global $_CONF, $_MG_CONF, $_TABLES, $_USER, $LANG_MG03;

    if ($_MG_CONF['member_albums'] != 1) {
        echo COM_refresh($_MG_CONF['site_url'] . '/index.php');
        exit;
    }

    if (!isset($_MG_CONF['member_quota'])) {
        $_MG_CONF['member_quota'] = 0;
    }

    $sql = "SELECT album_id FROM {$_TABLES['mg_albums']} "
         . "WHERE owner_id=" . intval($_USER['uid'])
         . " AND album_parent=" . intval($_MG_CONF['member_album_root']);
    $result = DB_query($sql);
    $nRows = DB_numRows($result);
    if ($nRows > 0) {
        $display = COM_startBlock('', '', COM_getBlockTemplate('_msg_block', 'header'));
        $display .= $LANG_MG03['existing_member_album'];
        $display .= COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
        $display = MG_createHTMLDocument($display);
        COM_output($display);
        exit;
    }

    $uid = $_USER['uid'];
    $aid = plugin_user_create_mediagallery($uid,1);

    DB_change($_TABLES['mg_userprefs'], 'member_gallery', 1, 'uid', $uid);
    DB_change($_TABLES['mg_userprefs'], 'quota', intval($_MG_CONF['member_quota']), 'uid', $uid);

    if (DB_error()) {
        $sql = "INSERT INTO {$_TABLES['mg_userprefs']} "
             . "(uid, active, display_rows, display_columns, mp3_player, playback_mode, tn_size, quota, member_gallery) "
             . "VALUES (" . $uid . ",1,0,0,-1,-1,-1," . intval($_MG_CONF['member_quota']) . ",1)";
        DB_query($sql, 1);
    }
    echo COM_refresh($_MG_CONF['site_url'] . '/album.php?aid=' . $aid);
    exit;
}

// --- Main Processing Loop

$mode = isset($_REQUEST['mode']) ? COM_applyFilter($_REQUEST['mode']) : '';

$display  = '';

if ($mode == $LANG_MG03['agree'] && !empty($LANG_MG03['agree'])) {
    $display .= MG_saveEnroll();
} elseif ($mode == $LANG_MG03['cancel']) {
    echo COM_refresh($_MG_CONF['site_url'] . '/index.php');
    exit;
} else {
    $display .= MG_enroll();
}

$display = MG_createHTMLDocument($display);
COM_output($display);
?>