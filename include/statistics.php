<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | statistics.php                                                           |
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

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';

/**
* Shows the statistics for the plugin on stats.php.  If
* $showsitestats is 1 then we are to only print the overall stats in the 'site
* statistics' box otherwise we show the detailed stats for the plugin
*
* Universal Plugin:  Modify/uncomment use it
*
* @param    int showsitestate   Flag to let us know which stats to get
* @return   string  returns formatted HTML to insert in stats page
*
*/
function MG_showstats($showsitestats)
{
    global $_CONF, $_MG_CONF, $_TABLES, $LANG_MG00, $_USER;

    if (COM_isAnonUser() && $_MG_CONF['loginrequired'] == 1) {
        return;
    }

    $retval='';
    $sql = "SELECT a.album_id,m.media_original_filename,m.media_title,m.media_id,m.media_rating "
         . "FROM {$_TABLES['mg_albums']} AS a "
         . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON a.album_id=ma.album_id "
         . "LEFT JOIN {$_TABLES['mg_media']} AS m ON ma.media_id=m.media_id "
         . "WHERE m.media_rating <> 0 AND a.hidden=0 " . COM_getPermSQL('and')
         . " ORDER BY m.media_rating DESC LIMIT 10";
    $result = DB_query($sql);
    $numrows = DB_numRows($result);
    if ($numrows > 0) {
        $header_arr = array(
            array('text'         => $LANG_MG00['media_col_header'],
                  'field'        => 'title',
                  'header_class' => 'stats-header-title'
            ),
            array('text'         => $LANG_MG00['rating'],
                  'field'        => 'rating',
                  'field_class'  => 'stats-list-count'
            )
        );
        $data_arr = array();
        $text_arr = array('has_menu' => false,
                          'title'    => $LANG_MG00['stats_rate_title'],
        );
        for ($i = 0; $i < $numrows; $i++) {
            $A = DB_fetchArray($result);
            if ($A['media_title'] == '' || $A['media_title'] == " ") {
                if ($A['media_original_filename'] == '') {
                    $album_data = MG_getAlbumData($A['album_id'], array('album_title'));
                    $title = '<b>' . $LANG_MG00['album'] . '</b>'
                           . '<em>' . strip_tags($album_data['album_title'])
                           . ' - ' . $LANG_MG00['no_title'] . '</em>';
                } else {
                    $title = $A['media_original_filename'];
                }
            } else {
                $title = strip_tags($A['media_title']);
            }
            $S['title'] = '<a href="' . $_MG_CONF['site_url'] . '/media.php?s=' . $A['media_id'] . '">' . $title . '</a>';
            $S['rating'] = @number_format($A['media_rating']/2, 2) . '/5.00';
            $data_arr[] = $S;
        }
        $retval .= ADMIN_simpleList("", $header_arr, $text_arr, $data_arr);
    }

    $sql = "SELECT DISTINCT m.media_id,m.media_title,ma.album_id,m.media_original_filename,m.media_views "
         . "FROM {$_TABLES['mg_media']} AS m "
         . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON m.media_id=ma.media_id "
         . "WHERE m.media_views > 0 "
         . "ORDER BY m.media_views DESC LIMIT 10";
    $result = DB_query($sql);
    $numrows = DB_numRows($result);
    if ($numrows > 0) {
        $header_arr = array(
            array('text'         => $LANG_MG00['media_col_header'],
                  'field'        => 'title',
                  'header_class' => 'stats-header-title'
            ),
            array('text'         => $LANG_MG00['hitsmsg'],
                  'field'        => 'views',
                  'field_class'  => 'stats-list-count'
            )
        );
        $data_arr = array();
        $text_arr = array('has_menu' => false,
                          'title'    => $LANG_MG00['stats_title'],
        );
        for ($i = 0; $i < $numrows; $i++) {
            $A = DB_fetchArray($result);
            $album_data = MG_getAlbumData($A['album_id'], array('album_title'), true);
            if ($A['media_id'] != '' && $album_data['access'] > 0) {
                if ($A['media_title'] == '' || $A['media_title'] == " ") {
                    if ($A['media_original_filename'] == '') {
                        $title = '<b>' . $LANG_MG00['album'] . '</b>'
                               . '<em>' . strip_tags($album_data['album_title'])
                               . ' - ' . $LANG_MG00['no_title'] . '</em>';
                    } else {
                        $title = $A['media_original_filename'];
                    }
                } else {
                    $title = strip_tags($A['media_title']);
                }
                $S['title'] = '<a href="' . $_MG_CONF['site_url'] . '/media.php?s=' . $A['media_id'] . '">' . $title . '</a>';
                $S['views'] = $A['media_views'];
                $data_arr[] = $S;
            }
        }
        $retval .= ADMIN_simpleList("", $header_arr, $text_arr, $data_arr);
    }
    return $retval;
}
