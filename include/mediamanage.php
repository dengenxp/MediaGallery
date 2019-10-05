<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | mediamanage.php                                                          |
// |                                                                          |
// | Media Management administration routines                                 |
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

use Geeklog\Input;

if (strpos(strtolower($_SERVER['PHP_SELF']), strtolower(basename(__FILE__))) !== false) {
    die('This file can not be used on its own!');
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/sort.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/classMedia.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/classAlbum.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-media.php';

function MG_imageAdmin($album_id, $page, $actionURL = '')
{
    global $_CONF, $_TABLES, $_USER, $_MG_CONF, $LANG_MG00, $LANG_MG01;

    $album = new mgAlbum($album_id);

    if ($actionURL == '') {
        $actionURL = $_MG_CONF['site_url'] . '/index.php';
    }

    if ($page > 0)
        $page = $page - 1;

    $begin = $_MG_CONF['mediamanage_items'] * $page;
    $end   = $_MG_CONF['mediamanage_items'];

    $retval = '';

    $T = COM_newTemplate(MG_getTemplatePath($album_id));
    $T->set_file(array(
        'admin' => 'mediamanage.thtml',
        'media' => 'mediaitems.thtml'
    ));

    // -- Get Album Cover Info..
    if ($album->access != 3) {
        COM_errorLog("Someone has tried to illegally edit media in Media Gallery. "
                   . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    $album_cover = $album->cover;

    $album_selectbox = '<select name="album">';
    $root_album = new mgAlbum(0);
    $root_album->buildAlbumBox($album_selectbox, $album_id, 3, $album_id, 'manage');
    $album_selectbox .= '</select>';

    $sql = "SELECT * FROM {$_TABLES['mg_category']} ORDER BY cat_id ASC";
    $result = DB_query($sql);
    $nrows = DB_numRows($result);
    for ($i=0; $i < $nrows; $i++) {
        $catRow[$i] = DB_fetchArray($result);
    }

    $sql = "SELECT COUNT(*) AS totalitems "
         . "FROM {$_TABLES['mg_media_albums']} "
         . "WHERE album_id=" . intval($album_id);
    $result = DB_query($sql);
    $row = DB_fetchArray($result);
    $totalAlbumItems = $row['totalitems'];

    $sql = MG_buildMediaSql(array(
        'album_id' => $album_id,
        'offset'   => $begin,
        'limit'    => $end
    ));
    $result = DB_query($sql);
    $nrows = DB_numRows($result);

    $batchOptionSelect = '<select name="batchOption">';
    if ($_CONF['image_lib'] == 'gdlib' && !function_exists("imagerotate")) {
        $batchOptionSelect .= '';
    } else {
        $batchOptionSelect .= '<option value="rrt">' . $LANG_MG01['rotate_right'] . '</option>';
        $batchOptionSelect .= '<option value="rlt">' . $LANG_MG01['rotate_left'] . '</option>';
    }
    if ($album->wm_id != 0) {
        $batchOptionSelect .= '<option value="watermark">' . $LANG_MG01['watermark'] . '</option>';
    }
    $batchOptionSelect .= '</select>&nbsp;';

    $T->set_var(array(
        'lang_albumsel'           => $LANG_MG01['destination_album'],
        'albumselect'             => $album_selectbox,
        'lang_save'               => $LANG_MG01['save'],
        'lang_cancel'             => $LANG_MG01['cancel'],
        'lang_delete'             => $LANG_MG01['delete'],
        'lang_move'               => $LANG_MG01['move'],
        'lang_select'             => $LANG_MG01['select'],
        'lang_item'               => $LANG_MG01['item'],
        'lang_order'              => $LANG_MG01['order'],
        'lang_cover'              => $LANG_MG01['cover'],
        'lang_title'              => $LANG_MG01['title'],
        'lang_description'        => $LANG_MG01['description'],
        'lang_checkall'           => $LANG_MG01['check_all'],
        'lang_uncheckall'         => $LANG_MG01['uncheck_all'],
        'lang_rotate_right'       => $LANG_MG01['rotate_right'],
        'lang_rotate_left'        => $LANG_MG01['rotate_left'],
        'lang_batch'              => $LANG_MG01['batch_process'],
        'lang_media_manage_title' => $LANG_MG01['manage_media'],
        'lang_media_manage_help'  => $LANG_MG01['media_manage_help'],
        'lang_reset_cover'        => $LANG_MG01['reset_cover'],
        'lang_include_ss'         => $LANG_MG01['include_ss'],
        'lang_watermarked'        => $LANG_MG01['watermarked'],
        'lang_delete_confirm'     => $LANG_MG01['delete_item_confirm'],
        'batchoptionselect'       => $batchOptionSelect,
        'lang_batch_options'      => $LANG_MG01['batch_options'],
        'lang_keywords'           => $LANG_MG01['keywords'],
        'albumselect'             => $album_selectbox,
        'lang_batch'              => $LANG_MG01['batch_process'],
        'batchoptionselect'       => $batchOptionSelect,
        'val_reset_cover'         => (($album_cover == '-1') ? ' checked="checked"' : ''),
    ));

    $tn_size = 1; // include:150x150
    $rowclass = 0;
    $counter = 0;
    if ($nrows == 0) {
        // we have nothing in the album at this time...
        $T->set_var('lang_no_image', $LANG_MG01['no_media_objects']);
    } else {
        $T->set_block('media', 'ImageColumn', 'IColumn');
        $T->set_block('media', 'ImageRow', 'IRow');

        for ($x = 0; $x < $nrows; $x+=3) {
            $T->set_var('IColumn','');

            for ($j = $x; $j < ($x + 3); $j++) {
                if ($j >= $nrows) break;

                $row = DB_fetchArray($result);

                $album_cover_check = '';
                $radio_box = '&nbsp;';
                if (($row['media_type'] == 0 || $row['media_tn_attached'] == 1) && $album->tn_attached == 0) {
                    $checked = ($album_cover == $row['media_id']) ? ' checked="checked"' : '';
                    $radio_box = '<input type="radio" name="cover" value="'
                               . $row['media_id'] . '"' . $checked . XHTML . '>';
                    $album_cover_check = $checked;
                }

                $include_ss = '&nbsp;';
                if ($row['media_type'] == 0) {
                    $checked = ($row['include_ss'] == 1) ? ' checked="checked"' : '';
                    $include_ss = '<input type="checkbox" name="ss[' . $counter . ']" value="1"'
                                . $checked . XHTML . '>';
                }

                switch ($row['media_type']) {
                    case 0 : // standard image
                        list($thumbnail, $pThumbnail, $img_size) = Media::getThumbInfo($row, $tn_size);
                        $fname = $row['media_filename'];
                        $ext = $row['media_mime_ext'];
                        $pDisplay = Media::getFilePath('disp', $fname, $ext);
                        $display  = Media::getFileUrl ('disp', $fname, $ext);
                        break;
                    default :
                        $mediaClass = new Media($row, $album_id);
                        list($thumbnail,$pThumbnail) = $mediaClass->displayRawThumb(1);
                        $img_size = @getimagesize($pThumbnail);
                        break;
                }
                $media_time = MG_getUserDateTimeFormat($row['media_time']);

                if ($img_size != false) {
                    list($width, $height) = Media::getImageWH($img_size[0], $img_size[1], 150, 150);
                } else {
                    //$width = 100;
                    //$height = 75;
                    $width = 150;
                    $height = 112;
                    $thumbnail = $_MG_CONF['mediaobjects_url'] . '/missing.png';
                }

                $cat_select = '<select name="cat_id[]">';
                $cat_select .= '<option value="0">' . $LANG_MG01['no_category'] . '</option>';
                $cRows = count($catRow);
                for ($i=0; $i < $cRows; $i++) {
                    $cat_select .= '<option value="' . $catRow[$i]['cat_id'] . '" '
                                 . ($catRow[$i]['cat_id'] == $row['media_category'] ? ' selected="selected"' : '') . '>'
                                 . $catRow[$i]['cat_name'] . '</option>';
                }
                $cat_select .= '</select>';

                $media_edit = $_MG_CONF['site_url'] . '/admin.php?mode=mediaedit&amp;mid='
                            . $row['media_id'] . '&amp;album_id=' . $album_id
                            . '&amp;t=' . time();

                $opt = array(
                    'playback_type'  => 0, // popup window mode
                    'skin'           => 'default',
                    'display_skin'   => 'default',
                    'full_display'   => 0,
                );
                $object = MG_buildContent($row, $opt);
                $media_zoom = '<a href="' . $object[4] . '">';

                $T->set_var(array(
                    'lang_category'     => $LANG_MG01['category'],
                    'cat_select'        => $cat_select,
                    'row_class'         => ($rowclass % 2) ? '1' : '2',
                    'media_id'          => $row['media_id'],
                    'mid'               => $row['media_id'],
                    'order'             => $row['media_order'],
                    'u_thumbnail'       => $thumbnail,
                    'media_title'       => $row['media_title'],
                    'media_desc'        => $row['media_desc'],
                    'media_keywords'    => $row['media_keywords'],
                    'media_time'        => $media_time[0],
                    'media_views'       => $row['media_views'],
                    'radio_box'         => $radio_box,
                    'album_cover_check' => $album_cover_check,
                    'include_ss'        => $include_ss,
                    'watermarked'       => ($row['media_watermarked'] ? '*' : ''),
                    'height'            => $height,
                    'width'             => $width,
                    'counter'           => $counter,
                    'media_edit'        => $media_edit,
                    'media_zoom'        => $media_zoom,
                    'lang_edit'         => $LANG_MG01['edit'],
                ));

                $rowclass++;
                $counter++;
                $T->parse('IColumn', 'ImageColumn', true);
            }
            $T->parse('IRow', 'ImageRow', true);
        }
        $T->parse('mediaitems', 'media');
    }

    $T->set_var(array(
        'album_id'               => $album_id,
        'url_album'              => $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id,
        's_mode'                 => 'cover',
        's_form_action'          => $actionURL,
        'mode'                   => 'media',
        'action'                 => 'cover',
        'lang_save'              => $LANG_MG01['save'],
        'lang_cancel'            => $LANG_MG01['cancel'],
        'lang_delete'            => $LANG_MG01['delete'],
        'lang_media_manage_help' => $LANG_MG01['media_manage_help'],
        'lang_delete_confirm'    => $LANG_MG01['delete_item_confirm'],
        'albums'                 => $LANG_MG01['albums'],
        'batchoptionselect'      => $batchOptionSelect,
        'bottom_pagination'      => COM_printPageNavigation($_MG_CONF['site_url'] . '/admin.php?album_id=' . $album_id
                                       . '&amp;mode=media', $page+1,ceil($totalAlbumItems  / $_MG_CONF['mediamanage_items'])),
    ));
    $retval .= $T->finish($T->parse('output', 'admin'));
    return $retval;
}


function MG_saveMedia($album_id, $actionURL = '')
{
    global $_USER, $_CONF, $_TABLES, $_MG_CONF, $LANG_MG00, $LANG_MG01, $LANG_MG03;

    // check permissions...

    $sql = "SELECT * FROM {$_TABLES['mg_albums']} WHERE album_id=" . intval($album_id);
    $result = DB_query($sql);
    $row = DB_fetchArray($result);
    if (DB_error() != 0) {
        echo COM_errorLog("Media Gallery - Error retrieving album cover.");
    }
    $access = SEC_hasAccess($row['owner_id'], $row['group_id'], $row['perm_owner'],
                            $row['perm_group'], $row['perm_members'], $row['perm_anon']);

    if ($access != 3 && !SEC_hasRights('mediagallery.admin')) {
        COM_errorLog("Someone has tried to illegally manage (save) Media Gallery. "
                   . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    $numItems = count(Input::post('mid', array()));

    for ($i=0; $i < $numItems; $i++) {
        $media[$i]['mid']         = $_POST['mid'][$i];
        $media[$i]['seq']         = $_POST['seq'][$i];
        $media[$i]['oldseq']      = $_POST['oldseq'][$i];
        $media[$i]['title']       = COM_stripslashes($_POST['mtitle'][$i]);
        $media[$i]['description'] = COM_stripslashes($_POST['mdesc'][$i]);
        $media[$i]['include_ss']  = $_POST['ss'][$i];
        $media[$i]['keywords']    = COM_stripslashes($_POST['mkeywords'][$i]);
        $media[$i]['cat_id']      = $_POST['cat_id'][$i];
    }

    for ($i=0; $i < $numItems; $i++) {
        $media_title_safe = substr($media[$i]['title'], 0, 254);

        if ($_MG_CONF['htmlallowed'] != 1) {
            $media_title = DB_escapeString(htmlspecialchars(strip_tags(COM_checkWords($media_title_safe))));
            $media_desc  = DB_escapeString(htmlspecialchars(strip_tags(COM_checkWords($media[$i]['description']))));
        } else {
            $media_title = DB_escapeString($media_title_safe);
            $media_desc  = DB_escapeString($media[$i]['description']);
        }
        if ($media[$i]['include_ss'] == 1) {
            $ss = 1;
        } else {
            $ss = 0;
        }
        $media_keywords_safe = substr($media[$i]['keywords'],0,254);
        $media_keywords = DB_escapeString(htmlspecialchars(strip_tags(COM_checkWords($media_keywords_safe))));
        $cat_id = $media[$i]['cat_id'];

        $sql = "UPDATE {$_TABLES['mg_media']} SET media_title='" . $media_title
             . "',media_desc='" . $media_desc
             . "',include_ss=" . intval($ss)
             . ",media_keywords='" . $media_keywords
             . "',media_category=" . $cat_id
             . " WHERE media_id='" . DB_escapeString($media[$i]['mid']) . "'";
        DB_query($sql);
        $sql = "UPDATE {$_TABLES['mg_media_albums']}"
             . " SET media_order=" . intval($media[$i]['seq'])
             . " WHERE album_id=" . intval($album_id)
             . " AND media_id='" . DB_escapeString($media[$i]['mid']) . "'";
        DB_query($sql);
        PLG_itemSaved($media[$i]['mid'],'mediagallery');
    }
    MG_reorderMedia($album_id);

    // Now do the album cover...

    $cover = isset($_POST['cover']) ? COM_applyFilter($_POST['cover'], true) : 0;

    if ($cover == 0) {
        $cover = -1;
    }

    // get the filename

    // we need to fix this so that it pulls the whole media record, if it is a video / audio file
    // we need to see if a thumbnail is attached and then act properly.

    if ($cover != -1) {

        $sql = "SELECT media_type,media_tn_attached,media_filename "
             . "FROM {$_TABLES['mg_media']} WHERE media_id='" . DB_escapeString($cover) . "'";
        $result = DB_query($sql);
        $nrows = DB_numRows($result);
        if ($nrows > 0) {
            $row = DB_fetchArray($result);
            switch ($row['media_type']) {
                case 0 :  // image
                    if ($row['media_tn_attached'] == 1) {
                        $coverFilename = 'tn_' . $row['media_filename'];
                    } else {
                        $coverFilename = $row['media_filename'];
                    }
                    break;
                default : // we will treat all the non image media the same...
                    if ($row['media_tn_attached'] == 1) {
                        $coverFilename = 'tn_' . $row['media_filename'];
                    } else {
                        $coverFilename = '';
                    }
            }
        }
        if ($coverFilename != '') {
            DB_change($_TABLES['mg_albums'], 'album_cover', DB_escapeString($cover), 'album_id', intval($album_id));
            DB_change($_TABLES['mg_albums'], 'album_cover_filename', $coverFilename, 'album_id', intval($album_id));
        }
    }

    if ($cover == -2) { // reset
        MG_resetAlbumCover($album_id);
    }
    require_once $_CONF['path'] . 'plugins/mediagallery/include/rssfeed.php';
    MG_buildAlbumRSS($album_id);
    COM_redirect($actionURL);
}

function MG_mediaEdit($album_id, $media_id, $actionURL='', $mqueue=0, $view=0, $back='')
{
    global $_USER, $_CONF, $_MG_CONF, $_TABLES, $_MG_CONF, $LANG_MG00,
           $LANG_MG01, $LANG_MG03, $LANG_MG07, $_DB_dbms;

    $album = new mgAlbum($album_id);

    if ($actionURL == '') {
        $actionURL = $_MG_CONF['site_url'] . '/index.php';
    }

    $retval = '';

    $T = COM_newTemplate(MG_getTemplatePath($album_id));
    $T->set_file(array(
        'admin'       => 'mediaedit.thtml',
        'asf_options' => 'edit_asf_options.thtml',
        'mp3_options' => 'edit_mp3_options.thtml',
        'swf_options' => 'edit_swf_options.thtml',
        'mov_options' => 'edit_mov_options.thtml',
        'flv_options' => 'edit_flv_options.thtml',
    ));

    // pull the media information from the database...

    $sql = "SELECT * FROM ";
    if ($_DB_dbms == "mssql") {
        $sql = "SELECT *,CAST(media_desc AS TEXT) AS media_desc FROM ";
    }
    $sql .= ($mqueue ? $_TABLES['mg_mediaqueue'] : $_TABLES['mg_media']) .
            " WHERE media_id='" . DB_escapeString($media_id) . "'";
    $result = DB_query($sql);
    $row = DB_fetchArray($result);

    if ($album->access != 3 && !SEC_inGroup($album->mod_group_id) && $row['media_user_id'] != $_USER['uid']) {
        COM_errorLog("Someone has tried to illegally sort albums in Media Gallery. "
                   . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
        return COM_showMessageText($LANG_MG00['access_denied_msg']);
    }

    // Build Album List
    $album_jumpbox = '<select name="albums" width="40">';
    $root_album = new mgAlbum(0);
    $root_album->buildJumpBox($album_jumpbox, $album_id);
    $album_jumpbox .= '</select>';

    // should check the above for errors, etc...

    $exif_info = '';
    if ($row['media_type'] == 0) {
        if (!function_exists('MG_readEXIF')) {
            require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-exif.php';
        }
        $exif_info = MG_readEXIF($row['media_id'], 1, $mqueue);
        if (empty($exif_info)) {
            $exif_info = '';
        }
    }

    $media_time_month  = date("m", $row['media_time']);
    $media_time_day    = date("d", $row['media_time']);
    $media_time_year   = date("Y", $row['media_time']);
    $media_time_hour   = date("H", $row['media_time']);
    $media_time_minute = date("i", $row['media_time']);

    $month_select = '<select name="media_month">';
    $month_select .= COM_getMonthFormOptions($media_time_month);
    $month_select .= '</select>';

    $day_select = '<select name="media_day">';
    for ($i = 1; $i < 32; $i++) {
        $day_select .= '<option value="' . $i . '"'
            . ($media_time_day == $i ? 'selected="selected"' : "") . '>'
            . $i . '</option>';
    }
    $day_select .= '</select>';

    $current_year = (int) date("Y");
    $end_year = $current_year + 10;

    $year_select = '<select name="media_year">';
    for ($i = 1998; $i < $end_year; $i++) {
        $year_select .= '<option value="' . $i . '"'
            . ($media_time_year == $i ? 'selected="selected"' : "") . '>'
            . $i . '</option>';
    }
    $year_select .= '</select>';

    $hour_select = '<select name="media_hour">';
    for ($i = 0; $i < 24; $i++) {
        $hour_select .= '<option value="' . $i . '"'
            . ($media_time_hour == $i ? 'selected="selected"' : "") . '>'
            . $i . '</option>';
    }
    $hour_select .= '</select>';

    $minute_select = '<select name="media_minute">';
    for ($i = 0; $i < 60; $i++) {
        $minute_select .= '<option value="' . $i . '"'
            . ($media_time_minute == $i ? 'selected="selected"' : "") . '>'
            . ($i < 10 ? '0' : '') . $i . '</option>';
    }
    $minute_select .= '</select>';

    $media_time = MG_getUserDateTimeFormat($row['media_time']);

    $tn_size = 1;
    list($thumbnail, $pThumbnail, $size) = Media::getThumbInfo($row, $tn_size);
    $attached_thumbnail ='';
    if ($row['media_tn_attached'] == 1) {
        $atnsize = '';
        if ($size != false) {
            list($newwidth, $newheight) = Media::getImageWH($size[0], $size[1], 150, 150);
            $atnsize = 'width="' . $newwidth . '" height="' . $newheight . '"';
        }
        $attached_thumbnail = '<img src="' . $thumbnail . '" alt="" ' . $atnsize . XHTML . '>';
        $tmpthumb = Media::getDefaultThumbnail($row, $tn_size);
        $thumbnail = $_MG_CONF['mediaobjects_url'] . '/' . $tmpthumb;
        $size = getimagesize($_MG_CONF['path_mediaobjects'] . $tmpthumb);
    }

    $preview = '';
    $preview_end = '';
    if ($row['media_type'] == 0 || $row['media_type'] == 1 || $row['media_type'] == 2) { // image, video and music file
        if ($row['media_type'] == 2) {
            $win_width  = 540;
            $win_height = 320;
        } elseif ($row['media_type'] == 1) {
            $win_width  = 660;
            $win_height = 525;
        } elseif ($row['media_type'] == 0) {
            $path = Media::getFilePath('disp', $row['media_filename'], $row['media_mime_ext']);
            $media_size_disp = @getimagesize($path);
            $win_width  = $media_size_disp[0] + 20;
            $win_height = $media_size_disp[1] + 20;
        } else {
            $win_width  = 800;
            $win_height = 600;
        }
        $url = Media::getHref_showvideo($row['media_id'], $win_height, $win_width, $mqueue);
        $preview = "<a href=\"" . $url . "\">";
        $preview_end  = "</a>";
    }

    $rotate_right = '';
    $rotate_left  = '';
    if ($row['media_type'] == 0 && ($_CONF['image_lib'] != 'gdlib' || function_exists("imagerotate"))) {
        $rotate_right = '<a href="' . $_MG_CONF['site_url']
                      . '/admin.php?mode=rotate&amp;action=right&amp;media_id='
                      . $row['media_id'] . '&amp;album_id=' . $album_id . '">'
                      . '<img src="' . $_MG_CONF['site_url'] . '/images/rotate_right_icon.gif" alt="'
                      . $LANG_MG01['rotate_left']  . '" style="border:none;"' . XHTML . '></a>';
        $rotate_left  = '<a href="' . $_MG_CONF['site_url']
                      . '/admin.php?mode=rotate&amp;action=left&amp;media_id='
                      . $row['media_id'] . '&amp;album_id=' . $album_id . '">'
                      . '<img src="' . $_MG_CONF['site_url'] . '/images/rotate_left_icon.gif" alt="'
                      . $LANG_MG01['rotate_right'] . '" style="border:none;"' . XHTML . '></a>';
    }

    $resolution = '';
    $lang_resolution = '';
    if ($row['media_type'] == 1) { // video file
        $resolution = 'unknown';
        if ($row['media_resolution_x'] > 0 && $row['media_resolution_y'] > 0) {
            $resolution = $row['media_resolution_x'] . 'x' . $row['media_resolution_y'];
        }
        $lang_resolution = $LANG_MG07['resolution'];
    }

    $sql = "SELECT * FROM {$_TABLES['mg_playback_options']} "
         . "WHERE media_id='" . DB_escapeString($row['media_id']) . "'";
    $poResult = DB_query($sql);
    $poNumRows = DB_numRows($poResult);

    // playback options, if needed...
    if ( $row['mime_type'] == 'video/x-ms-asf' ||
         $row['mime_type'] == 'video/x-ms-wvx' ||
         $row['mime_type'] == 'video/x-ms-wm'  ||
         $row['mime_type'] == 'video/x-ms-wmx' ||
         $row['mime_type'] == 'video/x-ms-wmv' ||
         $row['mime_type'] == 'audio/x-ms-wma' ||
         $row['mime_type'] == 'video/x-msvideo' ) {
        // pull defaults, then override...
        $playback_options['autostart']         = $_MG_CONF['asf_autostart'];
        $playback_options['enablecontextmenu'] = $_MG_CONF['asf_enablecontextmenu'];
        $playback_options['stretchtofit']      = $_MG_CONF['asf_stretchtofit'];
        $playback_options['uimode']            = $_MG_CONF['asf_uimode'];
        $playback_options['showstatusbar']     = $_MG_CONF['asf_showstatusbar'];
        $playback_options['playcount']         = $_MG_CONF['asf_playcount'];
        $playback_options['height']            = $_MG_CONF['asf_height'];
        $playback_options['width']             = $_MG_CONF['asf_width'];
        $playback_options['bgcolor']           = $_MG_CONF['asf_bgcolor'];

        for ($i=0; $i < $poNumRows; $i++) {
            $poRow = DB_fetchArray($poResult);
            $playback_options[$poRow['option_name']] = $poRow['option_value'];
        }

        $uimode_select = MG_optionlist(array(
            'name'    => 'uimode',
            'current' => $playback_options['uimode'],
            'values'  => array(
                'none' => $LANG_MG07['none'],
                'mini' => $LANG_MG07['mini'],
                'full' => $LANG_MG07['full'],
            ),
        ));

        $T->set_var(array(
            'autostart_enabled'          => $playback_options['autostart'] ? ' checked="checked"' : '',
            'autostart_disabled'         => $playback_options['autostart'] ? '' : ' checked="checked"',
            'enablecontextmenu_enabled'  => $playback_options['enablecontextmenu'] ? ' checked="checked"' : '',
            'enablecontextmenu_disabled' => $playback_options['enablecontextmenu'] ? '' : ' checked="checked"',
            'stretchtofit_enabled'       => $playback_options['stretchtofit'] ? ' checked="checked"' : '',
            'stretchtofit_disabled'      => $playback_options['stretchtofit'] ? '' : ' checked="checked"',
            'showstatusbar_enabled'      => $playback_options['showstatusbar'] ? ' checked="checked"' : '',
            'showstatusbar_disabled'     => $playback_options['showstatusbar'] ? '' : ' checked="checked"',
            'uimode_select'              => $uimode_select,
            'uimode'                     => $playback_options['uimode'],
            'playcount'                  => $playback_options['playcount'],
            'height'                     => $playback_options['height'],
            'width'                      => $playback_options['width'],
            'bgcolor'                    => $playback_options['bgcolor'],
            'lang_resolution'            => $lang_resolution,
            'resolution'                 => $resolution,
        ));
        $T->parse('playback_options', 'asf_options');
    }

    if ($row['mime_type'] == 'audio/mpeg') {
        // pull defaults, then override...
        $playback_options['autostart']         = $_MG_CONF['mp3_autostart'];
        $playback_options['enablecontextmenu'] = $_MG_CONF['mp3_enablecontextmenu'];
        $playback_options['uimode']            = $_MG_CONF['mp3_uimode'];
        $playback_options['showstatusbar']     = $_MG_CONF['mp3_showstatusbar'];
        $playback_options['loop']              = $_MG_CONF['mp3_loop'];

        for ($i=0; $i < $poNumRows; $i++) {
            $poRow = DB_fetchArray($poResult);
            $playback_options[$poRow['option_name']] = $poRow['option_value'];
        }

        $uimode_select = MG_optionlist(array(
            'name'    => 'uimode',
            'current' => $playback_options['uimode'],
            'values'  => array(
                'none' => $LANG_MG07['none'],
                'mini' => $LANG_MG07['mini'],
                'full' => $LANG_MG07['full'],
            ),
        ));

        $T->set_var(array(
            'autostart_enabled'          => $playback_options['autostart'] ? ' checked="checked"' : '',
            'autostart_disabled'         => $playback_options['autostart'] ? '' : ' checked="checked"',
            'enablecontextmenu_enabled'  => $playback_options['enablecontextmenu'] ? ' checked="checked"' : '',
            'enablecontextmenu_disabled' => $playback_options['enablecontextmenu'] ? '' : ' checked="checked"',
            'showstatusbar_enabled'      => $playback_options['showstatusbar'] ? ' checked="checked"' : '',
            'showstatusbar_disabled'     => $playback_options['showstatusbar'] ? '' : ' checked="checked"',
            'loop_enabled'               => $playback_options['loop'] ? ' checked="checked"' : '',
            'loop_disabled'              => $playback_options['loop'] ? '' : ' checked="checked"',
            'uimode_select'              => $uimode_select,
            'uimode'                     => $playback_options['uimode'],
        ));
        $T->parse('playback_options', 'mp3_options');
    }

    if ($row['mime_type'] == 'application/x-shockwave-flash' ||
        $row['mime_type'] == 'video/x-flv') {
        // pull defaults, then override...
        $playback_options['play']              = $_MG_CONF['swf_play'];
        $playback_options['menu']              = $_MG_CONF['swf_menu'];
        $playback_options['quality']           = $_MG_CONF['swf_quality'];
        $playback_options['height']            = $_MG_CONF['swf_height'];
        $playback_options['width']             = $_MG_CONF['swf_width'];
        $playback_options['loop']              = $_MG_CONF['swf_loop'];
        $playback_options['scale']             = $_MG_CONF['swf_scale'];
        $playback_options['wmode']             = $_MG_CONF['swf_wmode'];
        $playback_options['allowscriptaccess'] = $_MG_CONF['swf_allowscriptaccess'];
        $playback_options['bgcolor']           = $_MG_CONF['swf_bgcolor'];
        $playback_options['swf_version']       = $_MG_CONF['swf_version'];

        for ($i=0; $i < $poNumRows; $i++) {
            $poRow = DB_fetchArray($poResult);
            $playback_options[$poRow['option_name']] = $poRow['option_value'];
        }

        $quality_select = MG_optionlist(array(
            'name'    => 'quality',
            'current' => $playback_options['quality'],
            'values'  => array(
                'low'  => $LANG_MG07['low'],
                'high' => $LANG_MG07['high'],
            ),
        ));

        $scale_select = MG_optionlist(array(
            'name'    => 'scale',
            'current' => $playback_options['scale'],
            'values'  => array(
                'showall'  => $LANG_MG07['showall'],
                'noborder' => $LANG_MG07['noborder'],
                'exactfit' => $LANG_MG07['exactfit'],
            ),
        ));

        $wmode_select = MG_optionlist(array(
            'name'    => 'wmode',
            'current' => $playback_options['wmode'],
            'values'  => array(
                'window'      => $LANG_MG07['window'],
                'opaque'      => $LANG_MG07['opaque'],
                'transparent' => $LANG_MG07['transparent'],
            ),
        ));

        $asa_select = MG_optionlist(array(
            'name'    => 'allowscriptaccess',
            'current' => $playback_options['allowscriptaccess'],
            'values'  => array(
                'always'     => $LANG_MG07['always'],
                'sameDomain' => $LANG_MG07['sameDomain'],
                'never'      => $LANG_MG07['never'],
            ),
        ));

        $T->set_var(array(
            'play_enabled'   => $playback_options['play'] ? ' checked="checked"' : '',
            'play_disabled'  => $playback_options['play'] ? '' : ' checked="checked"',
            'menu_enabled'   => $playback_options['menu'] ? ' checked="checked"' : '',
            'menu_disabled'  => $playback_options['menu'] ? '' : ' checked="checked"',
            'loop_enabled'   => $playback_options['loop'] ? ' checked="checked"' : '',
            'loop_disabled'  => $playback_options['loop'] ? '' : ' checked="checked"',
            'quality_select' => $quality_select,
            'scale_select'   => $scale_select,
            'wmode_select'   => $wmode_select,
            'asa_select'     => $asa_select,
            'flashvars'      => isset($playback_options['flashvars']) ? $playback_options['flashvars'] : '',
            'height'         => $playback_options['height'],
            'width'          => $playback_options['width'],
            'bgcolor'        => $playback_options['bgcolor'],
            'swf_version'    => $playback_options['swf_version'],
        ));
        if ($row['mime_type'] == 'application/x-shockwave-flash') {
            $T->parse('playback_options', 'swf_options');
        } else {
            $T->parse('playback_options', 'flv_options');
        }
    }

    if ($row['media_mime_ext'] == 'mov' ||
        $row['media_mime_ext'] == 'mp4' ||
        $row['mime_type'] == 'video/quicktime' ||
        $row['mime_type'] == 'video/mpeg') {
        // pull defaults, then override...
        $playback_options['autoref']    = $_MG_CONF['mov_autoref'];
        $playback_options['autoplay']   = $_MG_CONF['mov_autoplay'];
        $playback_options['controller'] = $_MG_CONF['mov_controller'];
        $playback_options['kioskmode']  = isset($_MG_CONF['mov_kioskmod']) ? $_MG_CONF['mov_kiokmode'] : '';
        $playback_options['scale']      = $_MG_CONF['mov_scale'];
        $playback_options['loop']       = $_MG_CONF['mov_loop'];
        $playback_options['height']     = $_MG_CONF['mov_height'];
        $playback_options['width']      = $_MG_CONF['mov_width'];
        $playback_options['bgcolor']    = $_MG_CONF['mov_bgcolor'];

        for ($i=0; $i < $poNumRows; $i++) {
            $poRow = DB_fetchArray($poResult);
            $playback_options[$poRow['option_name']] = $poRow['option_value'];
        }

        $scale_select = MG_optionlist(array(
            'name'    => 'scale',
            'current' => $playback_options['scale'],
            'values'  => array(
                'tofit'  => $LANG_MG07['to_fit'],
                'aspect' => $LANG_MG07['aspect'],
                '1'      => $LANG_MG07['normal_size'],
            ),
        ));

        $T->set_var(array(
            'autoref_enabled'     => $playback_options['autoref'] ? ' checked="checked"' : '',
            'autoref_disabled'    => $playback_options['autoref'] ? '' : ' checked="checked"',
            'autoplay_enabled'    => $playback_options['autoplay'] ? ' checked="checked"' : '',
            'autoplay_disabled'   => $playback_options['autoplay'] ? '' : ' checked="checked"',
            'controller_enabled'  => $playback_options['controller'] ? ' checked="checked"' : '',
            'controller_disabled' => $playback_options['controller'] ? '' : ' checked="checked"',
            'kioskmode_enabled'   => $playback_options['kioskmode'] ? ' checked="checked"' : '',
            'kioskmode_disabled'  => $playback_options['kioskmode'] ? '' : ' checked="checked"',
            'loop_enabled'        => $playback_options['loop'] ? ' checked="checked"' : '',
            'loop_disabled'       => $playback_options['loop'] ? '' : ' checked="checked"',
            'height'              => $playback_options['height'],
            'width'               => $playback_options['width'],
            'bgcolor'             => $playback_options['bgcolor'],
        ));
        $T->parse('playback_options', 'mov_options');
    }

    $remoteurl = $row['remote_url'];
    $lang_remote_url = ($row['remote_media'] == 1) ? $LANG_MG01['remote_url'] : $LANG_MG01['alternate_url'];

    // user information
    $username = '';
    if (SEC_hasRights('mediagallery.admin')) {
        $username = '<select name="owner_name"> ';
        $sql = "SELECT * FROM {$_TABLES['users']} WHERE status=3 AND uid > 1 ORDER BY username ASC";
        $result = DB_query($sql);
        while ($userRow = DB_fetchArray($result)) {
            $username .= '<option value="'.$userRow['uid'].'"'
            . ($userRow['uid'] == $row['media_user_id'] ? ' selected="selected"' : '')
            .'>'.$userRow['username'].'</option>' .LB;
        }
        $username .= '</select>';
    } else {
        if ($row['media_user_id'] != '') {
            $displayname = $_CONF['show_fullname'] ? 'fullname' : 'username';
            $username = DB_getItem($_TABLES['users'], $displayname, "uid={$row['media_user_id']}");
        }
    }

    $cat_select = '<select name="cat_id" id="cat_id">';
    $cat_select .= '<option value="">' . $LANG_MG01['no_category'] . '</option>';
    $result = DB_query("SELECT * FROM {$_TABLES['mg_category']} ORDER BY cat_id ASC");
    while ($catRow = DB_fetchArray($result)) {
        $cat_select .= '<option value="' . $catRow['cat_id'] . '" '
                     . ($catRow['cat_id'] == $row['media_category'] ? ' selected="selected"' : '') . '>'
                     . $catRow['cat_name'] . '</option>';
    }
    $cat_select .= '</select>';

    $T->set_var(array(
        'original_filename'  => $row['media_original_filename'],
        'attach_tn'          => $row['media_tn_attached'],
        'at_tn_checked'      => $row['media_tn_attached'] == 1 ? ' checked="checked"' : '',
        'attached_thumbnail' => $attached_thumbnail,
        'album_id'           => $album_id,
        'media_thumbnail'    => $thumbnail,
        'media_id'           => $row['media_id'],
        'media_title'        => $row['media_title'],
        'media_desc'         => $row['media_desc'],
        'media_time'         => $media_time[0],
        'media_views'        => $row['media_views'],
        'media_comments'     => $row['media_comments'],
        'media_exif_info'    => $exif_info,
        'media_rating_max'   => 5,
        'height'             => $size[1] + 50,
        'width'              => $size[0] + 40,
        'queue'              => $mqueue,
        'month_select'       => $month_select,
        'day_select'         => $day_select,
        'year_select'        => $year_select,
        'hour_select'        => $hour_select,
        'minute_select'      => $minute_select,
        'user_ip'            => $row['media_user_ip'],
        'album_select'       => $album_jumpbox,
        'media_rating'       => $row['media_rating'] / 2,
        'media_votes'        => $row['media_votes'],
        's_mode'             => 'edit',
        's_title'            => $LANG_MG01['edit_media'],
        's_rotate_right'     => $rotate_right,
        's_rotate_left'      => $rotate_left,
        's_form_action'      => $actionURL,
        'allowed_html'       => COM_allowedHTML(),
        'site_url'           => $_MG_CONF['site_url'],
        'preview'            => $preview,
        'preview_end'        => $preview_end,
        'rpath'              => htmlentities($back, ENT_QUOTES, COM_getCharset()),
        'remoteurl'          => $remoteurl,
        'lang_remote_url'    => $lang_remote_url,
        'resolution'         => $resolution,
        'lang_resolution'    => $lang_resolution,
        'username'           => $username,
        'cat_select'         => $cat_select,
        'media_keywords'     => $row['media_keywords'],
        'artist'             => $row['artist'],
        'musicalbum'         => $row['album'],
        'genre'              => $row['genre'],
    ));

    // language items

    $T->set_var(array(
        'lang_playcount'                => $LANG_MG07['playcount'],
        'lang_playcount_help'           => $LANG_MG07['playcount_help'],
        'lang_playback_options'         => $LANG_MG07['playback_options'],
        'lang_option'                   => $LANG_MG07['option'],
        'lang_description'              => $LANG_MG07['description'],
        'lang_on'                       => $LANG_MG07['on'],
        'lang_off'                      => $LANG_MG07['off'],
        'lang_auto_start'               => $LANG_MG07['auto_start'],
        'lang_auto_start_help'          => $LANG_MG07['auto_start_help'],
        'lang_height'                   => $LANG_MG07['height'],
        'lang_width'                    => $LANG_MG07['width'],
        'lang_height_help'              => $LANG_MG07['height_help'],
        'lang_width_help'               => $LANG_MG07['width_help'],
        'lang_enable_context_menu'      => $LANG_MG07['enable_context_menu'],
        'lang_enable_context_menu_help' => $LANG_MG07['enable_context_menu_help'],
        'lang_stretch_to_fit'           => $LANG_MG07['stretch_to_fit'],
        'lang_stretch_to_fit_help'      => $LANG_MG07['stretch_to_fit_help'],
        'lang_status_bar'               => $LANG_MG07['status_bar'],
        'lang_status_bar_help'          => $LANG_MG07['status_bar_help'],
        'lang_ui_mode'                  => $LANG_MG07['ui_mode'],
        'lang_ui_mode_help'             => $LANG_MG07['ui_mode_help'],
        'lang_bgcolor'                  => $LANG_MG07['bgcolor'],
        'lang_bgcolor_help'             => $LANG_MG07['bgcolor_help'],
        'lang_loop'                     => $LANG_MG07['loop'],
        'lang_loop_help'                => $LANG_MG07['loop_help'],
        'lang_menu'                     => $LANG_MG07['menu'],
        'lang_menu_help'                => $LANG_MG07['menu_help'],
        'lang_scale'                    => $LANG_MG07['scale'],
        'lang_swf_scale_help'           => $LANG_MG07['swf_scale_help'],
        'lang_wmode'                    => $LANG_MG07['wmode'],
        'lang_wmode_help'               => $LANG_MG07['wmode_help'],
        'lang_quality'                  => $LANG_MG07['quality'],
        'lang_quality_help'             => $LANG_MG07['quality_help'],
        'lang_flash_vars'               => $LANG_MG07['flash_vars'],
        'lang_asa'                      => $LANG_MG07['asa'],
        'lang_asa_help'                 => $LANG_MG07['asa_help'],
        'lang_swf_version_help'         => $LANG_MG07['swf_version_help'],
        'lang_auto_ref'                 => $LANG_MG07['auto_ref'],
        'lang_auto_ref_help'            => $LANG_MG07['auto_ref_help'],
        'lang_controller'               => $LANG_MG07['controller'],
        'lang_controller_help'          => $LANG_MG07['controller_help'],
        'lang_kiosk_mode'               => $LANG_MG07['kiosk_mode'],
        'lang_kiosk_mode_help'          => $LANG_MG07['kiosk_mode_help'],
        'lang_original_filename'        => $LANG_MG01['original_filename'],
        'lang_media_item'               => $LANG_MG00['media_col_header'],
        'lang_media_attributes'         => $LANG_MG01['media_attributes'],
        'lang_mediaattributes'          => $LANG_MG01['mediaattributes'],
        'lang_attached_thumbnail'       => $LANG_MG01['attached_thumbnail'],
        'lang_category'                 => $LANG_MG01['category'],
        'lang_keywords'                 => $LANG_MG01['keywords'],
        'lang_rating'                   => $LANG_MG03['rating'],
        'lang_comments'                 => $LANG_MG03['comments'],
        'lang_votes'                    => $LANG_MG03['votes'],
        'media_edit_title'              => $LANG_MG01['media_edit'],
        'media_edit_help'               => $LANG_MG01['media_edit_help'],
        'rotate_left'                   => $LANG_MG01['rotate_left'],
        'rotate_right'                  => $LANG_MG01['rotate_right'],
        'lang_title'                    => $LANG_MG01['title'],
        'albums'                        => $LANG_MG01['albums'],
        'description'                   => $LANG_MG01['description'],
        'capture_time'                  => $LANG_MG01['capture_time'],
        'views'                         => $LANG_MG03['views'],
        'uploaded_by'                   => $LANG_MG01['uploaded_by'],
        'submit'                        => $LANG_MG01['submit'],
        'cancel'                        => $LANG_MG01['cancel'],
        'reset'                         => $LANG_MG01['reset'],
        'lang_save'                     => $LANG_MG01['save'],
        'lang_reset'                    => $LANG_MG01['reset'],
        'lang_cancel'                   => $LANG_MG01['cancel'],
        'lang_delete'                   => $LANG_MG01['delete'],
        'lang_delete_confirm'           => $LANG_MG01['delete_item_confirm'],
        'lang_reset_rating'             => $LANG_MG01['reset_rating'],
        'lang_reset_views'              => $LANG_MG01['reset_views'],
        'lang_replacefile'              => $LANG_MG01['replace_file'],
        'lang_artist'                   => $LANG_MG01['artist'],
        'lang_genre'                    => $LANG_MG01['genre'],
        'lang_music_album'              => $LANG_MG01['music_album'],
    ));

    $retval .= $T->finish($T->parse('output', 'admin'));

    return $retval;
}

function MG_mediaResetRating($album_id, $media_id, $mqueue)
{
    global $_MG_CONF, $_TABLES;

    DB_change($_TABLES['mg_media'], 'media_rating', 0, 'media_id', DB_escapeString($media_id));
    DB_change($_TABLES['mg_media'], 'media_votes', 0, 'media_id', DB_escapeString($media_id));
    DB_delete($_TABLES['mg_rating'], 'media_id', DB_escapeString($media_id));
    $retval = MG_mediaEdit($album_id, $media_id,
                           $_MG_CONF['site_url'] . '/admin.php?mode=media&amp;album_id='
                           . $album_id, $mqueue);
    return $retval;
}

function MG_mediaResetViews($album_id, $media_id, $mqueue)
{
    global $_MG_CONF, $_TABLES;

    DB_change($_TABLES['mg_media'], 'media_views', 0, 'media_id', DB_escapeString($media_id));
    $retval = MG_mediaEdit($album_id, $media_id,
                           $_MG_CONF['site_url'] . '/admin.php?mode=media&amp;album_id='
                           . $album_id, $mqueue);
    return $retval;
}

function MG_savePBOption($mid, $name, $val, $is_num=false)
{
    global $_TABLES;

    $mid = DB_escapeString($mid);
    $name = DB_escapeString($name);
    if ($is_num) {
        $val = intval($val);
    } else {
        $val = DB_escapeString($val);
    }
    DB_save($_TABLES['mg_playback_options'], 'media_id, option_name, option_value', "'$mid', '$name', '$val'");
}

function MG_saveMediaEdit($album_id, $media_id, $actionURL)
{
    global $_USER, $_CONF, $_TABLES, $_MG_CONF, $LANG_MG00, $LANG_MG01, $LANG_MG03;

    $back = COM_applyFilter($_POST['rpath']);
    if ($back != '') {
        $actionURL = $back;
    }

    $queue = COM_applyFilter($_POST['queue'], true);

    $replacefile = 0;
    if (isset($_POST['replacefile'])) {
        $replacefile = COM_applyFilter($_POST['replacefile']);
    }
    if ($replacefile == 1) {
        require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-upload.php';
        $repfilename = $_FILES['repfilename'];
        $filename = $repfilename['name'];
        $file = $repfilename['tmp_name'];

        $opt = array('replace' => $media_id);
        list($rc, $msg) = MG_getFile($file, $filename, $album_id, $opt);

        COM_errorLog($msg);
    }

    // see if we had an attached thumbnail before...
    $thumb     = $_FILES['attthumb'];
    $thumbnail = $thumb['tmp_name'];
    $att       = isset($_POST['attachtn']) ? COM_applyFilter($_POST['attachtn'], true) : 0;

    $attachtn = ($att == 1) ? 1 : 0;

    $table = $queue ? $_TABLES['mg_mediaqueue'] : $_TABLES['mg_media'];

    $old_attached_tn = DB_getItem($table, 'media_tn_attached', 'media_id="' . DB_escapeString($media_id) . '"');

    if ($old_attached_tn == 0 && $att == 1 && $thumbnail == '') {
        $attachtn = 0;
    }

    $remove_old_tn = 0;
    if ($old_attached_tn == 1 && $attachtn == 0) {
        $remove_old_tn = 1;
    }

    $remote_media = DB_getItem($table, 'remote_media', 'media_id="' . DB_escapeString($media_id) . '"');

    $remote_url = DB_escapeString(COM_stripslashes($_POST['remoteurl']));

    if ($_MG_CONF['htmlallowed']) {
        $media_title    = COM_checkWords(COM_stripslashes($_POST['media_title']));
        $media_desc     = COM_checkWords(COM_stripslashes($_POST['media_desc']));
    } else {
        $media_title    = htmlspecialchars(strip_tags(COM_checkWords(COM_stripslashes($_POST['media_title']))));
        $media_desc     = htmlspecialchars(strip_tags(COM_checkWords(COM_stripslashes($_POST['media_desc']))));
    }
    $media_time_month   = COM_applyFilter($_POST['media_month']);
    $media_time_day     = COM_applyFilter($_POST['media_day']);
    $media_time_year    = COM_applyFilter($_POST['media_year']);
    $media_time_hour    = COM_applyFilter($_POST['media_hour']);
    $media_time_minute  = COM_applyFilter($_POST['media_minute']);
    $original_filename  = COM_applyFilter(COM_stripslashes($_POST['original_filename']));
    if ($replacefile == 1) {
        $original_filename = $filename;
    }
    $cat_id             = COM_applyFilter($_POST['cat_id'],true);
    $media_keywords     = COM_stripslashes($_POST['media_keywords']);
    $media_keywords_safe = substr($media_keywords,0,254);
    $media_keywords = DB_escapeString(htmlspecialchars(strip_tags(COM_checkWords($media_keywords_safe))));

    $artist     = DB_escapeString(COM_applyFilter(COM_stripslashes($_POST['artist']) ) );
    $musicalbum = DB_escapeString(COM_applyFilter(COM_stripslashes($_POST['musicalbum']) ) );
    $genre      = DB_escapeString(COM_applyFilter(COM_stripslashes($_POST['genre']) ) );

    $media_time = mktime($media_time_hour,$media_time_minute,0,$media_time_month,$media_time_day,$media_time_year,1);

    $owner_sql = '';
    if (isset($_POST['owner_name'])) {
        $owner_id = COM_applyFilter($_POST['owner_name'], true);
        $owner_sql = ',media_user_id=' . $owner_id . ' ';
    }

    $sql = "UPDATE " . $table . "
            SET media_title='"  . DB_escapeString($media_title) . "',
            media_desc='"       . DB_escapeString($media_desc) . "',
            media_original_filename='" . DB_escapeString($original_filename) . "',
            media_time="        . $media_time . ",
            media_tn_attached=" . $attachtn . ",
            media_category="    . intval($cat_id) . ",
            media_keywords='"   . $media_keywords . "',
            artist='"           . $artist . "',
            album='"            . $musicalbum . "',
            genre='"            . $genre . "',
            remote_url='"       . $remote_url . "' " .
            $owner_sql .
            "WHERE media_id='"   . DB_escapeString($media_id) . "'";

    DB_query($sql);
    if (DB_error() != 0) {
        echo COM_errorLog("Media Gallery: ERROR Updating image in media database");
    }
    PLG_itemSaved($media_id, 'mediagallery');

    // process playback options if any...
    if (isset($_POST['autostart'])) {   // asf
        $opt['autostart']         = COM_applyFilter($_POST['autostart'], true);
        $opt['enablecontextmenu'] = COM_applyFilter($_POST['enablecontextmenu'], true);
        $opt['stretchtofit']      = isset($_POST['stretchtofit']) ? COM_applyFilter($_POST['stretchtofit'],true) : 0;
        $opt['showstatusbar']     = COM_applyFilter($_POST['showstatusbar'], true);
        $opt['uimode']            = COM_applyFilter($_POST['uimode']);
        $opt['height']            = isset($_POST['height'])    ? COM_applyFilter($_POST['height'],   true) : 0;
        $opt['width']             = isset($_POST['width'])     ? COM_applyFilter($_POST['width'],    true) : 0;
        $opt['bgcolor']           = isset($_POST['bgcolor'])   ? COM_applyFilter($_POST['bgcolor']) : 0;
        $opt['playcount']         = isset($_POST['playcount']) ? COM_applyFilter($_POST['playcount'],true) : 0;
        $opt['loop']              = isset($_POST['loop'])      ? COM_applyFilter($_POST['loop'],     true) : 0;

        if ($opt['playcount'] < 1) {
            $opt['playcount'] = 1;
        }

        MG_savePBOption($media_id, 'autostart',         $opt['autostart'], true);
        MG_savePBOption($media_id, 'enablecontextmenu', $opt['enablecontextmenu'], true);
        if ($opt['stretchtofit'] != '') {
            MG_savePBOption($media_id, 'stretchtofit', $opt['stretchtofit'], true);
        }
        MG_savePBOption($media_id, 'showstatusbar', $opt['showstatusbar'], true);
        MG_savePBOption($media_id, 'uimode',        $opt['uimode']);
        MG_savePBOption($media_id, 'height',        $opt['height'], true);
        MG_savePBOption($media_id, 'width',         $opt['width'], true);
        MG_savePBOption($media_id, 'bgcolor',       $opt['bgcolor']);
        MG_savePBOption($media_id, 'playcount',     $opt['playcount'], true);
        MG_savePBOption($media_id, 'loop',          $opt['loop'], true);
    }
    if (isset($_POST['play'])) {    // swf
        $opt['play']              = COM_applyFilter($_POST['play'],   true);
        $opt['menu']              = isset($_POST['menu'])              ? COM_applyFilter($_POST['menu'], true) : 0;
        $opt['quality']           = isset($_POST['quality'])           ? COM_applyFilter($_POST['quality'])    : '';
        $opt['flashvars']         = isset($_POST['flashvars'])         ? COM_applyFilter($_POST['flashvars'])  : '';
        $opt['height']            = COM_applyFilter($_POST['height'], true);
        $opt['width']             = COM_applyFilter($_POST['width'],  true);
        $opt['loop']              = isset($_POST['loop'])              ? COM_applyFilter($_POST['loop'], true) : 0;
        $opt['scale']             = isset($_POST['scale'])             ? COM_applyFilter($_POST['scale'])      : '';
        $opt['wmode']             = isset($_POST['wmode'])             ? COM_applyFilter($_POST['wmode'])      : '';
        $opt['allowscriptaccess'] = isset($_POST['allowscriptaccess']) ? COM_applyFilter($_POST['allowscriptaccess']) : '';
        $opt['bgcolor']           = isset($_POST['bgcolor'])           ? COM_applyFilter($_POST['bgcolor'])    : '';
        $opt['swf_version']       = isset($_POST['swf_version'])       ? COM_applyFilter($_POST['swf_version'], true) : 9;

        MG_savePBOption($media_id, 'play', $opt['play'], true);
        if ($opt['menu'] != '') {
            MG_savePBOption($media_id, 'menu', $opt['menu'], true);
        }
        MG_savePBOption($media_id, 'quality',           $opt['quality']);
        MG_savePBOption($media_id, 'flashvars',         $opt['flashvars']);
        MG_savePBOption($media_id, 'height',            $opt['height'], true);
        MG_savePBOption($media_id, 'width',             $opt['width'], true);
        MG_savePBOption($media_id, 'loop',              $opt['loop'], true);
        MG_savePBOption($media_id, 'scale',             $opt['scale']);
        MG_savePBOption($media_id, 'wmode',             $opt['wmode']);
        MG_savePBOption($media_id, 'allowscriptaccess', $opt['allowscriptaccess']);
        MG_savePBOption($media_id, 'bgcolor',           $opt['bgcolor']);
        MG_savePBOption($media_id, 'swf_version',       $opt['swf_version'], true);
    }
    if (isset($_POST['autoplay'])) {    // quicktime
        $opt['autoplay']    = COM_applyFilter($_POST['autoplay'], true);
        $opt['autoref']     = COM_applyFilter($_POST['autoref'], true);
        $opt['controller']  = COM_applyFilter($_POST['controller'], true);
        $opt['kioskmode']   = COM_applyFilter($_POST['kioskmode'], true);
        $opt['scale']       = COM_applyFilter($_POST['scale']);
        $opt['height']      = COM_applyFilter($_POST['height'], true);
        $opt['width']       = COM_applyFilter($_POST['width'], true);
        $opt['bgcolor']     = COM_applyFilter($_POST['bgcolor']);
        $opt['loop']        = COM_applyFilter($_POST['loop'], true);

        MG_savePBOption($media_id, 'autoref',    $opt['autoref'], true);
        MG_savePBOption($media_id, 'autoplay',   $opt['autoplay'], true);
        MG_savePBOption($media_id, 'controller', $opt['controller'], true);
        MG_savePBOption($media_id, 'kioskmode',  $opt['kioskmode'], true);
        MG_savePBOption($media_id, 'scale',      $opt['scale']);
        MG_savePBOption($media_id, 'height',     $opt['height'], true);
        MG_savePBOption($media_id, 'width',      $opt['width'], true);
        MG_savePBOption($media_id, 'bgcolor',    $opt['bgcolor'], true);
        MG_savePBOption($media_id, 'loop',       $opt['loop'], true);
    }

    if ($attachtn == 1 && $thumbnail != '') {
        require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-upload.php';
        $media_filename = DB_getItem($_TABLES['mg_media'], 'media_filename', 'media_id="' . DB_escapeString($media_id) . '"');
        $thumbFilename = $_MG_CONF['path_mediaobjects'] . 'tn/' . $media_filename[0] . '/tn_' . $media_filename;
        MG_attachThumbnail($album_id, $thumbnail, $thumbFilename);
    }

    if ($remove_old_tn == 1) {
        $media_filename = DB_getItem($_TABLES['mg_media'], 'media_filename', 'media_id="' . DB_escapeString($media_id) . '"');
        $tmpstr = 'tn/' . $media_filename[0] . '/tn_' . $media_filename;
        $ext = Media::getMediaExt($_MG_CONF['path_mediaobjects'] . $tmpstr);
        if (!empty($ext)) {
            @unlink($_MG_CONF['path_mediaobjects'] . $tmpstr . $ext);
        }
    }
    if ($queue) {
        COM_redirect($actionURL);
    } else {
        require_once $_CONF['path'] . 'plugins/mediagallery/include/rssfeed.php';
        MG_buildAlbumRSS($album_id);
        COM_redirect($actionURL);
    }
}
