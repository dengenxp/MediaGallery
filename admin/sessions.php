<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | Administer Media Gallery sessions.                                       |
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

function MG_batchDeleteSession()
{
    global $_MG_CONF, $_CONF, $_TABLES;

    if (!empty($_POST['sel'])) {
        $numItems = count($_POST['sel']);
        for ($i=0; $i < $numItems; $i++) {
            DB_delete($_TABLES['mg_session_items'], 'session_id', $_POST['sel'][$i]);
            if (DB_error()) {
                COM_errorLog("Media Gallery Error: Error removing session items");
            }
            DB_delete($_TABLES['mg_sessions'], 'session_id', $_POST['sel'][$i]);
            if (DB_error()) {
                COM_errorLog("Media Gallery Error: Error removing session");
            }
        }
    }

    COM_redirect($_MG_CONF['admin_url'] . 'sessions.php');
}

function MG_displaySessions()
{
    global $_CONF, $_MG_CONF, $_TABLES, $LANG_MG01;

    $retval = '';
    $T = new Template($_MG_CONF['template_path']);
    $T->set_file('sessions', 'sessions.thtml');
    $T->set_var(array(
        'site_url'                  => $_CONF['site_url'],
        'xhtml'                     => XHTML,
        's_form_action'             => $_MG_CONF['admin_url'] . 'sessions.php',
        'lang_save'                 => $LANG_MG01['save'],
        'lang_cancel'               => $LANG_MG01['cancel'],
        'lang_delete'               => $LANG_MG01['delete'],
        'lang_select'               => $LANG_MG01['select'],
        'lang_checkall'             => $LANG_MG01['check_all'],
        'lang_uncheckall'           => $LANG_MG01['uncheck_all'],
        'lang_session_description'  => $LANG_MG01['description'],
        'lang_session_owner'        => $LANG_MG01['owner'],
        'lang_session_count'        => $LANG_MG01['count'],
        'lang_action'               => $LANG_MG01['action'],
    ));
    $T->set_block('sessions', 'sessItems', 'sItems');

    $sql      = "SELECT * FROM {$_TABLES['mg_sessions']} WHERE session_status=1";
    $result   = DB_query($sql);
    $numRows  = DB_numRows($result);
    $rowclass = 0;

    if ($numRows == 0) {
        // we have no active sessions
        $T->set_var('lang_no_sessions', $LANG_MG01['no_sessions']);
        $T->set_var('noitems', true);
        $T->set_var('sItems', '');
    } else {
        $totalSess = $numRows;
        $T->set_block('sessions', 'sessRow', 'sRow');
        for ($x = 0; $x < $numRows; $x++) {
            $row = DB_fetchArray($result);

            $res2 = DB_query("SELECT COUNT(id) FROM {$_TABLES['mg_session_items']} "
                           . "WHERE session_id='" . $row['session_id'] . "' AND status=0");
            list($count) = DB_fetchArray($res2);

            $T->set_var(array(
                'row_class'           => ($rowclass % 2) ? '2' : '1',
                'session_id'          => $row['session_id'],
                'session_owner'       => DB_getItem($_TABLES['users'],'username',"uid={$row['session_uid']}"),
                'session_description' => $row['session_description'],
                'session_continue'    => $_MG_CONF['site_url'] . '/batch.php?mode=continue&amp;sid=' . $row['session_id'] . '&amp;limit=0',
                'count'               => $count,
            ));
            $T->parse('sRow', 'sessRow', true);
            $rowclass++;
        }
        $T->parse('sItems', 'sessItems');
    }

    $retval .= $T->finish($T->parse('output', 'sessions'));
    return $retval;
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

if ($mode == $LANG_MG01['cancel']) {
    COM_redirect($_MG_CONF['admin_url'] . 'index.php');
} elseif ($mode == $LANG_MG01['delete'] && !empty ($LANG_MG01['delete'])) {
    MG_batchDeleteSession();
} else {
    $T->set_var(array(
        'admin_body' => MG_displaySessions(),
        'title'      => $LANG_MG01['batch_sessions'],
        'lang_help'  => '<img src="' . MG_getImageFile('button_help.png') . '" style="border:none;" alt="?"' . XHTML . '>',
        'help_url'   => $_MG_CONF['site_url'] . '/docs/usage.html#Paused_Sessions',
    ));
}

$display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
$display .= MG_showAdminMenu('batch_sessions');
$display .= $T->finish($T->parse('output', 'admin'));
$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
$display = COM_createHTMLDocument($display);

COM_output($display);
?>