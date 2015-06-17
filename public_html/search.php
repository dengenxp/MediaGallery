<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | search.php                                                               |
// |                                                                          |
// | Media Gallery search implementation                                      |
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

function MG_buildSearchBox(&$T, $searchinfo=array())
{
    global $_CONF, $_MG_CONF, $_TABLES, $LANG_MG01, $LANG_MG03;

    $cat_select = '<select name="cat_id">';
    $select_cat_id = ($searchinfo['cat_id'] == '') ? UC_SELECTED : '';
    $cat_select .= '<option value="" ' . $select_cat_id . '>'
                 . $LANG_MG03['all_categories'] . '</option>';
    $result = DB_query("SELECT cat_id, cat_name FROM {$_TABLES['mg_category']} ORDER BY cat_id ASC");
    while ($row = DB_fetchArray($result)) {
        $select_cat_id = ($searchinfo['cat_id'] == $row['cat_id']) ? UC_SELECTED : '';
        $cat_select .= '<option value="' . $row['cat_id'] . '" ' . $select_cat_id . '>'
                     . $row['cat_name'] . '</option>';
    }
    $cat_select .= '</select>';

    $keytype = MG_optionlist(array(
        'name'    => 'keyType',
        'current' => $searchinfo['keytype'],
        'values'  => array(
            'phrase' => $LANG_MG03['exact_phrase'],
            'all'    => $LANG_MG03['all'],
            'any'    => $LANG_MG03['any'],
        ),
    ));

    $swhere = MG_optionlist(array(
        'name'    => 'swhere',
        'current' => $searchinfo['swhere'],
        'values'  => array(
            '0' => $LANG_MG03['title_desc_keywords'],
            '1' => $LANG_MG03['keywords_only'],
            '2' => $LANG_MG03['title_desc_only'],
            '3' => $LANG_MG01['artist'],
            '4' => $LANG_MG01['music_album'],
            '5' => $LANG_MG01['genre'],
        ),
    ));

    $nresults = MG_optionlist(array(
        'name'    => 'numresults',
        'current' => $searchinfo['numresults'],
        'values'  => array(
            '10' => '10',
            '20' => '20',
            '30' => '30',
            '40' => '40',
            '50' => '50',
        ),
    ));

    $userselect = '<select name="uid">';
    $select_uid = ($searchinfo['uid'] == '0') ? UC_SELECTED : '';
    $userselect .= '<option value="0" ' . $select_uid . '>'
                 . $LANG_MG01['all_users'] . '</option>';
    $result = DB_query("SELECT uid FROM {$_TABLES['users']} WHERE uid > 1 ORDER BY username");
    while ($U = DB_fetchArray($result)) {
        $select_uid = ($searchinfo['uid'] == $U['uid']) ? UC_SELECTED : '';
        $userselect .= '<option value="' . $U['uid'] . '" ' . $select_uid . '>'
                     . COM_getDisplayName($U['uid']) . '</option>';
    }
    $userselect .= '</select>';

    $T->set_var(array(
        's_form_action'       => $_MG_CONF['site_url'] . '/search.php',
        'mode'                => 'search',
        'action'              => '',
        'cat_select'          => $cat_select,
        'keytype_select'      => $keytype,
        'swhere_select'       => $swhere,
        'nresults_select'     => $nresults,
        'user_select'         => $userselect,
        'lang_search_title'   => $LANG_MG03['advanced_search'],
        'lang_search_query'   => $LANG_MG03['search_query'],
        'lang_search_help'    => $LANG_MG03['search_help'],
        'lang_options'        => $LANG_MG03['options'],
        'lang_keywords'       => $LANG_MG03['keywords'],
        'lang_category'       => $LANG_MG03['category'],
        'lang_all_fields'     => $LANG_MG03['all_fields'],
        'lang_keyword_only'   => $LANG_MG03['keywords_only'],
        'lang_return_results' => $LANG_MG03['return_results'],
        'lang_search_for'     => $LANG_MG03['search_for'],
        'lang_search_in'      => $LANG_MG03['search_in'],
        'lang_results'        => $LANG_MG03['results'],
        'lang_per_page'       => $LANG_MG03['per_page'],
        'lang_search'         => $LANG_MG01['search'],
        'lang_cancel'         => $LANG_MG01['cancel'],
        'lang_user'           => $LANG_MG01['select_user'],
    ));
}

/**
* this searches for pages matching the user query and returns an array of
* for the header and table rows back to search.php where it will be formated and
* printed
*
* @query            string          Keywords user is looking for
* @datestart        date/time       Start date to get results for
* @dateend          date/time       End date to get results for
* @topic            string          The topic they were searching in
* @type             string          Type of items they are searching
* @author           string          Get all results by this author
*
*/
function MG_search($id, $page, $searchinfo='')
{
    global $_USER, $_TABLES, $_CONF, $_MG_CONF, $LANG_MG00, $LANG_MG01, $LANG_MG03;

    $columns_per_page = $_MG_CONF['search_columns'];
    $rows_per_page    = $_MG_CONF['search_rows'];
    if (!empty($searchinfo['numresults'])) {
        $rows_per_page = intval($searchinfo['numresults'] / $columns_per_page);
    }
    $media_per_page   = $columns_per_page * $rows_per_page;

    $current_print_page = $page;

//    $alertmsg = '<div class="pluginAlert">' . $LANG_MG03['no_search_found'] . '</div>';

    // pull the query from the search database...

    $result = DB_query("SELECT * FROM {$_TABLES['mg_sort']} WHERE sort_id='" . addslashes($id) . "'");
//    $nrows  = DB_numRows($result);
//    if ($nrows < 1) {
//        return $alertmsg;
//    }
    $S = DB_fetchArray($result);

    if (!isset($_USER['uid']) || $_USER['uid'] < 2) {
        $sort_user = 1;
    } else {
        $sort_user = $_USER['uid'];
    }
//    if ($sort_user != $S['sort_user'] && $S['sort_user'] != 1) {
//        return $alertmsg;
//    }

    $page  = $page - 1;
    $begin = $media_per_page * $page;
    $end   = $media_per_page;

    $root_album_owner_id = SEC_hasRights('mediagallery.admin');
    $permsql = COM_getPermSQL('AND', $sort_user, 2, 'a');
    $hiddensql = !$root_album_owner_id ? "AND a.hidden=0 " : '';

    $sql = "SELECT DISTINCT count(*) AS c FROM {$_TABLES['mg_media']} AS m, "
         . $_TABLES['mg_media_albums'] . " AS ma, "
         . $_TABLES['mg_albums'] . " AS a "
         . $S['sort_query']
         . " AND m.media_id=ma.media_id AND ma.album_id=a.album_id "
         . $hiddensql
         . $permsql;
    $result = DB_query($sql);
    $row = DB_fetchArray($result);
    $total_media = $row['c'];

//    if ($total_media < 1) {
//        return $alertmsg;
//    }

    $sql = "SELECT DISTINCT m.*,a.album_id FROM {$_TABLES['mg_media']} AS m, "
         . $_TABLES['mg_media_albums'] . " AS ma, "
         . $_TABLES['mg_albums'] . " AS a "
         . $S['sort_query']
         . " AND m.media_id=ma.media_id AND ma.album_id=a.album_id "
         . $hiddensql
         . $permsql
         . " ORDER BY m.media_time DESC"
         . " LIMIT " . $begin . "," . intval($begin + $end);
    $result = DB_query($sql);

    $media_array = array();
    while ($row = DB_fetchArray($result)) {
        $media_array[] = $row;
    }

    $total_print_pages = ceil($total_media / $media_per_page);

    $pagination = COM_printPageNavigation($_MG_CONF['site_url'] . '/search.php?id=' . $id, $page + 1, $total_print_pages, 'page=');

    $page_number = sprintf("%s %d %s %d", $LANG_MG03['page'], $current_print_page, $LANG_MG03['of'], $total_print_pages);

    $return_url = ($S['referer'] == '') ? $_MG_CONF['site_url'] : htmlentities($S['referer'], ENT_QUOTES, COM_getCharset());

    // new stuff
    $T = COM_newTemplate(MG_getTemplatePath_byName());
    $T->set_file('page', 'search_page.thtml');
    $T->set_var(array(
        'site_url'             => $_MG_CONF['site_url'],
        'table_columns'        => $columns_per_page,
        'table_column_width'   => intval(100 / $columns_per_page) . '%',
        'top_pagination'       => $pagination,
        'bottom_pagination'    => $pagination,
        'page_number'          => $page_number,
        'lang_search_results'  => $LANG_MG03['search_results'],
        'lang_return_to_index' => $LANG_MG03['return_to_index'],
        'return_url'           => $return_url,
        'search_keywords'      => ($searchinfo['keywords'] == '*') ? '*' : $S['keywords'],
        'lang_search'          => $LANG_MG01['search'],
    ));

    MG_buildSearchBox($T, $searchinfo);

    $howmany = $total_media - ($page * $media_per_page);
    if ($howmany > $total_media) {
        $howmany = $total_media;
    }

    if ($howmany > 0) {
        $k = 0;
        $col = 0;
        $opt = array('sortOrder' => 0, 'searchmode' => 1);
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

                if ($j + $begin >= $total_media) continue;
                
                $media = new Media($media_array[$j], $media_array[$j]['album_id']);
                $celldisplay = $media->displayThumb($opt);
                if ($media->type == 1) {
                    $PhotoURL = MG_getFileUrl('disp', $media->filename);
                    $T->set_var('URL', $PhotoURL);
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

    return $T->finish($T->parse('output', 'page'));
}


function MG_showSearchForm($searchinfo)
{
    global $_MG_CONF, $LANG_MG01, $LANG_MG03;

    $columns_per_page = $_MG_CONF['search_columns'];
    $page_number = sprintf("%s %d %s %d", $LANG_MG03['page'], 1, $LANG_MG03['of'], 1);
    $T = COM_newTemplate(MG_getTemplatePath_byName());
    $T->set_file('page', 'search_page.thtml');
    $T->set_var(array(
        'site_url'             => $_MG_CONF['site_url'],
        'table_columns'        => $columns_per_page,
        'table_column_width'   => intval(100 / $columns_per_page) . '%',
        'top_pagination'       => '',
        'bottom_pagination'    => '',
        'page_number'          => $page_number,
        'lang_search_results'  => $LANG_MG03['search_results'],
        'lang_return_to_index' => $LANG_MG03['return_to_index'],
        'return_url'           => $_MG_CONF['site_url'],
        'search_keywords'      => '',
        'lang_search'          => $LANG_MG01['search'],
    ));
    MG_buildSearchBox($T, $searchinfo);
    $T->set_var('lang_no_image', '');
    return $T->finish($T->parse('output', 'page'));
}

/*
* Main Function
*/

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

$root_album = new mgAlbum(0);

$skin = !empty($_MG_CONF['search_album_skin']) ? $_MG_CONF['search_album_skin'] : $root_album->skin;
MG_getThemePublicJSandCSS($skin);
$frame_skin = !empty($_MG_CONF['search_frame_skin']) ? $_MG_CONF['search_frame_skin'] : $root_album->image_skin;
MG_getCSS($frame_skin);
$display = '';

$mode          = isset($_REQUEST['mode'])       ? COM_applyFilter($_REQUEST['mode'])            : '';
$keywords      = isset($_REQUEST['keywords'])   ? COM_applyFilter($_REQUEST['keywords'])        : '';
$stype         = isset($_REQUEST['keyType'])    ? COM_applyFilter($_REQUEST['keyType'])         : 'phrase';
$category      = isset($_REQUEST['cat_id'])     ? COM_applyFilter($_REQUEST['cat_id'])          : 0;
$skeywords     = isset($_REQUEST['swhere'])     ? COM_applyFilter($_REQUEST['swhere'])          : 1;
$numresults    = isset($_REQUEST['numresults']) ? COM_applyFilter($_REQUEST['numresults'],true) : 10;
$users         = isset($_REQUEST['uid'])        ? COM_applyFilter($_REQUEST['uid'],true)        : 0;
$sortyby       = 'title'; // no use?
$sortdirection = 'DESC';  // no use?
$searchinfo = array(
    'keywords'   => $keywords,
    'keytype'    => $stype,
    'cat_id'     => $category,
    'swhere'     => $skeywords,
    'numresults' => $numresults,
    'uid'        => $users,
);

if ($mode == $LANG_MG01['search'] || $mode == 'search') {

    $f_all = false;
    if ($keywords == '*') {
        $keywords = '';
        $f_all = true;
    }
    $keywords = strip_tags(COM_stripslashes($keywords));

    // build the query and put into our database...

    $sqltmp = " WHERE 1=1 ";
    $keywords_db = addslashes($keywords);
    if ($stype == 'phrase') { // search phrase
        switch ($skeywords) {
            case 0 :
                $sqltmp .= "AND (m.media_title LIKE '%$keywords_db%' OR m.media_desc LIKE '%$keywords%' OR m.media_keywords LIKE '%$keywords%' OR m.artist LIKE '%$keywords%' OR m.album LIKE '%$keywords%' OR m.genre LIKE '%$keywords%')";
                break;
            case 1 :
                $sqltmp .= "AND (m.media_keywords LIKE '%$keywords_db%')";
                break;
            case 2 :
                $sqltmp .= "AND (m.media_title LIKE '%$keywords_db%' OR m.media_desc LIKE '%$keywords%')";
                break;
            case 3 :
                $sqltmp .= "AND (m.artist LIKE '%$keywords_db%')";
                break;
            case 4 :
                $sqltmp .= "AND (m.album LIKE '%$keywords_db%')";
                break;
            case 5 :
                $sqltmp .= "AND (m.genre LIKE '%$keywords_db%')";
                break;
        }
    } else if ($stype == 'any') {
        $sqltmp .= ' AND ';
        $tmp = '';
        $mywords = explode(' ', $keywords);
        foreach ($mywords AS $mysearchitem) {
            $mysearchitem = addslashes($mysearchitem);
            switch ($skeywords) {
                case 0 :
                    $tmp .= "(m.media_title LIKE '%$mysearchitem%' OR m.media_desc LIKE '%$mysearchitem%' OR m.media_keywords LIKE '%$mysearchitem%' OR m.artist LIKE '%$keywords%' OR m.album LIKE '%$keywords%' OR m.genre LIKE '%$keywords%') OR ";
                    break;
                case 1 :
                    $tmp .= "(m.media_keywords LIKE '%$mysearchitem%') OR ";
                    break;
                case 2 :
                    $tmp .= "(m.media_title LIKE '%$mysearchitem%' OR m.media_desc LIKE '%$mysearchitem%') OR ";
                    break;
                case 3 :
                    $tmp .= "(m.artist LIKE '%$mysearchitem%') OR ";
                    break;
                case 4 :
                    $tmp .= "(m.album LIKE '%$mysearchitem%') OR ";
                    break;
                case 5 :
                    $tmp .= "(m.genre LIKE '%$keywords%') OR ";
                    break;
            }
        }
        $tmp = substr($tmp, 0, strlen($tmp) - 3);
        $sqltmp .= "($tmp)";
    } else if ($stype == 'all') {
        $sqltmp .= 'AND ';
        $tmp = '';
        $mywords = explode(' ', $keywords);
        foreach ($mywords AS $mysearchitem) {
            $mysearchitem = addslashes($mysearchitem);
            switch ($skeywords) {
                case 0 :
                    $tmp .= "(m.media_title LIKE '%$mysearchitem%' OR m.media_desc LIKE '%$mysearchitem%' OR m.media_keywords LIKE '%$mysearchitem%' OR m.artist LIKE '%$keywords%' OR m.album LIKE '%$keywords%' OR m.genre LIKE '%$keywords%') AND ";
                    break;
                case 1 :
                    $tmp .= "(m.media_keywords LIKE '%$mysearchitem%') AND ";
                    break;
                case 2 :
                    $tmp .= "(m.media_title LIKE '%$mysearchitem%' OR m.media_desc LIKE '%$mysearchitem%') AND ";
                    break;
                case 3 :
                    $tmp .= "(m.artist LIKE '%$mysearchitem%') AND ";
                    break;
                case 4 :
                    $tmp .= "(m.album LIKE '%$mysearchitem%') AND ";
                    break;
                case 5 :
                    $tmp .= "(m.genre LIKE '%$keywords%') AND ";
                    break;
            }
        }
        $tmp = substr($tmp, 0, strlen($tmp) - 4);
        $sqltmp .= "($tmp)";
    } else {
        $sqltmp = "WHERE (m.media_title LIKE '%$keywords_db%' OR m.media_desc LIKE '%$keywords_db%' OR m.media_keywords LIKE '%$keywords_db%')";
    }

    if ($category != 0) {
        $sqltmp .= " AND m.media_category=" . $category;
    }
    if ($users > 0) {
        $sqltmp .= " AND m.media_user_id=" . $users;
    }

    $sqltmp = addslashes($sqltmp);

    $sort_id = COM_makesid();
    if (!isset($_USER['uid']) || $_USER['uid'] < 2) {
        $sort_user = 1;
    } else {
        $sort_user = $_USER['uid'];
    }
    $sort_datetime = time();

    $referer = addslashes($referer);
    $keywords = addslashes($keywords);

    if ($f_all == true || !empty($keywords)) {
        $sql = "INSERT INTO {$_TABLES['mg_sort']} (sort_id,sort_user,sort_query,sort_results,sort_datetime,referer,keywords)
                VALUES ('$sort_id',$sort_user,'$sqltmp',$numresults,$sort_datetime,'$referer','$keywords')";
        $result = DB_query($sql);
        if (DB_error()) {
            COM_errorLog("Media Gallery: Error placing sort query into database");
        }
        $display .= MG_search($sort_id, 1, $searchinfo);
    } else {
        $display .= MG_showSearchForm($searchinfo);
    }

    $sort_purge = time() - 3660; // 43200;
    DB_query("DELETE FROM {$_TABLES['mg_sort']} WHERE sort_datetime < " . $sort_purge);

} elseif ($mode == $LANG_MG01['cancel']) {
    echo COM_refresh($_MG_CONF['site_url'] . '/index.php');
    exit;
} elseif (isset($_GET['id'])) {
    $id = COM_applyFilter($_GET['id']);
    $page = intval(COM_applyFilter($_GET['page'], true));
    if ($page < 1 || empty($page)) $page = 1;
    $display .= MG_search($id, $page, $searchinfo);
}

$display = MG_createHTMLDocument($display, $LANG_MG00['results']);

COM_output($display);
?>