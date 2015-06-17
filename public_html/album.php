<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | album.php                                                                |
// |                                                                          |
// | Displays the contents of a MG album                                      |
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

if (COM_isAnonUser() && $_MG_CONF['loginrequired'] == 1) {
    $display = SEC_loginRequiredForm();
    $display = MG_createHTMLDocument($display);
    COM_output($display);
    exit;
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/classAlbum.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/classMedia.php';

// construct the adminbox
function MG_buildAdminbox(&$album, &$root_album, &$T)
{
    global $_TABLES, $_MG_CONF, $_USER, $LANG_MG01, $LANG_MG03;

    $_MG_USERPREFS = MG_getUserPrefs();

    $uploadMenu = 0;
    $adminMenu  = 0;
    if ($root_album->owner_id) {
        $uploadMenu = 1;
        $adminMenu  = 1;
    } else if ($album->access == 3) {
        $uploadMenu = 1;
        $adminMenu  = 1;
        if ($_MG_CONF['member_albums']) {
            if ($_MG_USERPREFS['active'] != 1) {
                $uploadMenu = 0;
                $adminMenu  = 0;
            } else {
                $uploadMenu = 1;
                $adminMenu  = 1;
            }
        }
    } else if ($album->member_uploads == 1 &&
               isset($_USER['uid']) && $_USER['uid'] >= 2) {
        $uploadMenu = 1;
        $adminMenu  = 0;
    }

    $admin_box_option = '';
    if ($uploadMenu == 1) {
        $admin_box_option .= MG_options(array(
            'current' => '',
            'values'  => array(
                'upload' => $LANG_MG01['add_media']
            )
        ));
    }
    if ($adminMenu == 1) {
        $admin_box_option .= MG_options(array(
            'current' => '',
            'values'  => array(
                'edit'       => $LANG_MG01['edit_album'],
                'create'     => $LANG_MG01['create_album'],
                'staticsort' => $LANG_MG01['static_sort_media'],
                'media'      => $LANG_MG01['manage_media'],
                'resize'     => $LANG_MG01['resize_display'],
                'rebuild'    => $LANG_MG01['rebuild_thumb'],
            )
        ));
    } else if ($_MG_CONF['member_albums'] == 1 &&
               !empty($_USER['username']) &&
               $_MG_CONF['member_create_new'] == 1 &&
               $_MG_USERPREFS['active'] == 1 &&
               $album->id == $_MG_CONF['member_album_root']) {
        $admin_box_option .= MG_options(array(
            'current' => '',
            'values'  => array(
                'upload' => $LANG_MG01['create_album']
            )
        ));

        $adminMenu = 1;
    }

    $admin_box = '';
    if ($uploadMenu == 1 || $adminMenu == 1) {
        $action = $_MG_CONF['site_url'] . '/admin.php';
        $admin_box = '<form name="adminbox" id="adminbox" action="' . $action . '" method="get" class="uk-form"><div>' . LB;
        $admin_box .= '<input type="hidden" name="album_id" value="' . $album->id . '"' . XHTML . '>' . LB;
        $admin_box .= '<select name="mode" onchange="forms[\'adminbox\'].submit()">' . LB;
        $admin_box .= '<option label="Options" value="">' . $LANG_MG01['options'] .'</option>' . LB;
        $admin_box .= $admin_box_option;
        $admin_box .= '</select>' . LB;
        $admin_box .= '<input type="submit" value="' . $LANG_MG03['go'] . '"' . XHTML . '>' . LB;
        $admin_box .= '</div></form>' . LB;
    }

    $edit_album = '';
    if ($adminMenu == 1) {
        $url_edit = $_MG_CONF['site_url'] . '/admin.php?album_id=' . $album->id . '&amp;mode=edit';
        $lang_edit = $LANG_MG01['edit'];
        $edit_album = '<a href="' . $url_edit . '"' . '>' . $lang_edit . '</a>';
    }

    $T->set_var(array(
        'select_adminbox' => $admin_box,
        'url_edit'        => $url_edit,
        'lang_edit'       => $lang_edit,
        'edit_album'      => $edit_album
    ));
}

// construct the sortbox
function MG_buildSortbox($album_id, $sortOrder, $page)
{
    global $_MG_CONF, $LANG_MG03;

    $action = $_MG_CONF['site_url'] . '/album.php';
    $retval = '<form name="sortbox" id="sortbox" action="' . $action . '" method="get" class="uk-form"><div>' . LB;
    $retval .= '<input type="hidden" name="aid" value="' . $album_id . '"' . XHTML . '>' . LB;
    $retval .= '<input type="hidden" name="page" value="' . $page . '"' . XHTML . '>' . LB;
    $retval .= $LANG_MG03['sort_by'] . ':&nbsp;'
             . '<select name="sort" onchange="forms[\'sortbox\'].submit()">' . LB;
    $retval .= MG_options(array(
        'current' => $sortOrder,
        'values'  => array(
            '0'  => $LANG_MG03['sort_default'],
            '1'  => $LANG_MG03['sort_default_asc'],
            '2'  => $LANG_MG03['sort_upload'],
            '3'  => $LANG_MG03['sort_upload_asc'],
            '4'  => $LANG_MG03['sort_capture'],
            '5'  => $LANG_MG03['sort_capture_asc'],
            '6'  => $LANG_MG03['sort_rating'],
            '7'  => $LANG_MG03['sort_rating_asc'],
            '8'  => $LANG_MG03['sort_views'],
            '9'  => $LANG_MG03['sort_views_asc'],
            '10' => $LANG_MG03['sort_alpha'],
            '11' => $LANG_MG03['sort_alpha_asc'],
        )
    ));
    $retval .= '</select>' . LB;
    $retval .= '<input type="submit" value="' . $LANG_MG03['go'] . '"' . XHTML . '>' . LB;
    $retval .= '</div></form>' . LB;

    return $retval;
}

/*
* Main
*/

$album_id  = isset($_GET['aid'])  ? COM_applyFilter($_GET['aid'],  true) : 0;
$page      = isset($_GET['page']) ? COM_applyFilter($_GET['page'], true) : 0;
$sortOrder = isset($_GET['sort']) ? COM_applyFilter($_GET['sort'], true) : 0;
$media_id  = isset($_GET['s'])    ? COM_applyFilter($_GET['s'])          : '';

if ($album_id == 0) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $_MG_CONF['site_url'] . '/index.php');
    exit;
}

$_MG_USERPREFS = MG_getUserPrefs();

$root_album = new mgAlbum(0);         // root album
$album      = new mgAlbum($album_id); // current album

$columns_per_page = ($album->display_columns == 0) ? $_MG_CONF['ad_display_columns'] : $album->display_columns;
$rows_per_page    = ($album->display_rows    == 0) ? $_MG_CONF['ad_display_rows']    : $album->display_rows;
if (isset($_MG_USERPREFS['display_rows']) && $_MG_USERPREFS['display_rows'] > 0) {
    $rows_per_page = $_MG_USERPREFS['display_rows'];
}
if (isset($_MG_USERPREFS['display_columns'] ) && $_MG_USERPREFS['display_columns'] > 0) {
    $columns_per_page = $_MG_USERPREFS['display_columns'];
}
$media_per_page = $columns_per_page * $rows_per_page;

if ($page != 0) {
    $page = $page - 1;
} else if ($media_id != 0) {
    $sql = MG_buildMediaSql(array(
        'album_id'  => $album_id,
        'fields'    => array('media_id'),
        'sortorder' => $sortOrder
    ));
    $result = DB_query($sql);
    $mediaOffset = 0;
    while ($row = DB_fetchArray($result)) {
        if ($media_id == $row['media_id']) break;
        $mediaOffset++;
    }
    if ($album->albums_first) {
        $childCount = $album->getChildCount();
        $page = intval(($mediaOffset + $childCount) / $media_per_page);
    } else {
        $page = intval($mediaOffset / $media_per_page);
    }
}

$errorMessage = '';
if (!isset($album->id)) {
    $errorMessage = $LANG_MG02['albumaccessdeny'];
} else if ($album->access == 0 || ($album->hidden == 1 && $album->access != 3)) {
    $errorMessage = $LANG_MG02['albumaccessdeny'];
} else {
    $aOffset = $album->getOffset();
    if ($aOffset == -1) {
        $errorMessage = $LANG_MG02['albumaccessdeny'];
    }
}
if ($errorMessage != '') {
    COM_errorLog("Media Gallery Error - User attempted to view an album that does not exist.");
    $display .= COM_showMessageText($errorMessage);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
    exit;
}

if ($_MG_CONF['usage_tracking']) {
    MG_updateUsage('album_view', $album->title, '', 0);
}

// update views counter....

if (!$root_album->owner_id && $page == 0) {
    $album_views = $album->views + 1;
    DB_change($_TABLES['mg_albums'], 'album_views', intval($album_views), 'album_id', intval($album_id));
}

// initialize variables

$begin = $media_per_page * $page;
$end   = $media_per_page;

$total_media = 0;
$MG_media = array();

if ($album->albums_first == 1) {
    $children = $album->getChildrenVisible();
    $cCount = count($children);

    for ($i=$begin; $i < $begin + $end; $i++) {
        if ($i >= $cCount) continue;
        $MG_media[] = array(
            'type' => 0, // A sub album
            'obj'  => $children[$i]);
        $total_media++;
    }

    $begin = $begin - $cCount;
    if ($begin < 0) $begin = 0;
    $end = $end - $total_media;
} else {
    $cCount = $album->getChildcount();
}

$sql = MG_buildMediaSql(array(
    'album_id'  => $album_id,
    'sortorder' => $sortOrder,
    'offset'    => $begin,
    'limit'     => $end
));
$result = DB_query($sql);
$mediaRows = 0;
while ($row = DB_fetchArray($result)) {
    $MG_media[] = array(
        'type' => 1, // regular media type
        'obj'  => new Media($row, $album_id));
    $total_media++;
    $mediaRows++;
}

if ($album->albums_first == 0) {
    if (($begin + $mediaRows) >= $album->media_count) {
        $startingPoint = $begin - $album->media_count;
        if ($startingPoint < 0) {
            $startingPoint = 0;
        }
        $numToProcess = $end - $mediaRows;

        $children = $album->getChildrenVisible();

        $endPoint = $startingPoint + $numToProcess;
        if ($endPoint > count($children)) {
            $endPoint = count($children);
        }

        for ($i=$startingPoint; $i < $endPoint; $i++) {
            $MG_media[] = array(
                'type' => 0, // A sub album
                'obj'  => $children[$i]);
            $total_media++;
        }
    }
}

$total_items_in_album = $album->media_count + $cCount;
$total_pages = ceil($total_items_in_album / $media_per_page);

if ($page >= $total_pages) {
    $page = $total_pages - 1;
}

$start = $page * $media_per_page;

$current_print_page = floor($start / $media_per_page) + 1;
if ($current_print_page == 0) $current_print_page = 1;

$total_print_pages = $total_pages;
if ($total_print_pages == 0) $total_print_pages = 1;

$aPage = 1;
if ($aOffset > 0) {
    $aPage = intval($aOffset / ($root_album->display_columns * $root_album->display_rows)) + 1;
}

$birdseed = MG_getBirdseed($album_id, 0, $sortOrder, $aPage);

$ownername = DB_getItem($_TABLES['users'], 'username', "uid=" . intval($album->owner_id));
$album_last_update = MG_getUserDateTimeFormat($album->last_update);
$pagination = COM_printPageNavigation($_MG_CONF['site_url'] . '/album.php?aid=' . $album_id
                                    . '&amp;sort=' . $sortOrder, $page + 1, $total_pages);

$rsslink = '';
if ($album->enable_rss) {
    $rssfeedname = sprintf($_MG_CONF['rss_feed_name'] . "%06d", $album_id);
    $rsslink = COM_createLink(COM_createImage(MG_getImageFile('feed.png'), '', array('class' => 'mg_rssimg')),
                              MG_getFeedUrl($rssfeedname . '.rss'),
                              array('type' => 'application/rss+xml'));
}

$T = COM_newTemplate(MG_getTemplatePath_byName($album->skin));
$T->set_file('page', 'album_page.thtml');
$T->set_var(array(
    'site_url'           => $_MG_CONF['site_url'],
    'birdseed'           => $birdseed,
    'album_title'        => PLG_replaceTags($album->title),
    'table_columns'      => $columns_per_page,
    'table_column_width' => intval(100 / $columns_per_page) . '%',
    'top_pagination'     => $pagination,
    'bottom_pagination'  => $pagination,
    'page_number'        => sprintf("%s %d %s %d", $LANG_MG03['page'], $current_print_page, $LANG_MG03['of'], $total_print_pages),
    'jumpbox'            => MG_buildAlbumJumpbox($root_album, $album_id, 1, -1),
    'album_id'           => $album_id,
    'album_description'  => ($album->display_album_desc ? PLG_replaceTags($album->description) : ''),
    'album_id_display'   => ($root_album->owner_id || $_MG_CONF['enable_media_id'] == 1 ? $LANG_MG03['album_id_display'] . $album_id : ''),
    'select_sortbox'     => ($album->enable_sort == 1 ? MG_buildSortbox($album_id, $sortOrder, $page) : ''),
    'album_last_update'  => $album_last_update[0],
    'album_owner'        => $ownername,
    'media_count'        => $album->getMediaCount(),
    'lang_search'        => $LANG_MG01['search'],
    'rsslink'            => $rsslink,
    'list_title'         => $LANG_MG03['list_title'],
    'list_desc'          => $LANG_MG03['list_desc'],
    'list_size'          => $LANG_MG03['list_size'],
    'list_user'          => $LANG_MG03['list_user'],
    'list_updated'       => $LANG_MG03['list_updated'],
));
MG_buildAdminbox($album, $root_album, $T);
MG_buildSlideshow($album, $T, $sortOrder);

// completed setting header / footer vars, parse them

PLG_templateSetVars('mediagallery', $T);

//$T->parse('album_header', 'header');

// main processing of the album contents.

if ($total_media > 0) {
    $k = 0;
    $col = 0;
    $opt = array('sortOrder' => $sortOrder);
    $T->set_block('page', 'ImageColumn', 'IColumn');
    $T->set_block('page', 'ImageRow', 'IRow');
    for ($i = 0; $i < $media_per_page; $i += $columns_per_page) {

        $next_columns = $i + $columns_per_page;
        for ($j = $i; $j < $next_columns; $j++) {

            if ($j >= $total_media) {
                $T->parse('IRow', 'ImageRow', true);
                $T->set_var('IColumn', '');
                break 2;
            }

            if ($MG_media[$j]['type'] == 0) {  // a sub album
                $celldisplay = MG_albumThumbnail($MG_media[$j]['obj']);
            } else {                           // regular media type
                $celldisplay = $MG_media[$j]['obj']->displayThumb($opt);
                if ($MG_media[$j]['obj']->type == 1) {
                    $T->set_var('URL', MG_getFilePath('disp', $MG_media[$j]['obj']->filename, 'jpg'));
                }
            }

            $T->set_var('clear_float', '');
            if ($col == $columns_per_page) {
                $T->set_var('clear_float', ' clear:both;');
                $col = 0;
            }
            $T->set_var('CELL_DISPLAY_IMAGE', $celldisplay);
            $T->parse('IColumn', 'ImageColumn', true);
            $col++;
        }
        $T->parse('IRow', 'ImageRow', true);
        $T->set_var('IColumn', '');
    }
    $T->set_var('album_body', 1);
} else {
    $T->set_var('lang_no_image', $LANG_MG03['no_media_objects']);
}

MG_getThemePublicJSandCSS($album->skin);
MG_getCSS($album->image_skin);
if ($album->image_skin != $album->album_skin) {
    MG_getCSS($album->album_skin);
}
$display = $T->finish($T->parse('output', 'page'));
$display = MG_createHTMLDocument($display);

COM_output($display);
?>