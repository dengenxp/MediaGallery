<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | profile.php                                                              |
// |                                                                          |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2015 by the following authors:                             |
// |                                                                          |
// | Yoshinori Tahara       taharaxp AT gmail DOT com                         |
// |                                                                          |
// | Based on the Media Gallery Plugin for glFusion CMS                       |
// | Copyright (C) 2002-2008 by the following authors:                        |
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

require_once $_CONF['path'] . 'plugins/mediagallery/include/classMedia.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';

// display user info in profile

function MG_profileblocksdisplay($uid)
{
    global $_TABLES, $_MG_CONF, $_CONF, $LANG_MG10, $_USER;

    $retval = '';

    if ($_MG_CONF['profile_hook'] != 1) return '';
    if ((!isset($_USER['uid']) || $_USER['uid'] < 2) && $_MG_CONF['loginrequired'] == 1) return '';
    if (empty($uid)) return '';

    $username = DB_getItem($_TABLES['users'], 'username', 'uid=' . intval($uid));
    if (empty($username)) return '';

    $T = COM_newTemplate(MG_getTemplatePath(0));
    $T->set_file('mblock', 'profile_media.thtml');
    $T->set_block('mblock', 'itemRow', 'iRow');
    $T->set_var('start_block_last10mediaitems', COM_startBlock($LANG_MG10['last_10'] . $username));
    $T->set_var('start_block_useralbums', COM_startBlock($LANG_MG10['albums_owned'] . $username));
    $T->set_var('lang_thumbnail', $LANG_MG10['thumbnail']);
    $T->set_var('lang_title', $LANG_MG10['title']);
    $T->set_var('lang_album', $LANG_MG10['album']);
    $T->set_var('lang_album_description', $LANG_MG10['album_desc']);
    $T->set_var('lang_upload_date', $LANG_MG10['upload_date']);
    $T->set_var('end_block', COM_endBlock());

    $sql = "SELECT a.album_id,m.media_upload_time,m.media_id,m.media_filename,m.mime_type,"
         . "m.media_mime_ext,m.media_title,m.remote_media,m.media_type,m.media_tn_attached "
         . "FROM {$_TABLES['mg_albums']} AS a LEFT JOIN {$_TABLES['mg_media_albums']} AS ma "
         . "ON a.album_id=ma.album_id LEFT JOIN {$_TABLES['mg_media']} AS m ON ma.media_id=m.media_id "
         . "WHERE m.media_user_id=" . intval($uid) . " AND a.hidden=0 " . COM_getPermSQL('and')
         . " ORDER BY m.media_upload_time DESC LIMIT 5";
    $result = DB_query($sql);
    $class = 0;
    $mCount = 0;
    while ($row = DB_fetchArray($result)) {
        $album_id = $row['album_id'];
        $album_data = MG_getAlbumData($album_id, array('album_title'));
        $album_title = strip_tags($album_data['album_title']);
        $upload_time = MG_getUserDateTimeFormat($row['media_upload_time']);
        $url_media = $_MG_CONF['site_url'] . '/media.php?s=' . $row['media_id'];
        $url_album = $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id;
        $tn_size = 10;
        list($url_thumb, $p_thumb, $msize) = Media::getThumbInfo($row, $tn_size);
        $atnsize = '';
        if ($msize != false) {
            list($newwidth, $newheight) = Media::getImageWH($msize[0], $msize[1], 50, 50);
            $atnsize = 'width="' . $newwidth . '" height="' . $newheight . '" ';
        }

        $T->set_var('mediaitem_image', '<img src="' . $url_thumb . '" alt="" ' . $atnsize
                  . 'style="border:none;vertical-align:bottom;"' . XHTML . '>');
        $T->set_var('mediaitem_begin_href', '<a href="' . $url_media . '">');
        $T->set_var('mediaitem_title', strip_tags($row['media_title']));
        $T->set_var('mediaitem_end_href', '</a>');
        $T->set_var('mediaitem_album_begin_href', '<a href="' . $url_album . '">');
        $T->set_var('mediaitem_album_title', $album_title);
        $T->set_var('mediaitem_date', $upload_time[0]);
        $T->set_var('rowclass', ($class % 2) ? '1' : '2');
        $T->parse('iRow', 'itemRow', true);
        $class++;
        $mCount++;
    }
    if ($mCount != 0) {
        $retval .= $T->finish($T->parse('output', 'mblock'));
    }

    $T = COM_newTemplate(MG_getTemplatePath(0));
    $T->set_file('ablock', 'profile_album.thtml');
    $T->set_block('ablock', 'itemRow', 'iRow');
    $T->set_var('start_block_useralbums', COM_startBlock($LANG_MG10['albums_owned'] . $username));
    $T->set_var('lang_thumbnail', $LANG_MG10['thumbnail']);
    $T->set_var('lang_album', $LANG_MG10['album']);
    $T->set_var('lang_album_description', $LANG_MG10['album_desc']);
    $T->set_var('end_block', COM_endBlock());

    $sql = "SELECT album_id,album_title,album_desc,tn_attached "
         . "FROM " . $_TABLES['mg_albums']
         . " WHERE owner_id=" . intval($uid)
         . " AND hidden=0 " . COM_getPermSQL('and')
         . " ORDER BY last_update DESC LIMIT 10";
    $result = DB_query($sql);
    $class = 0;
    $aCount = 0;
    while ($row = DB_fetchArray($result)) {
        $aid        = $row['album_id'];
        $url_album  = $_MG_CONF['site_url'] . '/album.php?aid=' . $row['album_id'];

        $url_thumb = '';
        $msize = false;

        if ($row['tn_attached'] == 1) {
            list($url_thumb, $msize) = MG_getImageUrl('covers/cover_' . $row['album_id']);
        } else {
            $cover_file = MG_getAlbumCover($aid);
            if ($cover_file != '') {
                $offset = (substr($cover_file, 0, 3) == 'tn_') ? 3 : 0;
                list($url_thumb, $msize) = MG_getImageUrl('tn/' . $cover_file[$offset] . '/' . $cover_file);
            }
        }

        if ($msize == false || $url_thumb == '') {
            $url_thumb = $_MG_CONF['mediaobjects_url'] . '/empty.png';
            $msize = getimagesize($_MG_CONF['path_mediaobjects'] . 'empty.png');
        }

        $atnsize = '';
        if ($msize != false) {
            list($newwidth, $newheight) = Media::getImageWH($msize[0], $msize[1], 50, 50);
            $atnsize = 'width="' . $newwidth . '" height="' . $newheight . '" ';
        }

        $T->set_var('album_cover', '<img src="' . $url_thumb . '" alt="" '
                  . $atnsize . 'style="border:none;vertical-align:bottom;"' . XHTML . '>');
        $T->set_var('album_begin_href', '<a href="' . $url_album . '">');
        $T->set_var('album_title', strip_tags($row['album_title']));
        $T->set_var('album_end_href', '</a>');
        $T->set_var('album_desc', strip_tags($row['album_desc']));
        $T->set_var('rowclass', ($class % 2) ? '1' : '2');
        $T->parse('iRow', 'itemRow', true);
        $class++;
        $aCount++;
    }
    if ($aCount != 0) {
        $retval .= $T->finish($T->parse('output', 'ablock'));
    }
    return $retval;
}
