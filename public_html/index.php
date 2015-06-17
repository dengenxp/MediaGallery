<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | index.php                                                                |
// |                                                                          |
// | Main interface to Media Gallery                                          |
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

if (!in_array('mediagallery', $_PLUGINS) ||
  $_MG_CONF['var_current_code'] !== true) {
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

// construct the adminbox
function MG_buildAdminbox(&$root_album)
{
    global $_TABLES, $_MG_CONF, $_USER, $LANG_MG01, $LANG_MG03;

    $options = '';
    if ($root_album->member_uploads || $root_album->access == 3) {
        $options .= '<option value="upload">' . $LANG_MG01['add_media'] . '</option>' . LB;
    }
    if ($root_album->owner_id) {
        $options .= '<option value="albumsort">'  . $LANG_MG01['sort_albums'] . '</option>' . LB;
        $options .= '<option value="globalattr">' . $LANG_MG01['globalattr'] . '</option>' . LB;
        $options .= '<option value="globalperm">' . $LANG_MG01['globalperm'] . '</option>' . LB;
        $options .= '<option value="wmmanage">' . $LANG_MG01['wm_management'] . '</option>' . LB;
        $options .= '<option value="create">' . $LANG_MG01['create_album'] . '</option>' . LB;
    } elseif ($root_album->access == 3) {
        $options .= '<option value="create">' . $LANG_MG01['create_album'] . '</option>' . LB;
    } elseif ($_MG_CONF['member_albums'] == 1 && $_MG_CONF['member_album_root'] == 0 && $_MG_CONF['member_create_new']) {
        $options .= '<option value="create">' . $LANG_MG01['create_album'] . '</option>' . LB;
    }
    if ($options == '') return '';

    $retval = '';
    $retval .= '<form name="adminbox" id="adminbox" action="' . $_MG_CONF['site_url'] . '/admin.php" method="get" class="uk-form"><div>' . LB;
    $retval .= '<select onchange="javascript:forms[\'adminbox\'].submit();" name="mode">' . LB;
    $retval .= '<option label="Options" value="">' . $LANG_MG01['options'] . '</option>' . LB;
    $retval .= $options;
    $retval .= '</select>' . LB;
    $retval .= '<input type="hidden" name="album_id" value="0"' . XHTML . '>' . LB;
    $retval .= '<input type="submit" value="' . $LANG_MG03['go'] . '"' . XHTML . '>' . LB;
    $retval .= '</div></form>' . LB;

    return $retval;
}

/*
* Main
*/

$album_id = 0;
$root_album = new mgAlbum(0); // root album

if ($root_album->access == 0 || ($root_album->hidden == 1 && $root_album->access != 3)) {
    COM_errorLog("Media Gallery Error - User attempted to view an album that does not exist.");
    $display = COM_showMessageText($LANG_MG02['albumaccessdeny']);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
    exit;
}

if ($_MG_CONF['usage_tracking']) {
    MG_updateUsage('MediaGallery', 'Main Menu', '', 0);
}

// initialize variables

$page = isset($_GET['page']) ? COM_applyFilter($_GET['page'], true) : 0;

$columns_per_page = $root_album->display_columns;
$rows_per_page    = $root_album->display_rows;
$media_per_page   = $columns_per_page * $rows_per_page;

if ($page != 0) {
    $page = $page - 1;
}

$begin = $media_per_page * $page;
$end   = $media_per_page;

$total_media = 0;
$MG_media = array();

$children = $root_album->getChildrenVisible();
$cCount = count($children);

$sub_album_id = array();
for ($i=$begin; $i < $begin + $end; $i++) {
    if ($i >= $cCount) continue;
    $sub_album_id[] = $children[$i];
    $total_media++;
}

$begin = $begin - $cCount;
if ($begin < 0) $begin = 0;
$end = $end - $total_media;

$total_items_in_album = $root_album->media_count + $cCount;
$total_pages = ceil($total_items_in_album / $media_per_page);

if ($page >= $total_pages) {
    $page = $total_pages - 1;
}

$start = $page * $media_per_page;

$current_print_page = floor($start / $media_per_page) + 1;
if ($current_print_page == 0) $current_print_page = 1;

$total_print_pages = $total_pages;
if ($total_print_pages == 0) $total_print_pages = 1;

$birdseed = MG_getBirdseed(0, 0, 0, $total_print_pages);

$ownername = DB_getItem($_TABLES['users'], 'username', "uid=" . intval($root_album->owner_id));
$album_last_update = MG_getUserDateTimeFormat($root_album->last_update);
$pagination = COM_printPageNavigation($_MG_CONF['site_url'] . '/index.php', $page + 1, $total_pages);

$rsslink = '';
if ($_MG_CONF['rss_full_enabled']) {
    $rsslink = COM_createLink(COM_createImage(MG_getImageFile('feed.png'), '', array('class' => 'mg_rssimg')),
                              MG_getFeedUrl($_MG_CONF['rss_feed_name'] . '.rss'),
                              array('type' => 'application/rss+xml'));
}

$T = COM_newTemplate(MG_getTemplatePath_byName($root_album->skin));
$T->set_file('page', 'album_page.thtml');
$T->set_var(array(
    'site_url'           => $_MG_CONF['site_url'],
    'birdseed'           => $birdseed,
    'album_title'        => PLG_replaceTags($root_album->title),
    'table_columns'      => $columns_per_page,
    'table_column_width' => intval(100 / $columns_per_page) . '%',
    'top_pagination'     => $pagination,
    'bottom_pagination'  => $pagination,
    'page_number'        => sprintf("%s %d %s %d", $LANG_MG03['page'], $current_print_page, $LANG_MG03['of'], $total_print_pages),
    'jumpbox'            => MG_buildAlbumJumpbox($root_album, $album_id, 1, -1),
    'album_id'           => $album_id,
    'album_description'  => ($root_album->display_album_desc ? PLG_replaceTags($root_album->description) : ''),
    'album_id_display'   => ($root_album->owner_id || $_MG_CONF['enable_media_id'] == 1 ? $LANG_MG03['album_id_display'] . $album_id : ''),
    'select_adminbox'    => (COM_isAnonUser() ? '' : MG_buildAdminbox($root_album)),
    'album_last_update'  => $album_last_update[0],
    'album_owner'        => $ownername,
    'media_count'        => $root_album->getMediaCount(),
    'lang_menulabel'     => $LANG_MG03['menulabel'],
    'lang_search'        => $LANG_MG01['search'],
    'rsslink'            => $rsslink,
    'list_title'         => $LANG_MG03['list_title'],
    'list_desc'          => $LANG_MG03['list_desc'],
    'list_size'          => $LANG_MG03['list_size'],
    'list_user'          => $LANG_MG03['list_user'],
    'list_updated'       => $LANG_MG03['list_updated'],
));

// completed setting header / footer vars, parse them

PLG_templateSetVars('mediagallery', $T);

// main processing of the album contents.

if ($total_media > 0) {
    $k = 0;
    $col = 0;
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

            $T->set_var('clear_float', '');
            if ($col == $columns_per_page) {
                $T->set_var('clear_float', ' clear:both;');
                $col = 0;
            }
            $T->set_var('CELL_DISPLAY_IMAGE', MG_albumThumbnail($sub_album_id[$j]));
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

$T->parse('output', 'page');
MG_getThemePublicJSandCSS($root_album->skin);
MG_getCSS($root_album->album_skin);

$display = $T->finish($T->get_var('output'));
$display = MG_createHTMLDocument($display);

COM_output($display);
?>