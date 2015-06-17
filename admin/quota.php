<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | Media Gallery Rebuild User Quotas                                        |
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
require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-media.php';
require_once $_MG_CONF['path_admin'] . 'navigation.php';

function MG_quotaConfirm()
{
    global $_MG_CONF, $_CONF, $LANG_MG01;

    $retval = '';

    $B = new Template($_MG_CONF['template_path']);
    $B->set_file('admin', 'quotaconfirm.thtml');
    $B->set_var('site_url', $_CONF['site_url']);
    $B->set_var('site_admin_url', $_CONF['site_admin_url']);
    $B->set_var(array(
        'lang_title'    => $LANG_MG01['rebuild_quota'],
        's_form_action' => $_MG_CONF['admin_url'] . 'quota.php?mode=rebuild',
        'lang_save'     => $LANG_MG01['save'],
        'lang_cancel'   => $LANG_MG01['cancel'],
        'lang_details'  => $LANG_MG01['rebuild_quota_help'],
    ));
    $B->parse('output', 'admin');
    $retval .= $B->finish($B->get_var('output'));
    return $retval;
}

function MG_rebuildQuota()
{
    global $_TABLES, $_MG_CONF, $_CONF;

    $result = DB_query("SELECT album_id FROM {$_TABLES['mg_albums']}");
    while ($row = DB_fetchArray($result)) {
        MG_updateQuotaUsage($row['album_id']);
    }
    echo COM_refresh($_MG_CONF['admin_url'] . 'index.php?msg=16');
    exit;
}

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

if ($mode == $LANG_MG01['save'] && !empty ($LANG_MG01['save'])) {
    MG_rebuildQuota();
} elseif ($mode == $LANG_MG01['cancel']) {
    echo COM_refresh ($_MG_CONF['admin_url'] . 'index.php');
    exit;
} else {
    $T->set_var(array(
        'admin_body' => MG_quotaConfirm(),
        'title'      => $LANG_MG01['rebuild_quota'],
        'lang_help'  => '<img src="' . MG_getImageFile('button_help.png') . '" border="0" alt="?">',
        'help_url'   => $_MG_CONF['site_url'] . '/docs/usage.html#Rebuild_User_Quota',
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