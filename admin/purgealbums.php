<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | Batch Purge Member Albums                                                |
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

function MG_selectAlbums()
{
    global $glversion, $_CONF, $_MG_CONF, $_TABLES, $LANG_MG01, $LANG_MG07;

    // start by building an array of all site users (active)

    if ($glversion[1] < 4) {
        $result = DB_query("SELECT * FROM {$_TABLES['users']} AS users LEFT JOIN {$_TABLES['userinfo']} AS userinfo ON users.uid=userinfo.uid");
    } else {
        $result = DB_query("SELECT * FROM {$_TABLES['users']} AS users LEFT JOIN {$_TABLES['userinfo']} AS userinfo ON users.uid=userinfo.uid WHERE users.status=3");
    }
    while ($U = DB_fetchArray($result)) {
        $siteUsers[$U['uid']]['lastlogin'] = $U['lastlogin'];
        $siteUsers[$U['uid']]['username']  = $U['username'];
        $siteUsers[$U['uid']]['fullname']  = $U['fullname'];
    }

    $retval = '';
    $T = new Template($_MG_CONF['template_path']);
    $T->set_file('admin', 'purgealbums.thtml');
    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('site_admin_url', $_CONF['site_admin_url']);
    $T->set_var('xhtml', XHTML);

    $T->set_block('admin', 'UserRow', 'uRow');
    $rowcounter = 0;
    $rowclass = 0;

    // now process all the albums directly off the album_root
    //  we will not call this recursively, we only care about those off the root

    $album = new mgAlbum($_MG_CONF['member_album_root']);
    $children = $album->getChildren();
    $numItems = count($children);
    for ($x=0; $x < $numItems; $x++) {
        $child_album = new mgAlbum($children[$x]);
        if ($child_album->getMediaCount() == 0) {
            if ($siteUsers[ $child_album->owner_id ]['lastlogin'] == 0) {
                $lastlogin[0] = $LANG_MG07['never'];
            } else {
                $lastlogin = MG_getUserDateTimeFormat($siteUsers[ $child_album->owner_id ]['lastlogin']);
            }
            $T->set_var(array(
                'select'    => '<input type="checkbox" name="album[]" value="' . $child_album->id . '">',
                'aid'       => $child_album->id,
                'title'     => $child_album->title,
                'owner'     => $siteUsers[ $child_album->owner_id ]['username'] . '/' . $siteUsers[ $child_album->owner_id ]['fullname'],
                'lastlogin' => $lastlogin[0],
                'rowclass'  => ($rowclass % 2 ? '1' : '2'),
            ));
            $T->parse('uRow', 'UserRow', true);
            $rowcounter++;
        }
    }

    $T->set_var(array(
        'lang_last_login'  => $LANG_MG01['last_login'],
        'lang_album_title' => $LANG_MG01['album_title'],
        'lang_userid'      => $LANG_MG01['userid'],
        'lang_username'    => $LANG_MG01['username'],
        'lang_select'      => $LANG_MG01['select'],
        'lang_checkall'    => $LANG_MG01['check_all'],
        'lang_uncheckall'  => $LANG_MG01['uncheck_all'],
        'lang_delete'      => $LANG_MG01['delete'],
        'lang_cancel'      => $LANG_MG01['cancel'],
        'lang_reset'       => $LANG_MG01['reset'],
        's_form_action'    => $_MG_CONF['admin_url'] . 'purgealbums.php',
    ));

    $retval .= $T->finish($T->parse('output', 'admin'));
    return $retval;
}

function MG_purgeMemberAlbums()
{
    global $_CONF, $_MG_CONF, $_TABLES, $LANG_MG01;

    $numItems = !empty($_POST['album']) ? count($_POST['album']) : 0;
    if ($numItems > 0) {
        for ($i=0; $i < $numItems; $i++) {
            $album_id = COM_applyFilter($_POST['album'][$i], true);
            // grab owner ID
            $sql = "SELECT owner_id FROM {$_TABLES['mg_albums']} WHERE album_id=" . intval($album_id);
            $result = DB_query($sql);
            while (list($owner_id) = DB_fetchArray($result)) {
                DB_change($_TABLES['mg_userprefs'], 'member_gallery', 0, 'uid', $owner_id);
            }
            MG_deleteChildAlbums($album_id);
        }
        require_once $_CONF['path'] . 'plugins/mediagallery/include/rssfeed.php';
        MG_buildFullRSS();
    }

    echo COM_refresh($_MG_CONF['admin_url'] . 'index.php?msg=8');
    exit;
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

if ($mode == $LANG_MG01['delete'] && !empty ($LANG_MG01['delete'])) {
    require_once $_CONF['path'] . 'plugins/mediagallery/include/batch.php';
    MG_purgeMemberAlbums();
    exit;
} elseif ($mode == $LANG_MG01['cancel']) {
    echo COM_refresh ($_MG_CONF['admin_url'] . 'index.php');
    exit;
} else {
    $T->set_var(array(
        'admin_body' => MG_selectAlbums(),
        'title'      => $LANG_MG01['purge_member_albums'],
        'status_msg' => $LANG_MG01['purge_mem_albums_help'],
        'lang_help'  => '<img src="' . MG_getImageFile('button_help.png') . '" style="border:none;" alt="?"' . XHTML . '>',
        'help_url'   => $_MG_CONF['site_url'] . '/docs/usage.html#Purge_Member_Albums',
    ));
}

$display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
$display .= MG_showAdminMenu('member_albums');
$display .= $T->finish($T->parse('output', 'admin'));
$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
$display = COM_createHTMLDocument($display);

COM_output($display);
?>