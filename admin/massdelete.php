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
require_once $_CONF['path'].'plugins/mediagallery/include/classAlbum.php';
require_once $_MG_CONF['path_admin'] . 'navigation.php';

function _showSelectTree($aid=0, $level=0)
{
    $album = new mgAlbum($aid);

    $retval = '';

    $children = $album->getChildren();
    if ($album->id != 0 && $album->access > 0) {
        $block = '';
        if (!empty($children)) {
            $retval .= "<script type=\"text/javascript\"><!--" . LB
            . "function enableBlock" . $album->id . "() {" . LB
            . "  if (document.galselect.elements['album[" . $album->id ."]'].checked) {" . LB;
            foreach ($children as $child) {
                $retval .= "    document.galselect.elements['album[" . $child . "]'].disabled = true;" . LB;
                $retval .= "    document.galselect.elements['album[" . $child . "]'].checked = true;" . LB;
            }
            $retval .= "  } else {" . LB;
            foreach ($children as $child) {
                $retval .= "    document.galselect.elements['album[" . $child . "]'].disabled = false;" . LB;
                $retval .= "    document.galselect.elements['album[" . $child . "]'].checked = false;" . LB;
            }
            $retval .= "  }" . LB;
            foreach ($children as $child) {
                $child_of_child = $album->getChildren($child);
                if (!empty($child_of_child)) {
                    $retval .= '  enableBlock' . $child . '();' . LB;
                }
            }
            $retval .= "}" . LB . "// -->" . LB . "</script>" . LB;
            $block = 'onclick="enableBlock' . $album->id . '()" onchange="enableBlock' . $album->id . '()"';
        }
        if ($album->parent != 0)
            $block = '';

        $px = ($level - 1) * 15;
        $retval .= '<div style="margin-left:' . $px . 'px;">'
                 . '<input type="checkbox" name="album[' . $album->id . ']" id="album_' . $album->id . '" value="1" ' . $block . XHTML . '>&nbsp;&nbsp;'
                 . strip_tags($album->title) . ' (' . COM_numberFormat($album->album_disk_usage/1024) . ' Kb)</div>' . LB;
    }

    $level++;
    foreach ($children as $child) {
        $retval .= _showSelectTree($child, $level);
    }
    return $retval;
}

function MG_massDelete()
{
    global $_CONF, $_MG_CONF, $LANG_MG01;

    $retval = '';
    $T = COM_newTemplate($_MG_CONF['template_path']);
    $T->set_file('admin','massdelete.thtml');
    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('site_admin_url', $_CONF['site_admin_url']);
    $T->set_var(array(
        'album_list'          => _showSelectTree(),
        's_form_action'       => $_MG_CONF['admin_url'] . 'massdelete.php',
        'lang_save'           => $LANG_MG01['save'],
        'lang_cancel'         => $LANG_MG01['cancel'],
        'lang_reset'          => $LANG_MG01['reset'],
        'lang_delete_confirm' => $LANG_MG01['delete_item_confirm'],
        'lang_delete'         => $LANG_MG01['delete'],
        'lang_batch_delete'   => $LANG_MG01['batch_delete_albums'],
    ));
    $retval .= $T->finish($T->parse('output', 'admin'));
    return $retval;
}


function MG_MassdeleteAlbum($album_id)
{
    global $_USER, $_CONF, $_TABLES, $_MG_CONF, $LANG_MG00;

    // need to check perms here...

    $album_data = MG_getAlbumData($album_id, array('album_parent'), true);

    if ($album_data['access'] != 3) {
        COM_errorLog("MediaGallery: Someone has tried to illegally delete an album in Media Gallery. "
                   . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: " . $_SERVER['REMOTE_ADDR'], 1);
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }
    MG_MassdeleteChildAlbums( $album_id );

    if ($_MG_CONF['member_albums'] == 1 && $album_data['parent'] == $_MG_CONF['member_album_root']) {
        $result = DB_query("SELECT * FROM {$_TABLES['mg_albums']} WHERE owner_id=" . $album_data['owner_id']
                        . " AND album_parent=" . $album_data['parent']);
        $numRows = DB_numRows($result);
        if ($numRows == 0) {
            DB_change($_TABLES['mg_userprefs'], 'member_gallery', 0, 'uid', $album_data['owner_id']);
        }
    }
    require_once $_CONF['path'] . 'plugins/mediagallery/include/rssfeed.php';
    MG_buildFullRSS();
    echo COM_refresh($_MG_CONF['admin_url'] . 'index.php?msg=15');
}



/**
* Recursivly deletes all albums and child albums
*
* @param    int     album_id    album id to delete
* @return   int     true for success or false for failure
*
*/
function MG_MassdeleteChildAlbums($album_id)
{
    global $_CONF, $_MG_CONF, $_TABLES, $_USER;

    $sql = "SELECT album_id FROM {$_TABLES['mg_albums']} WHERE album_parent=" . intval($album_id);
    $aResult = DB_query($sql);
    while ($row = DB_fetchArray($aResult)) {
        MG_MassdeleteChildAlbums($row['album_id']);
    }

    $sql = MG_buildMediaSql(array(
        'album_id'  => $album_id,
        'fields'    => array('media_id', 'media_filename', 'media_mime_ext'),
        'sortorder' => -1
    ));
    $result = DB_query($sql);
    while ($A = DB_fetchArray($result)) {
        $count = DB_count($_TABLES['mg_media_albums'], 'media_id', addslashes($A['media_id']));
        if ($count <= 1) {
            $fn = $A['media_filename'];
            @unlink($_MG_CONF['path_mediaobjects'] . 'tn/'   . $fn[0] . '/' . $fn . '.*');
            @unlink($_MG_CONF['path_mediaobjects'] . 'disp/' . $fn[0] . '/' . $fn . '.*');
            @unlink($_MG_CONF['path_mediaobjects'] . 'orig/' . $fn[0] . '/' . $fn . '.' . $A['media_mime_ext']);
            DB_delete($_TABLES['mg_media'], 'media_id', addslashes($A['media_id']));
            DB_delete($_TABLES['comments'], 'sid', addslashes($A['media_id']));
            DB_delete($_TABLES['mg_playback_options'], 'media_id', addslashes($A['media_id']));
        }
    }
    DB_delete($_TABLES['mg_media_albums'], 'album_id', intval($album_id));
    DB_delete($_TABLES['mg_albums'], 'album_id', intval($album_id));
    $feedname = sprintf($_MG_CONF['rss_feed_name'] . "%06d", $album_id);
    @unlink($_MG_CONF['path_html'] . 'rss/' . $feedname . '.rdf');
}


function MG_massDeleteAlbums($aid)
{
    global $_MG_CONF;

    $children = MG_getAlbumChildren($aid);
    $numItems = count($children);
    for ($x=0; $x < $numItems; $x++) {
        $i = $children[$x];
        if ($_POST['album'][$i] == 1) {
            MG_MassdeleteAlbum($children[$x]);
        } else {
            MG_massDeleteAlbums($children[$x]);
        }
    }
    echo COM_refresh($_MG_CONF['admin_url'] . 'index.php?msg=15');
}

/**
* Main
*/

$mode = '';
if (isset($_POST['mode'])) {
    $mode = COM_applyFilter($_POST['mode']);
} elseif (isset($_GET['mode'])) {
    $mode = COM_applyFilter($_GET['mode']);
}

//$T = new Template($_MG_CONF['template_path']);
$T = COM_newTemplate($_MG_CONF['template_path']);
$T->set_file('admin', 'administration.thtml');
$T->set_var(array(
    'site_admin_url' => $_CONF['site_admin_url'],
    'site_url'       => $_MG_CONF['site_url'],
    'lang_admin'     => $LANG_MG00['admin'],
    'status_msg'     => $LANG_MG01['mass_delete_help'],
));

if ($mode == $LANG_MG01['delete'] && !empty($LANG_MG01['delete'])) {
    $T->set_var('admin_body', MG_massDeleteAlbums(0));
} elseif ($mode == $LANG_MG01['cancel']) {
    echo COM_refresh($_MG_CONF['admin_url'] . 'index.php');
    exit;
} else {
    $T->set_var(array(
        'admin_body' => MG_massDelete(),
        'title'      => $LANG_MG01['batch_delete_albums'],
        'lang_help'  => '<img src="' . MG_getImageFile('button_help.png') . '" border="0" alt="?"' .XHTML. '>',
        'help_url'   => $_MG_CONF['site_url'] . '/docs/usage.html#Batch_Delete_Albums',
    ));
}

$display = COM_startBlock($LANG_MG00['admin'], '', COM_getBlockTemplate('_admin_block', 'header'));
$display .= MG_showAdminMenu('batch_sessions');
$display .= $T->finish($T->parse('output', 'admin'));
$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
$display = COM_createHTMLDocument($display);

COM_output($display);
?>