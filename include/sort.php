<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | sort.php                                                                 |
// |                                                                          |
// | Sort albums                                                              |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2015 by the following authors:                             |
// |                                                                          |
// | Yoshinori Tahara       taharaxp AT gmail DOT com                         |
// |                                                                          |
// | Based on the Media Gallery Plugin for glFusion CMS                       |
// | Copyright (C) 2002-2009 by the following authors:                        |
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

if (strpos(strtolower($_SERVER['PHP_SELF']), strtolower(basename(__FILE__))) !== false) {
    die('This file can not be used on its own!');
}

//require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';

/**
* sorts the albums into the proper order
*
* @param    int     parent  parent album id
* @return   int     true for success or false for failure
*
*/
function MG_reorderAlbum($parent = 0)
{
    global $_TABLES, $_CONF;

    $sql = "SELECT album_id, album_order FROM " . $_TABLES['mg_albums']
         . " WHERE album_parent=" . intval($parent)
         . " ORDER BY album_order ASC";
    $result = DB_query($sql);
    if (DB_error()) return false;
    $order = 10;
    while ($row = DB_fetchArray($result)) {
        DB_change($_TABLES['mg_albums'], 'album_order', $order, 'album_id', $row['album_id']);
        $order += 10;
    }
    return true;
}

/**
* sorts all albums starting at the $parent level
*
* @param    int     parent  parent album id
* @param    int     page number to start
* @return   string  HTML for list of albums
*
*/
function MG_sortAlbums($parent=0, $actionURL)
{
    global $_USER, $_CONF, $_TABLES, $_MG_CONF, $LANG_MG01;

    $retval = '';
    $retval .= COM_startBlock($LANG_MG01['sort_albums'], '',
                              COM_getBlockTemplate('_admin_block', 'header'));

    $T = COM_newTemplate(MG_getTemplatePath($parent));
    $T->set_file('admin', 'sortalbum.thtml');
    $T->set_var(array(
        'lang_new_album'       => $LANG_MG01['new_album'],
        'lang_upload_media'    => $LANG_MG01['upload_media'],
        'lang_ftp_media'       => $LANG_MG01['ftp_media'],
        'lang_usage_reports'   => $LANG_MG01['usage_reports'],
        'lang_configuration'   => $LANG_MG01['configuration'],
        'lang_media_queue'     => $LANG_MG01['media_queue'],
        'lang_admin_home'      => $LANG_MG01['admin_home'],
        'lang_album_name_desc' => $LANG_MG01['album_name_desc'],
        'lang_count'           => $LANG_MG01['count'],
        'lang_order'           => $LANG_MG01['order'],
        'lang_action'          => $LANG_MG01['action'],
        'lang_move_up'         => $LANG_MG01['move_up'],
        'lang_move_down'       => $LANG_MG01['move_down'],
        'lang_edit'            => $LANG_MG01['edit'],
        'lang_delete'          => $LANG_MG01['delete'],
        'lang_caption'         => $LANG_MG01['caption'],
        'lang_images'          => $LANG_MG01['images'],
        'lang_admin_main_help' => $LANG_MG01['admin_main_help'],
        'lang_parent_album'    => $LANG_MG01['parent_album'],
        'lang_save'            => $LANG_MG01['save'],
        'lang_cancel'          => $LANG_MG01['cancel'],
        's_form_action'        => $_MG_CONF['site_url'] . '/admin.php',
    ));

    $rowcounter = 1;

    $sql = "SELECT a.album_id, a.album_title as album_title, a.album_desc, a.album_order, "
         . "a.owner_id, a.group_id, a.perm_owner, a.perm_group, a.perm_members, a.perm_anon, "
         . "COUNT(ma.media_id) AS media_count, album_cover "
         . "FROM {$_TABLES['mg_albums']} AS a "
         . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma "
         . "ON a.album_id=ma.album_id WHERE album_parent=" . intval($parent)
         . " GROUP BY a.album_id ORDER BY a.album_order DESC";
    $result = DB_query($sql, 1);
    if (DB_error()) {
        COM_errorLog("Media Gallery Error - Unable to build album select list");
        $T->parse('output', 'admin');
        $retval .= $T->finish($T->get_var('output'));
        $retval .= 'There was an error in the SQL statement - Check the error.log';
        $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
        return $retval;
    }
    $nRows  = DB_numRows($result);

    $T->set_block('admin', 'AlbumRow', 'ARow');

    for ($i = 0; $i < $nRows; $i++) {
        $row = DB_fetchArray($result);

        $access = SEC_hasAccess($row['owner_id'], $row['group_id'], $row['perm_owner'],
                                $row['perm_group'], $row['perm_members'], $row['perm_anon']);

        if ($access != 3 && !SEC_hasRights('mediagallery.admin')) {    // only allow access to items that we can edit
            continue;
        }

        $subalbums = DB_count($_TABLES['mg_albums'], 'album_parent', $row['album_id']);

        $albumTitle = strip_tags(COM_stripslashes($row['album_title']));
        if ($subalbums) {
            $albumTitle .= ' - ' . COM_createLink($LANG_MG01['subalbums'] . ' (' . $subalbums . ')',
                $_MG_CONF['site_url'] . '/admin.php?mode=albumsort&amp;album_id=' . $row['album_id']);
        }

        $T->set_var(array(
            'row_class'   => ($rowcounter % 2) ? '1' : '2',
            'album_id'    => $row['album_id'],
            'album_title' => $albumTitle,
            'album_desc'  => COM_stripslashes($row['album_desc']),
            'media_count' => $row['media_count'],
            'album_order' => $row['album_order'],
        ));
        $T->parse('ARow', 'AlbumRow', true);
        $rowcounter++;
    }

    $parent_album = '-';
    if ($parent != 0) {
        $parent_album_title = DB_getItem($_TABLES['mg_albums'], 'album_title',  'album_id=' . intval($parent));
        $parent_parent      = DB_getItem($_TABLES['mg_albums'], 'album_parent', 'album_id=' . intval($parent));
        $parent_album = COM_createLink(strip_tags($parent_album_title),
                        $_MG_CONF['site_url'] . '/admin.php?mode=albumsort&amp;album_id=' . $parent_parent);
    }
    $T->set_var('lang_parent_album', $LANG_MG01['parent_album']);
    $T->set_var('parent_album', $parent_album);
    $T->set_var('parent_id', $parent);

    $mqueue_count = DB_count($_TABLES['mg_mediaqueue']);
    $T->set_var('mqueue_count', $mqueue_count);

    $retval .= $T->finish($T->parse('output', 'admin'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $retval;
}


/**
* saves user album list in specified order
*
* @param    int     album_id    parent album id to begin sort
* @return   redirects to index page
*
*/
function MG_saveAlbumSort($album_id)
{
    global $_USER, $_CONF, $_TABLES, $_MG_CONF, $LANG_MG00;

    // check permissions...

    if (!SEC_hasRights('mediagallery.admin')) {
        COM_errorLog("MediaGallery: Someone has tried to illegally sort albums in Media Gallery. "
                   . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    $parent = COM_applyFilter($_POST['parent_id'], true);

    $numItems = count($_POST['aid']);

    for ($i=0; $i < $numItems; $i++) {
        $album[$i]['aid'] = $_POST['aid'][$i];
        $album[$i]['seq'] = $_POST['seq'][$i];
    }

    for ($i=0; $i < $numItems; $i++) {
        DB_change($_TABLES['mg_albums'], 'album_order', intval($album[$i]['seq']),
                                         'album_id',    intval($album[$i]['aid']));
        if (DB_error()) {
            COM_errorLog("MediaGallery: Error updating album sort order MG_saveAlbumSort()");
        }
    }

    MG_reorderAlbum($parent);

    echo COM_refresh($_MG_CONF['site_url'] . '/admin.php?album_id=0&mode=albumsort');
    exit;
}

function MG_staticSortMedia($album_id, $actionURL='')
{
    global $_USER, $_CONF, $_TABLES, $_MG_CONF, $LANG_MG00, $LANG_MG01, $LANG_MG03;

    $album_title = DB_getItem($_TABLES['mg_albums'], 'album_title', 'album_id=' . intval($album_id));

    $retval = '';
    $retval .= COM_startBlock($LANG_MG01['static_media_sort'] . ' - ' . strip_tags($album_title), '',
                              COM_getBlockTemplate('_admin_block', 'header'));

    $T = COM_newTemplate( MG_getTemplatePath($album_id) );
    $T->set_file('admin', 'staticsort.thtml');
    $T->set_var('album_id', $album_id);

    // check permissions...

    $album = new mgAlbum($album_id);
    if ($album->access != 3) {
        COM_errorLog("Someone has tried to illegally sort albums in Media Gallery. "
                   . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    // make sure there is something here to sort...

    $sql = MG_buildMediaSql(array(
        'album_id' => $album_id,
        'limit'    => 1
    ));
    $result = DB_query($sql);
    $nRows = DB_numRows($result);

    if ($nRows == 0) {
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    $T->set_var(array(
        'album_id'                => $album_id,
        's_form_action'           => $actionURL,
        'lang_save'               => $LANG_MG01['save'],
        'lang_cancel'             => $LANG_MG01['cancel'],
        'lang_static_sort_help'   => $LANG_MG01['static_sort_help'],
        'lang_media_capture_time' => $LANG_MG01['media_capture_time'],
        'lang_media_upload_time'  => $LANG_MG01['media_upload_time'],
        'lang_media_title'        => $LANG_MG01['mod_mediatitle'],
        'lang_media_filename'     => $LANG_MG01['media_original_filename'],
        'lang_rating'             => $LANG_MG03['rating'],
        'lang_ascending'          => $LANG_MG01['ascending'],
        'lang_descending'         => $LANG_MG01['descending'],
        'lang_sort_options'       => $LANG_MG01['sort_options'],
        'lang_order_options'      => $LANG_MG01['order_options'],
    ));

    $retval .= $T->finish($T->parse('output', 'admin'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $retval;
}

function MG_saveStaticSortMedia($album_id, $actionURL='')
{
    global $_USER, $_CONF, $_TABLES, $_MG_CONF, $LANG_MG00, $LANG_MG01, $LANG_MG03;

    // check permissions...

    if ($album_id == 0) {
        COM_errorLog("Media Gallery: Invalid album_id passed to sort");
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    //
    // -- get the sort options
    //

    $sortby = COM_applyFilter($_POST['sortyby'], true);
    $sorder = COM_applyFilter($_POST['sortorder'], true);

    switch ($sortby) {
        case '0' :  // media_time
            $sortOrder = 4;
            break;
        case '1' :  // media_upload_time
            $sortOrder = 2;
            break;
        case '2' : // media title
            $sortOrder = 10;
            break;
        case '3' : // media original filename
            $sortOrder = 12;
            break;
        case '4' : // rating
            $sortOrder = 6;
            break;
        default :
            $sortOrder = 4;
            break;
    }

    switch ($sorder) {
        case '0' :  // ascending
            break;
        case '1' :  // descending
        default :
            $sortOrder++;
            break;
    }

    $sql = MG_buildMediaSql(array(
        'album_id'  => $album_id,
        'fields'    => array('media_id'),
        'sortorder' => $sortOrder
    ));

    $result = DB_query($sql);
    $order = 10;
    while ($row = DB_fetchArray($result)) {
        DB_change($_TABLES['mg_media_albums'], 'media_order', $order, 'media_id', $row['media_id']);
        $order += 10;
    }
    echo COM_refresh($actionURL);
    exit;
}


function MG_reorderMedia($album_id)
{
    global $_TABLES, $_CONF;

    $sql = "SELECT media_id FROM " . $_TABLES['mg_media_albums']
         . " WHERE album_id = " . intval($album_id)
         . " ORDER BY media_order ASC";
    $result = DB_query($sql);
    $order = 10;
    while ($row = DB_fetchArray($result)) {
        DB_change($_TABLES['mg_media_albums'], 'media_order', $order, 'media_id', $row['media_id']);
        $order += 10;
    }
}

function MG_SortMedia($album_id)
{
    global $_TABLES;

    //
    // -- get the sort options
    //
    $id = intval($album_id);
    $album_sort_order = DB_getItem($_TABLES['mg_albums'], 'album_sort_order', "album_id=" . $id);

    if ($album_sort_order == 0) return;

    switch ($album_sort_order) {
        case '1' :  // media_time
            $sql_sort_by = " ORDER BY m.media_time DESC";
            break;
        case '2' :  // media_time
            $sql_sort_by = " ORDER BY m.media_time ASC";
            break;
        case '3' :  // media_upload_time
            $sql_sort_by = " ORDER BY m.media_upload_time DESC";
            break;
        case '4' :  // media_upload_time
            $sql_sort_by = " ORDER BY m.media_upload_time ASC";
            break;
        case '5' :  // title
            $sql_sort_by = " ORDER BY m.media_title ASC";
            break;
        case '6' :  // title
            $sql_sort_by = " ORDER BY m.media_title DESC";
            break;
//        case '7' :  // title
//            $sql_sort_by = " ORDER BY m.media_rating ASC";
//            break;
//        case '8' :  // title
//            $sql_sort_by = " ORDER BY m.media_rating DESC";
//            break;
    }

    $sql = "SELECT m.media_id FROM {$_TABLES['mg_media_albums']} AS ma "
         . "LEFT JOIN {$_TABLES['mg_media']} AS m ON m.media_id = ma.media_id "
         . "WHERE ma.album_id=" . $id . $sql_sort_by;
    $result = DB_query($sql);
    $order = 10;
    while ($row = DB_fetchArray($result)) {
        DB_change($_TABLES['mg_media_albums'], 'media_order', $order, 'media_id', $row['media_id']);
        $order += 10;
    }

    return;
}
?>