<?php
// +--------------------------------------------------------------------------+
// | Media Gallery WKZ Plugin - Geeklog                                       |
// +--------------------------------------------------------------------------+
// | rssfeed.php                                                              |
// |                                                                          |
// | RSS Feed maintenance                                                     |
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

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/lib/feedcreator.class.php';

function MG_buildAlbumRSS($aid)
{
    global $_MG_CONF, $_CONF, $_TABLES;

    $feedpath = MG_getFeedPath();

    $fname = sprintf($_MG_CONF['rss_feed_name'] . "%06d.rss", $aid);
//    $feedname = $_MG_CONF['path_html'] . "rss/" . $fname;
    $feedname = $feedpath . '/' . $fname;

    $album_data = MG_getAlbumData($aid, array('enable_rss', 'album_title', 'album_desc',
                                              'tn_attached', 'podcast', 'rsschildren', 'owner_id'));

    if ($album_data['enable_rss'] != 1) {
        @unlink($feedname);
        return;
    }

    $rss = new UniversalFeedCreator();
    $rss->title = $_CONF['site_name'] . '::' . $album_data['album_title'];
    $rss->description = $album_data['album_desc'];
    $rss->descriptionTruncSize = 500;
    $rss->descriptionHtmlSyndicated = true;

    $rss->encoding = strtoupper ($_CONF['default_charset']);

    $imgurl = '';

    $image = new FeedImage();
    $image->title = $album_data['album_title'];
    $filename = MG_getAlbumCover($aid);
    if (substr($filename,0,3) == 'tn_') {
        foreach ($_MG_CONF['validExtensions'] as $ext) {
            if (file_exists($_MG_CONF['path_mediaobjects'] . 'tn/' . $filename[3] . '/' . 'tn_' . $filename . $ext)) {
                $imgurl = $_MG_CONF['mediaobjects_url'] . '/tn/' . $filename[3] . '/' . 'tn_' . $filename . $ext;
                break;
            }
        }
//        $imgurl = $_MG_CONF['mediaobjects_url'] . '/tn/' . $filename[3] . '/' . 'tn_' . $filename . '.jpg';
    } elseif ($filename != '') {
        foreach ($_MG_CONF['validExtensions'] as $ext) {
            if (file_exists($_MG_CONF['path_mediaobjects'] . 'tn/' . $filename[0] . '/' . $filename . $ext)) {
                $imgurl = $_MG_CONF['mediaobjects_url'] . '/tn/' . $filename[0] . '/' . $filename . $ext;
                break;
            }
        }
//        $imgurl = $_MG_CONF['mediaobjects_url'] . '/tn/' . $filename[0] . '/' . $filename . '.jpg';
    } else {
        $imgurl = '';
    }
    if ($album_data['tn_attached'] == 1) {
        foreach ($_MG_CONF['validExtensions'] as $ext) {
            if (file_exists($_MG_CONF['path_mediaobjects'] . 'covers/cover_' . $aid . $ext)) {
                $imgurl = $_MG_CONF['mediaobjects_url'] . '/covers/cover_' . $aid . $ext;
                break;
            }
        }
//      $imgurl = $_MG_CONF['mediaobjects_url'] . '/covers/cover_' . $aid . '.jpg';
    }

    $image->url = $imgurl;
    $image->link = $_MG_CONF['site_url'];
    $image->description = $album_data['album_title'];
    $image->descriptionTruncSize = 500;
    $image->descriptionHtmlSyndicated = true;
    $rss->image = $image;

    if ($album_data['podcast']) {
        //optional -- applies only if this is a podcast
        $rss->podcast = new Podcast();
        $rss->podcast->subtitle = $album_data['album_desc'];
        if ($album_data['owner_id'] != '') {
            $res = DB_query("SELECT * FROM {$_TABLES['users']} WHERE uid='" . $album_data['owner_id'] . "'");
            $uRow = DB_fetchArray($res);
            $rss->podcast->author = $uRow['username'];
            $rss->podcast->owner_name = $uRow['fullname'];
            $rss->podcast->owner_email = $_MG_CONF['hide_author_email'] == 0 ? $uRow['email'] : '';
        } else {
            $rss->podcast->author = 'anonymous';
            $rss->podcast->owner_name = 'anonymous';
        }
        $rss->podcast->summary = $album_data['album_desc'];
//      $rss->podcast->keywords = "php podcast rss itunes";
//      $rss->podcast->owner_email = "owner@example.com";

        // file this podcast under Technology->Computers
//      $podcast_tech_category = new PodcastCategory('Technology');
//      $podcast_comp_category = new PodcastCategory('Computers');
//      $podcast_tech_category->addCategory($podcast_comp_category);
//      $podcast_comp_category->addCategory($podcast_tech_category);
    }

    $rss->link = $_MG_CONF['site_url'];
//    $rss->syndicationURL = $_MG_CONF['site_url'] . '/rss/' . $fname;
    $rss->syndicationURL = $feedpath . '/' . $fname;

    MG_processAlbumFeedItems($rss, $aid, $album_data);
    if ($album_data['rsschildren']) {
        $children = MG_getAlbumChildren($aid);
        foreach ($children as $child) {
            $child_data = MG_getAlbumData($child, array('enable_rss','album_title','album_desc','tn_attached','podcast','rsschildren','owner_id',
                                                        'hidden','last_update','media_count','perm_anon'));
            if ($child_data['hidden'] != 1) {
                if ($_MG_CONF['rss_ignore_empty'] == 1 && $child_data['last_update'] != 0 && $child_data['last_update'] != '' && $child_data['media_count'] > 0) {
                    if ($_MG_CONF['rss_anonymous_only'] == 1 && $child_data['perm_anon'] > 0) {
                        MG_processAlbumFeedItems($rss, $child, $child_data);
                    }
                }
            }
        }
    }
    if ($album_data['podcast']) {
        $rss->saveFeed("PODCAST", $feedname, 0);
    } else {
        $rss->saveFeed($_MG_CONF['rss_feed_type'], $feedname ,0);
    }
    @chmod($feedname, 0664);
}

/*
 * pulls the individual items from an album
 */

function MG_processAlbumFeedItems(&$rss, $aid, &$album_data)
{
    global $_MG_CONF, $_CONF, $_TABLES;

    $sql = MG_buildMediaSql(array(
        'album_id'  => $aid,
        'sortorder' => 4  // ORDER BY m.media_time DESC
    ));
    $result = DB_query($sql);
    while ($row = DB_fetchArray($result)) {
        $item = new FeedItem();
        $item->title = $row['media_title'];
        $item->link =  $_MG_CONF['site_url'] . '/media.php?s=' . $row['media_id'];
        $description = '';
        $item->description = $description . $row['media_desc'];
        $item->descriptionTruncSize = 500;
        $item->descriptionHtmlSyndicated = true;

        if ($album_data['podcast']) {
            // optional -- applies only if this is a podcast
            $item->podcast = new PodcastItem();
            $item->podcast->enclosure_url = $_MG_CONF['mediaobjects_url'] . '/orig/' . $row['media_filename'][0] . '/' . $row['media_filename'] . '.' . $row['media_mime_ext'];
            $item->podcast->enclosure_length = @filesize($_MG_CONF['path_mediaobjects'] . 'orig/' . $row['media_filename'][0] . '/' . $row['media_filename'] . '.' . $row['media_mime_ext']);
            $item->podcast->enclosure_type = $row['mime_type'];
        }

        $item->date = strftime("%a, %d %b %Y %H:%M:%S %z", $row['media_time']);
        $item->source = $_CONF['site_url'];
        if ($row['artist'] != '') {
            $item->author = $row['artist'];
            $item->podcast->author = $row['artist'];
        }
        if ($row['media_keywords'] != '') {
            $item->podcast->keywords = $row['media_keywords'];
        }
/* ---
        if ( $row['media_user_id'] != '' && $row['media_user_id'] > 1 ) {
            $res = DB_query("SELECT * FROM {$_TABLES['users']} WHERE uid='" . $row['media_user_id'] . "'");
            $uRow = DB_fetchArray($res);
            $item->author = $_MG_CONF['hide_author_email'] == 0 ? $uRow['email'] : '' . ' (' . $uRow['fullname'] . ')';
        }
--- */
        $rss->addItem($item);
    }
    /*
     * Process the children albums
     */

    if ($album_data['rsschildren']) {
        $children = MG_getAlbumChildren($aid);
        foreach ($children as $child) {
            $child_data = MG_getAlbumData($child, array('enable_rss','album_title','album_desc','tn_attached','podcast','rsschildren','owner_id',
                                                        'hidden','last_update','media_count','perm_anon'));
            if ($child_data['hidden'] != 1) {
                if ($_MG_CONF['rss_ignore_empty'] == 1 && $child_data['last_update'] != 0 && $child_data['last_update'] != '' && $child_data['media_count'] > 0) {
                    if ($_MG_CONF['rss_anonymous_only'] == 1 && $child_data['perm_anon'] > 0) {
                        MG_processAlbumFeedItems($rss, $child, $child_data);
                    }
                }
            }
        }
    }
}

function MG_parseAlbumsRSS(&$rss, $aid)
{
    global $_MG_CONF, $_CONF, $_TABLES;

    if ($aid == 0) { // root_album
        $children = MG_getAlbumChildren($aid);
        foreach ($children as $child) {
            MG_parseAlbumsRSS($rss, $child);
        }
        return;
    }

    $album_data = MG_getAlbumData($aid, array('hidden','last_update','media_count','perm_anon', 
                                              'album_title','album_desc','owner_id'));

    if ($album_data['hidden'] != 1) {
        if ($_MG_CONF['rss_ignore_empty'] == 1 && $album_data['last_update'] != 0 && $album_data['last_update'] != '' && $album_data['media_count'] > 0) {
            if ($_MG_CONF['rss_anonymous_only'] == 1 && $album_data['perm_anon'] > 0) {
                $item = new FeedItem();
                $item->title = $album_data['album_title'];
                $item->link =  $_MG_CONF['site_url'] . '/album.php?aid=' . $aid;
                $description = '';
                $childCount = MG_getAlbumChildCount($aid);
                $description = 'Album contains ' . $album_data['media_count'] . ' item and ' . $childCount . ' sub-albums.<br' . XHTML . '><br' . XHTML . '>';
                $filename = MG_getAlbumCover($aid);
                if (substr($filename, 0, 3) == 'tn_') {
                    foreach ($_MG_CONF['validExtensions'] as $ext) {
                        if (file_exists($_MG_CONF['path_mediaobjects'] . 'tn/' . $filename[3] . '/' . $filename . $ext)) {
                            $description .= '<img src="' . $_MG_CONF['mediaobjects_url'] . '/tn/' . $filename[3] . '/' . $filename . $ext . '" align="left"' . XHTML . '>';
                            break;
                        }
                    }
//                    $description .= '<img src="' . $_MG_CONF['mediaobjects_url'] . '/tn/' . $filename[3] . '/' . $filename . '.jpg" align="left"' . XHTML . '>';
                } elseif ($filename != '') {
                    foreach ($_MG_CONF['validExtensions'] as $ext) {
                        if (file_exists($_MG_CONF['path_mediaobjects'] . 'tn/' . $filename[0] . '/' . $filename . $ext)) {
                            $description .= '<img src="' . $_MG_CONF['mediaobjects_url'] . '/tn/' . $filename[0] . '/' . $filename . $ext . '" align="left"' . XHTML . '>';
                            break;
                        }
                    }
//                    $description .= '<img src="' . $_MG_CONF['mediaobjects_url'] . '/tn/' . $filename[0] . '/' . $filename . '.jpg" align="left"' . XHTML . '>';
                }
                $description .= $album_data['album_desc'];
                $item->description = $description;
                //optional
                $item->descriptionTruncSize = 500;
                $item->descriptionHtmlSyndicated = true;

                $item->date = strftime("%a, %d %b %Y %H:%M:%S %z", $album_data['last_update']);
                $item->source = $_CONF['site_url'];
                if ($album_data['owner_id'] != '') {
                    $username = DB_getItem($_TABLES['users'], 'username', "uid={$album_data['owner_id']}");
                    $item->author = $username;
                }
                $rss->addItem($item);
            }
        }
    }
    $children = MG_getAlbumChildren($aid);
    foreach ($children as $child) {
        MG_parseAlbumsRSS($rss, $child);
    }
}


function MG_buildFullRSS()
{
    global $_MG_CONF, $_CONF, $_TABLES;

    $feedpath = MG_getFeedPath();

    if ($_MG_CONF['rss_full_enabled'] != 1) {
        @unlink($feedpath . $_MG_CONF['rss_feed_name'] . '.rss');
        return;
    }
    $rss = new UniversalFeedCreator();
    $rss->title = $_CONF['site_name'] . ' Media Gallery RSS Feed';
    $rss->description = $_CONF['site_slogan'];
    $rss->descriptionTruncSize = 500;
    $rss->descriptionHtmlSyndicated = true;
    $rss->encoding = strtoupper ($_CONF['default_charset']);
    $rss->link = $_CONF['site_url'];
    $rss->syndicationURL = $_CONF['site_url'] . $_SERVER["PHP_SELF"]; // ‚ ‚â‚µ‚¢B
    MG_parseAlbumsRSS($rss, 0);
    // valid format strings are: RSS0.91, RSS1.0, RSS2.0, PIE0.1 (deprecated),
    // MBOX, OPML, ATOM, ATOM0.3, HTML, JS
    $rss->saveFeed($_MG_CONF['rss_feed_type'], $feedpath . $_MG_CONF['rss_feed_name'] . '.rss', 0);
    @chmod($feedpath . $_MG_CONF['rss_feed_name'] . '.rss', 0664);

    return;
}

function MG_buildNewRSS() {

}
?>