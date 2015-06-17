<?php

// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | install_defaults.php                                                     |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2015 by the following authors:                             |
// |                                                                          |
// | Yoshinori Tahara       taharaxp AT gmail DOT com                         |
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

/*
 * Media Gallery plugin default settings
 *
 * Initial Installation Defaults used when loading the online configuration
 * records. These settings are only used during the initial installation
 * and not referenced any more once the plugin is installed
 *
 */

global $_MG_DEFAULT, $_TABLES;

$admin_group_id  = DB_getItem($_TABLES['groups'], 'grp_id', "grp_name = 'mediagallery Admin'");

$_MG_DEFAULT = array(

    // General Options
    'gallery_only'               => '0',
    'loginrequired'              => '0',
    'htmlallowed'                => '0',
    'usage_tracking'             => '0',
    'whatsnew'                   => '1',
    'title_length'               => '28',
    'whatsnew_time'              => '7',
    'preserve_filename'          => '1',
    'discard_original'           => '0',
    'verbose'                    => '0',
    'disable_whatsnew_comments'  => '1',
    'profile_hook'               => '1',

    // Autotag Defaults
    'at_border'                  => '1',
    'at_align'                   => 'auto',
    'at_width'                   => '0',
    'at_height'                  => '0',
    'at_src'                     => 'tn',
    'at_autoplay'                => '0',
    'at_enable_link'             => '1',
    'at_delay'                   => '5',
    'at_showtitle'               => '0',

    // RSS Feeds
    'rss_full_enabled'           => '1',
    'rss_feed_type'              => 'RSS2.0',
    'rss_ignore_empty'           => '1',
    'rss_anonymous_only'         => '1',
    'hide_author_email'          => '0',
    'rss_feed_name'              => 'mgmedia',

    // Display Options
    'dfid'                       => '19', // ISO 8601
    'displayblocks'              => '0',
    'subalbum_select'            => '0',
    'jpg_orig_quality'           => '85',
    'jpg_quality'                => '85',
    'tn_jpg_quality'             => '85',
    'gallery_tn_size'            => '1',
    'gallery_tn_height'          => '200',
    'gallery_tn_width'           => '200',
    'enable_media_id'            => '1',
    'full_in_popup'              => '0',
    'seperator'                  => '&gt;',
    'use_flowplayer'             => '0',
    'custom_image_height'        => '412',
    'custom_image_width'         => '550',
    'popup_from_album'           => '0', // added
    'autotag_caption'            => '0', // added
    'random_width'               => '120',
    'random_skin'                => 'default', // changed
    'truncate_breadcrumb'        => '0',

    // Search Results Options
    'search_columns'             => '3',
    'search_rows'                => '4',
    'search_playback_type'       => '0',
    'search_enable_views'        => '1',
    'search_enable_rating'       => '1',
    'search_album_skin'          => 'default', // added // Set a theme of template to use at the search results page
    'search_frame_skin'          => 'default', // added // Set a frame to use for thumbnails at the search results page
    'search_tn_size'             => '1',       // added // Set the size of thumbnails at the search results page


    // Batch Options
    'def_refresh_rate'           => '30',
    'def_item_limit'             => '10',
    'def_time_limit'             => '90',

    // User Prefs
    'up_display_rows_enabled'    => '1',
    'up_display_columns_enabled' => '1',
    'up_av_playback_enabled'     => '1',
    'up_thumbnail_size_enabled'  => '1',

    // Graphics Package
    'jhead_enabled'              => '0', // added
    'jhead_path'                 => '',  // added
    'jpegtran_enabled'           => '0', // added
    'jpegtran_path'              => '',  // added
    'ffmpeg_enabled'             => '0', // added
    'ffmpeg_path'                => '',  // added
    'zip_enabled'                => '0', // added
    'zip_path'                   => '',  // added
    'tmp_path'                   => $_CONF['path'] . 'plugins/mediagallery/tmp/',
    'ftp_path'                   => $_CONF['path'] . 'plugins/mediagallery/uploads/',

    // Root Album
    'root_album_name'            => 'Root Album', // added
    'album_display_columns'      => '3',
    'album_display_rows'         => '4',
    'indextheme'                 => 'default', // added
    'indexskin'                  => 'default', // changed

    // Album Attributes
    'ad_skin'                    => 'default',
    'ad_enable_comments'         => '1',
    'ad_exif_display'            => '0',
    'ad_enable_rating'           => '1',
    'ad_enable_album_views'      => '1',
    'ad_enable_views'            => '1',
    'ad_enable_keywords'         => '1',
    'ad_display_album_desc'      => '0',
    'ad_filename_title'          => '0',
    'ad_enable_rss'              => '0',
    'ad_rsschildren'             => '1',
    'ad_podcast'                 => '0',
    'ad_mp3ribbon'               => '0',
    'ad_enable_sort'             => '1',
    'ad_album_sort_order'        => '0',
    'ad_playback_type'           => '2',
    'ad_enable_slideshow'        => '1',
    'ad_enable_random'           => '1',
    'ad_albums_first'            => '1',
    'ad_allow_download'          => '0',
    'ad_full_display'            => '0',
    'ad_tn_size'                 => '1',
    'ad_tn_width'                => '200',
    'ad_tn_height'               => '200',
    'ad_max_image_width'         => '0',
    'ad_max_image_height'        => '0',
    'ad_max_filesize'            => '0',
    'ad_display_image_size'      => '2',
    'ad_display_rows'            => '4',
    'ad_display_columns'         => '3',
    'ad_image_skin'              => 'default', // changed
    'ad_display_skin'            => 'default', // changed
    'ad_album_skin'              => 'default', // changed

    'ad_wm_auto'                 => '0',
    'ad_wm_opacity'              => '10',
    'ad_wm_location'             => '1',
    'ad_wm_id'                   => '0',
//    'ad_valid_formats'           => '983039', // maybe removed

    'ad_valid_format_jpg'        => '1', // added
    'ad_valid_format_png'        => '1', // added
    'ad_valid_format_tif'        => '1', // added
    'ad_valid_format_gif'        => '1', // added
    'ad_valid_format_bmp'        => '1', // added
    'ad_valid_format_tga'        => '1', // added
    'ad_valid_format_psd'        => '1', // added
    'ad_valid_format_mp3'        => '1', // added
    'ad_valid_format_ogg'        => '1', // added
    'ad_valid_format_asf'        => '1', // added
    'ad_valid_format_swf'        => '1', // added
    'ad_valid_format_mov'        => '1', // added
    'ad_valid_format_mp4'        => '1', // added
    'ad_valid_format_mpg'        => '1', // added
    'ad_valid_format_flv'        => '1', // added
    'ad_valid_format_rflv'       => '1', // added
    'ad_valid_format_emb'        => '1', // added
    'ad_valid_format_zip'        => '1', // added
    'ad_valid_format_other'      => '1', // added

    'ad_member_uploads'          => '0',
    'ad_moderate'                => '0',
    'ad_mod_group_id'            => $admin_group_id, // added
    'ad_email_mod'               => '0',

    'ad_group_id'                => $admin_group_id, // added
    'ad_permissions'             => array(3, 2, 2, 2), // added 'ad_perm_owner', 'ad_perm_group', 'ad_perm_members', 'ad_perm_anon'

    // Windows Media Player
    'asf_autostart'              => '1',
    'asf_enablecontextmenu'      => '1',
    'asf_stretchtofit'           => '1',
    'asf_showstatusbar'          => '1',
    'asf_uimode'                 => 'full',
    'asf_playcount'              => '9999',
    'asf_bgcolor'                => '#FFFFFF',
    'asf_width'                  => '640',
    'asf_height'                 => '480',

    // QuickTime Player
    'mov_autoref'                => '1',
    'mov_autoplay'               => '1',
    'mov_controller'             => '1',
    'mov_kioskmode'              => '1',
    'mov_scale'                  => 'tofit',
    'mov_loop'                   => '0',
    'mov_bgcolor'                => '#FFFFFF',
    'mov_width'                  => '640',
    'mov_height'                 => '480',

    // MP3 Playback
    'mp3_autostart'              => '1',
    'mp3_enablecontextmenu'      => '1',
    'mp3_showstatusbar'          => '1',
    'mp3_loop'                   => '0',
    'mp3_uimode'                 => '0',

    // Flash Media Player
    'swf_play'                   => '1',
    'swf_menu'                   => '0',
    'swf_scale'                  => 'showall',
    'swf_wmode'                  => 'transparent',
    'swf_allowscriptaccess'      => 'sameDomain',
    'swf_quality'                => 'high',
    'swf_loop'                   => '0',
    'swf_bgcolor'                => '#FFFFFF',
    'swf_width'                  => '640',
    'swf_height'                 => '480',
    'swf_flashvars'              => '',
    'swf_version'                => '6',

    // Member Albums Defaults
    'member_albums'              => '0',
    'allow_remote'               => '0', // added
    'member_use_fullname'        => '0', // added
    'feature_member_album'       => '0', // added
    'member_quota'               => '0', // added
    'member_auto_create'         => '0',
    'member_create_new'          => '0',
    'member_album_root'          => '0',
    'member_album_archive'       => '0',

//    'member_valid_formats'       => '65535', // maybe removed
    'member_valid_format_jpg'    => '1', // added
    'member_valid_format_png'    => '1', // added
    'member_valid_format_tif'    => '1', // added
    'member_valid_format_gif'    => '1', // added
    'member_valid_format_bmp'    => '1', // added
    'member_valid_format_tga'    => '1', // added
    'member_valid_format_psd'    => '1', // added
    'member_valid_format_mp3'    => '1', // added
    'member_valid_format_ogg'    => '1', // added
    'member_valid_format_asf'    => '1', // added
    'member_valid_format_swf'    => '1', // added
    'member_valid_format_mov'    => '1', // added
    'member_valid_format_mp4'    => '1', // added
    'member_valid_format_mpg'    => '1', // added
    'member_valid_format_flv'    => '1', // added
    'member_valid_format_rflv'   => '1', // added
    'member_valid_format_emb'    => '1', // added
    'member_valid_format_zip'    => '1', // added
    'member_valid_format_other'  => '1', // added

    'member_enable_random'       => '1',
    'member_max_width'           => '0',
    'member_max_height'          => '0',
    'member_max_filesize'        => '0',

    'member_uploads'             => '0',
    'member_moderate'            => '0',
    'member_mod_group_id'        => $admin_group_id, // added
    'member_email_mod'           => '0',

    'member_permissions'         => array(3, 3, 0, 0), // added 'member_perm_owner', 'member_perm_group', 'member_perm_members', 'member_perm_anon'

    'graphicspackage'            => '2',
    'graphicspackage_path'       => '/usr/local/bin/',
    'mp3_player'                 => '0',
    'up_mp3_player_enabled'      => '1',
);

/**
* Initialize Media Gallery plugin configuration
*
* Creates the database entries for the configuation if they don't already
* exist. Initial values will be taken from $_MG_CONF if available (e.g. from
* an old config.php), uses $_MG_DEFAULT otherwise.
*
* @return   boolean     true: success; false: an error occurred
*
*/
function plugin_initconfig_mediagallery()
{
    global $_MG_CONF, $_MG_DEFAULT, $_TABLES;

    if (is_array($_MG_CONF) && (count($_MG_CONF) > 1)) {
        $_MG_DEFAULT = array_merge($_MG_DEFAULT, $_MG_CONF);
    }

    $c = config::get_instance();
    $n = 'mediagallery';
    $o = 1;
    if (!$c->group_exists($n)) {

        $c->add('sg_main',               NULL,                                     'subgroup', 0,  0, NULL, 0,    true, $n, 0);
        // ----------------------------------
        $c->add('tab_main',              NULL,                                     'tab',      0,  0, NULL, 0,    true, $n, 0);
        $c->add('fs_main',               NULL,                                     'fieldset', 0,  0, NULL, 0,    true, $n, 0);
        $c->add('gallery_only',          $_MG_DEFAULT['gallery_only'],             'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('loginrequired',         $_MG_DEFAULT['loginrequired'],            'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('htmlallowed',           $_MG_DEFAULT['htmlallowed'],              'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('usage_tracking',        $_MG_DEFAULT['usage_tracking'],           'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('whatsnew',              $_MG_DEFAULT['whatsnew'],                 'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('title_length',          $_MG_DEFAULT['title_length'],             'text',     0,  0, 0,    $o++, true, $n, 0);
        $c->add('whatsnew_time',         $_MG_DEFAULT['whatsnew_time'],            'text',     0,  0, 0,    $o++, true, $n, 0);
        $c->add('preserve_filename',     $_MG_DEFAULT['preserve_filename'],        'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('discard_original',      $_MG_DEFAULT['discard_original'],         'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('verbose',               $_MG_DEFAULT['verbose'],                  'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('disable_whatsnew_comments', $_MG_DEFAULT['disable_whatsnew_comments'], 'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('profile_hook',          $_MG_DEFAULT['profile_hook'],             'select',   0,  0, 0,    $o++, true, $n, 0);

        // ----------------------------------
        $c->add('fs_autotag',            NULL,                                     'fieldset', 0,  1, NULL, 0,    true, $n, 0);
        $c->add('at_border',             $_MG_DEFAULT['at_border'],                'select',   0,  1, 0,    $o++, true, $n, 0);
        $c->add('at_align',              $_MG_DEFAULT['at_align'],                 'select',   0,  1, 7,    $o++, true, $n, 0);
        $c->add('at_width',              $_MG_DEFAULT['at_width'],                 'text',     0,  1, 0,    $o++, true, $n, 0);
        $c->add('at_height',             $_MG_DEFAULT['at_height'],                'text',     0,  1, 0,    $o++, true, $n, 0);
        $c->add('at_src',                $_MG_DEFAULT['at_src'],                   'select',   0,  1, 8,    $o++, true, $n, 0);
        $c->add('at_autoplay',           $_MG_DEFAULT['at_autoplay'],              'select',   0,  1, 0,    $o++, true, $n, 0);
        $c->add('at_enable_link',        $_MG_DEFAULT['at_enable_link'],           'select',   0,  1, 9,    $o++, true, $n, 0);
        $c->add('at_delay',              $_MG_DEFAULT['at_delay'],                 'text',     0,  1, 0,    $o++, true, $n, 0);
        $c->add('at_showtitle',          $_MG_DEFAULT['at_showtitle'],             'select',   0,  1, 0,    $o++, true, $n, 0);
        // ----------------------------------
        $c->add('fs_rssfeed',            NULL,                                     'fieldset', 0,  2, NULL, 0,    true, $n, 0);
        $c->add('rss_full_enabled',      $_MG_DEFAULT['rss_full_enabled'],         'select',   0,  2, 0,    $o++, true, $n, 0);
        $c->add('rss_feed_type',         $_MG_DEFAULT['rss_feed_type'],            'select',   0,  2, 30,   $o++, true, $n, 0);
        $c->add('rss_ignore_empty',      $_MG_DEFAULT['rss_ignore_empty'],         'select',   0,  2, 0,    $o++, true, $n, 0);
        $c->add('rss_anonymous_only',    $_MG_DEFAULT['rss_anonymous_only'],       'select',   0,  2, 0,    $o++, true, $n, 0);
        $c->add('hide_author_email',     $_MG_DEFAULT['hide_author_email'],        'select',   0,  2, 0,    $o++, true, $n, 0);
        $c->add('rss_feed_name',         $_MG_DEFAULT['rss_feed_name'],            'text',     0,  2, 0,    $o++, true, $n, 0);

        // ----------------------------------
        $c->add('tab_display',           NULL,                                     'tab',      0,  0, NULL, 0,    true, $n, 1);
        $c->add('fs_display',            NULL,                                     'fieldset', 0,  0, NULL, 0,    true, $n, 1);
        $c->add('dfid',                  $_MG_DEFAULT['dfid'],                     'select',   0,  0, NULL, $o++, true, $n, 1);
        $c->add('displayblocks',         $_MG_DEFAULT['displayblocks'],            'select',   0,  0, 10,   $o++, true, $n, 1);
        $c->add('subalbum_select',       $_MG_DEFAULT['subalbum_select'],          'select',   0,  0, 0,    $o++, true, $n, 1);
        $c->add('jpg_orig_quality',      $_MG_DEFAULT['jpg_orig_quality'],         'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('jpg_quality',           $_MG_DEFAULT['jpg_quality'],              'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('tn_jpg_quality',        $_MG_DEFAULT['tn_jpg_quality'],           'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('gallery_tn_size',       $_MG_DEFAULT['gallery_tn_size'],          'select',   0,  0, 11,   $o++, true, $n, 1);
        $c->add('gallery_tn_height',     $_MG_DEFAULT['gallery_tn_height'],        'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('gallery_tn_width',      $_MG_DEFAULT['gallery_tn_width'],         'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('enable_media_id',       $_MG_DEFAULT['enable_media_id'],          'select',   0,  0, 0,    $o++, true, $n, 1);
        $c->add('full_in_popup',         $_MG_DEFAULT['full_in_popup'],            'select',   0,  0, 0,    $o++, true, $n, 1);
        $c->add('seperator',             $_MG_DEFAULT['seperator'],                'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('use_flowplayer',        $_MG_DEFAULT['use_flowplayer'],           'select',   0,  0, 13,   $o++, true, $n, 1);
        $c->add('custom_image_height',   $_MG_DEFAULT['custom_image_height'],      'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('custom_image_width',    $_MG_DEFAULT['custom_image_width'],       'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('popup_from_album',      $_MG_DEFAULT['popup_from_album'],         'select',   0,  0, 0,    $o++, true, $n, 1);
        $c->add('autotag_caption',       $_MG_DEFAULT['autotag_caption'],          'select',   0,  0, 0,    $o++, true, $n, 1);
        $c->add('random_width',          $_MG_DEFAULT['random_width'],             'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('random_skin',           $_MG_DEFAULT['random_skin'],              'select',   0,  0, NULL, $o++, true, $n, 1);
        $c->add('truncate_breadcrumb',   $_MG_DEFAULT['truncate_breadcrumb'],      'text',     0,  0, 0,    $o++, true, $n, 1);
        // ----------------------------------
        $c->add('fs_searchresults',      NULL,                                     'fieldset', 0,  1, NULL, 0,    true, $n, 1);
        $c->add('search_columns',        $_MG_DEFAULT['search_columns'],           'text',     0,  1, 0,    $o++, true, $n, 1);
        $c->add('search_rows',           $_MG_DEFAULT['search_rows'],              'text',     0,  1, 0,    $o++, true, $n, 1);
        $c->add('search_playback_type',  $_MG_DEFAULT['search_playback_type'],     'select',   0,  1, 14,   $o++, true, $n, 1);
        $c->add('search_enable_views',   $_MG_DEFAULT['search_enable_views'],      'select',   0,  1, 0,    $o++, true, $n, 1);
        $c->add('search_enable_rating',  $_MG_DEFAULT['search_enable_rating'],     'select',   0,  1, 0,    $o++, true, $n, 1);
        $c->add('search_album_skin',     $_MG_DEFAULT['search_album_skin'],        'select',   0,  1, NULL, $o++, true, $n, 1);
        $c->add('search_frame_skin',     $_MG_DEFAULT['search_frame_skin'],        'select',   0,  1, NULL, $o++, true, $n, 1);
        $c->add('search_tn_size',        $_MG_DEFAULT['search_tn_size'],           'select',   0,  1, 31,   $o++, true, $n, 1);

        // ----------------------------------
        $c->add('tab_batch',             NULL,                                     'tab',      0,  0, NULL, 0,    true, $n, 2);
        $c->add('fs_batch',              NULL,                                     'fieldset', 0,  0, NULL, 0,    true, $n, 2);
        $c->add('def_refresh_rate',      $_MG_DEFAULT['def_refresh_rate'],         'text',     0,  0, 0,    $o++, true, $n, 2);
        $c->add('def_item_limit',        $_MG_DEFAULT['def_item_limit'],           'text',     0,  0, 0,    $o++, true, $n, 2);
        $c->add('def_time_limit',        $_MG_DEFAULT['def_time_limit'],           'text',     0,  0, 0,    $o++, true, $n, 2);

        // ----------------------------------
        $c->add('tab_userprefs',         NULL,                                     'tab',      0,  0, NULL, 0,    true, $n, 3);
        $c->add('fs_userprefs',          NULL,                                     'fieldset', 0,  0, NULL, 0,    true, $n, 3);
        $c->add('up_display_rows_enabled',    $_MG_DEFAULT['up_display_rows_enabled'],    'select',   0,  0, 0,    $o++, true, $n, 3);
        $c->add('up_display_columns_enabled', $_MG_DEFAULT['up_display_columns_enabled'], 'select',   0,  0, 0,    $o++, true, $n, 3);
        $c->add('up_av_playback_enabled',     $_MG_DEFAULT['up_av_playback_enabled'],     'select',   0,  0, 0,    $o++, true, $n, 3);
        $c->add('up_thumbnail_size_enabled',  $_MG_DEFAULT['up_thumbnail_size_enabled'],  'select',   0,  0, 0,    $o++, true, $n, 3);

        // ----------------------------------
        $c->add('tab_graphics',          NULL,                                     'tab',      0,  0, NULL, 0,    true, $n, 4);
        $c->add('fs_graphics',           NULL,                                     'fieldset', 0,  0, NULL, 0,    true, $n, 4);
        $c->add('jhead_enabled',         $_MG_DEFAULT['jhead_enabled'],            'select',   0,  0, 0,    $o++, true, $n, 4);
        $c->add('jhead_path',            $_MG_DEFAULT['jhead_path'],               'text',     0,  0, 0,    $o++, true, $n, 4);
        $c->add('jpegtran_enabled',      $_MG_DEFAULT['jpegtran_enabled'],         'select',   0,  0, 0,    $o++, true, $n, 4);
        $c->add('jpegtran_path',         $_MG_DEFAULT['jpegtran_path'],            'text',     0,  0, 0,    $o++, true, $n, 4);
        $c->add('ffmpeg_enabled',        $_MG_DEFAULT['ffmpeg_enabled'],           'select',   0,  0, 0,    $o++, true, $n, 4);
        $c->add('ffmpeg_path',           $_MG_DEFAULT['ffmpeg_path'],              'text',     0,  0, 0,    $o++, true, $n, 4);
        $c->add('zip_enabled',           $_MG_DEFAULT['zip_enabled'],              'select',   0,  0, 0,    $o++, true, $n, 4);
        $c->add('zip_path',              $_MG_DEFAULT['zip_path'],                 'text',     0,  0, 0,    $o++, true, $n, 4);
        $c->add('tmp_path',              $_MG_DEFAULT['tmp_path'],                 'text',     0,  0, 0,    $o++, true, $n, 4);
        $c->add('ftp_path',              $_MG_DEFAULT['ftp_path'],                 'text',     0,  0, 0,    $o++, true, $n, 4);

        // ----------------------------------
        $c->add('sg_album',              NULL,                                     'subgroup', 1,  0, NULL, 0,    true, $n, 0);
        $c->add('tab_album',             NULL,                                     'tab',      1,  0, NULL, 0,    true, $n, 5);
        $c->add('fs_root_album',         NULL,                                     'fieldset', 1,  0, NULL, 0,    true, $n, 5);
        $c->add('root_album_name',       $_MG_DEFAULT['root_album_name'],          'text',     1,  0, 0,    $o++, true, $n, 5);
        $c->add('album_display_columns', $_MG_DEFAULT['album_display_columns'],    'text',     1,  0, 0,    $o++, true, $n, 5);
        $c->add('album_display_rows',    $_MG_DEFAULT['album_display_rows'],       'text',     1,  0, 0,    $o++, true, $n, 5);
        $c->add('indextheme',            $_MG_DEFAULT['indextheme'],               'select',   1,  0, NULL, $o++, true, $n, 5);
        $c->add('indexskin',             $_MG_DEFAULT['indexskin'],                'select',   1,  0, NULL, $o++, true, $n, 5);
        // ----------------------------------
        $c->add('fs_album',              NULL,                                     'fieldset', 1,  1, NULL, 0,    true, $n, 5);
        $c->add('ad_skin',               $_MG_DEFAULT['ad_skin'],                  'select',   1,  1, NULL, $o++, true, $n, 5);
        $c->add('ad_enable_comments',    $_MG_DEFAULT['ad_enable_comments'],       'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_exif_display',       $_MG_DEFAULT['ad_exif_display'],          'select',   1,  1, 15,   $o++, true, $n, 5);
        $c->add('ad_enable_rating',      $_MG_DEFAULT['ad_enable_rating'],         'select',   1,  1, 16,   $o++, true, $n, 5);
        $c->add('ad_enable_album_views', $_MG_DEFAULT['ad_enable_album_views'],    'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_enable_views',       $_MG_DEFAULT['ad_enable_views'],          'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_enable_keywords',    $_MG_DEFAULT['ad_enable_keywords'],       'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_display_album_desc', $_MG_DEFAULT['ad_display_album_desc'],    'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_filename_title',     $_MG_DEFAULT['ad_filename_title'],        'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_enable_rss',         $_MG_DEFAULT['ad_enable_rss'],            'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_rsschildren',        $_MG_DEFAULT['ad_rsschildren'],           'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_podcast',            $_MG_DEFAULT['ad_podcast'],               'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_mp3ribbon',          $_MG_DEFAULT['ad_mp3ribbon'],             'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_enable_sort',        $_MG_DEFAULT['ad_enable_sort'],           'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_album_sort_order',   $_MG_DEFAULT['ad_album_sort_order'],      'select',   1,  1, 17,   $o++, true, $n, 5);
        $c->add('ad_playback_type',      $_MG_DEFAULT['ad_playback_type'],         'select',   1,  1, 18,   $o++, true, $n, 5);
        $c->add('ad_enable_slideshow',   $_MG_DEFAULT['ad_enable_slideshow'],      'select',   1,  1, 19,   $o++, true, $n, 5);
        $c->add('ad_enable_random',      $_MG_DEFAULT['ad_enable_random'],         'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_albums_first',       $_MG_DEFAULT['ad_albums_first'],          'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_allow_download',     $_MG_DEFAULT['ad_allow_download'],        'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_full_display',       $_MG_DEFAULT['ad_full_display'],          'select',   1,  1, 20,   $o++, true, $n, 5);
        $c->add('ad_tn_size',            $_MG_DEFAULT['ad_tn_size'],               'select',   1,  1, 11,   $o++, true, $n, 5);
        $c->add('ad_tn_width',           $_MG_DEFAULT['ad_tn_width'],              'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_tn_height',          $_MG_DEFAULT['ad_tn_height'],             'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_max_image_width',    $_MG_DEFAULT['ad_max_image_width'],       'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_max_image_height',   $_MG_DEFAULT['ad_max_image_height'],      'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_max_filesize',       $_MG_DEFAULT['ad_max_filesize'],          'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_display_image_size', $_MG_DEFAULT['ad_display_image_size'],    'select',   1,  1, 21,   $o++, true, $n, 5);
        $c->add('ad_display_rows',       $_MG_DEFAULT['ad_display_rows'],          'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_display_columns',    $_MG_DEFAULT['ad_display_columns'],       'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_image_skin',         $_MG_DEFAULT['ad_image_skin'],            'select',   1,  1, NULL, $o++, true, $n, 5);
        $c->add('ad_display_skin',       $_MG_DEFAULT['ad_display_skin'],          'select',   1,  1, NULL, $o++, true, $n, 5);
        $c->add('ad_album_skin',         $_MG_DEFAULT['ad_album_skin'],            'select',   1,  1, NULL, $o++, true, $n, 5);

        // ----------------------------------
        $c->add('tab_watermark',         NULL,                                     'tab',      1,  0, NULL, 0,    true, $n, 6);
        $c->add('fs_watermark',          NULL,                                     'fieldset', 1,  0, NULL, 0,    true, $n, 6);
        $c->add('ad_wm_auto',            $_MG_DEFAULT['ad_wm_auto'],               'select',   1,  0, 0,    $o++, true, $n, 6);
        $c->add('ad_wm_opacity',         $_MG_DEFAULT['ad_wm_opacity'],            'select',   1,  0, 22,   $o++, true, $n, 6);
        $c->add('ad_wm_location',        $_MG_DEFAULT['ad_wm_location'],           'select',   1,  0, 23,   $o++, true, $n, 6);
        $c->add('ad_wm_id',              $_MG_DEFAULT['ad_wm_id'],                 'select',   1,  0, NULL, $o++, true, $n, 6);
        // ----------------------------------
        $c->add('tab_allowedmediatypes', NULL,                                     'tab',      1,  0, NULL, 0,    true, $n, 7);
        $c->add('fs_allowedmediatypes',  NULL,                                     'fieldset', 1,  0, NULL, 0,    true, $n, 7);
        $c->add('ad_valid_format_jpg',   $_MG_DEFAULT['ad_valid_format_jpg'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_png',   $_MG_DEFAULT['ad_valid_format_png'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_tif',   $_MG_DEFAULT['ad_valid_format_tif'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_gif',   $_MG_DEFAULT['ad_valid_format_gif'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_bmp',   $_MG_DEFAULT['ad_valid_format_bmp'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_tga',   $_MG_DEFAULT['ad_valid_format_tga'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_psd',   $_MG_DEFAULT['ad_valid_format_psd'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_mp3',   $_MG_DEFAULT['ad_valid_format_mp3'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_ogg',   $_MG_DEFAULT['ad_valid_format_ogg'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_asf',   $_MG_DEFAULT['ad_valid_format_asf'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_swf',   $_MG_DEFAULT['ad_valid_format_swf'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_mov',   $_MG_DEFAULT['ad_valid_format_mov'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_mp4',   $_MG_DEFAULT['ad_valid_format_mp4'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_mpg',   $_MG_DEFAULT['ad_valid_format_mpg'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_flv',   $_MG_DEFAULT['ad_valid_format_flv'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_rflv',  $_MG_DEFAULT['ad_valid_format_rflv'],     'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_emb',   $_MG_DEFAULT['ad_valid_format_emb'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_zip',   $_MG_DEFAULT['ad_valid_format_zip'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_other', $_MG_DEFAULT['ad_valid_format_other'],    'select',   1,  0, 0,    $o++, true, $n, 7);

        // ----------------------------------
        $c->add('tab_useruploads',       NULL,                                     'tab',      1,  0, NULL, 0,    true, $n, 8);
        $c->add('fs_useruploads',        NULL,                                     'fieldset', 1,  0, NULL, 0,    true, $n, 8);
        $c->add('ad_member_uploads',     $_MG_DEFAULT['ad_member_uploads'],        'select',   1,  0, 0,    $o++, true, $n, 8);
        $c->add('ad_moderate',           $_MG_DEFAULT['ad_moderate'],              'select',   1,  0, 0,    $o++, true, $n, 8);
        $c->add('ad_mod_group_id',       $_MG_DEFAULT['ad_mod_group_id'],          'select',   1,  0, NULL, $o++, true, $n, 8);
        $c->add('ad_email_mod',          $_MG_DEFAULT['ad_email_mod'],             'select',   1,  0, 0,    $o++, true, $n, 8);

        // ----------------------------------
        $c->add('tab_accessrights',      NULL,                                     'tab',      1,  0, NULL, 0,    true, $n, 9);
        $c->add('fs_accessrights',       NULL,                                     'fieldset', 1,  0, NULL, 0,    true, $n, 9);
        $c->add('ad_group_id',           $_MG_DEFAULT['ad_group_id'],              'select',   1,  0, NULL, $o++, true, $n, 9);
        // ----------------------------------
        $c->add('fs_permissions',        NULL,                                     'fieldset', 1,  1, NULL, 0,    true, $n, 9);
        $c->add('ad_permissions',        $_MG_DEFAULT['ad_permissions'],           '@select',  1,  1, 12,   $o++, true, $n, 9);

        // ----------------------------------

        $c->add('sg_av',                 NULL,                                     'subgroup', 2,  0, NULL, 0,    true, $n, 0);
        // ----------------------------------
        $c->add('tab_wmedia',            NULL,                                     'tab',      2,  0, NULL, 0,    true, $n, 10);
        $c->add('fs_wmedia',             NULL,                                     'fieldset', 2,  0, NULL, 0,    true, $n, 10);
        $c->add('asf_autostart',         $_MG_DEFAULT['asf_autostart'],            'select',   2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_enablecontextmenu', $_MG_DEFAULT['asf_enablecontextmenu'],    'select',   2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_stretchtofit',      $_MG_DEFAULT['asf_stretchtofit'],         'select',   2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_showstatusbar',     $_MG_DEFAULT['asf_showstatusbar'],        'select',   2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_uimode',            $_MG_DEFAULT['asf_uimode'],               'select',   2,  0, 24,   $o++, true, $n, 10);
        $c->add('asf_playcount',         $_MG_DEFAULT['asf_playcount'],            'text',     2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_bgcolor',           $_MG_DEFAULT['asf_bgcolor'],              'text',     2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_width',             $_MG_DEFAULT['asf_width'],                'text',     2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_height',            $_MG_DEFAULT['asf_height'],               'text',     2,  0, 0,    $o++, true, $n, 10);
        // ----------------------------------
        $c->add('tab_quicktime',         NULL,                                     'tab',      2,  0, NULL, 0,    true, $n, 11);
        $c->add('fs_quicktime',          NULL,                                     'fieldset', 2,  0, NULL, 0,    true, $n, 11);
        $c->add('mov_autoref',           $_MG_DEFAULT['mov_autoref'],              'select',   2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_autoplay',          $_MG_DEFAULT['mov_autoplay'],             'select',   2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_controller',        $_MG_DEFAULT['mov_controller'],           'select',   2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_kioskmode',         $_MG_DEFAULT['mov_kioskmode'],            'select',   2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_scale',             $_MG_DEFAULT['mov_scale'],                'select',   2,  0, 25,   $o++, true, $n, 11);
        $c->add('mov_loop',              $_MG_DEFAULT['mov_loop'],                 'select',   2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_bgcolor',           $_MG_DEFAULT['mov_bgcolor'],              'text',     2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_width',             $_MG_DEFAULT['mov_width'],                'text',     2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_height',            $_MG_DEFAULT['mov_height'],               'text',     2,  0, 0,    $o++, true, $n, 11);
        // ----------------------------------
        $c->add('tab_mp3',               NULL,                                     'tab',      2,  0, NULL, 0,    true, $n, 12);
        $c->add('fs_mp3',                NULL,                                     'fieldset', 2,  0, NULL, 0,    true, $n, 12);
        $c->add('mp3_autostart',         $_MG_DEFAULT['mp3_autostart'],            'select',   2,  0, 0,    $o++, true, $n, 12);
        $c->add('mp3_enablecontextmenu', $_MG_DEFAULT['mp3_enablecontextmenu'],    'select',   2,  0, 0,    $o++, true, $n, 12);
        $c->add('mp3_showstatusbar',     $_MG_DEFAULT['mp3_showstatusbar'],        'select',   2,  0, 0,    $o++, true, $n, 12);
        $c->add('mp3_loop',              $_MG_DEFAULT['mp3_loop'],                 'select',   2,  0, 0,    $o++, true, $n, 12);
        $c->add('mp3_uimode',            $_MG_DEFAULT['mp3_uimode'],               'select',   2,  0, 24,   $o++, true, $n, 12);
        // ----------------------------------
        $c->add('tab_flashmedia',        NULL,                                     'tab',      2,  0, NULL, 0,    true, $n, 13);
        $c->add('fs_flashmedia',         NULL,                                     'fieldset', 2,  0, NULL, 0,    true, $n, 13);

        $c->add('swf_play',              $_MG_DEFAULT['swf_play'],                 'select',   2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_menu',              $_MG_DEFAULT['swf_menu'],                 'select',   2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_scale',             $_MG_DEFAULT['swf_scale'],                'select',   2,  0, 26,   $o++, true, $n, 13);
        $c->add('swf_wmode',             $_MG_DEFAULT['swf_wmode'],                'select',   2,  0, 27,   $o++, true, $n, 13);
        $c->add('swf_allowscriptaccess', $_MG_DEFAULT['swf_allowscriptaccess'],    'select',   2,  0, 28,   $o++, true, $n, 13);
        $c->add('swf_quality',           $_MG_DEFAULT['swf_quality'],              'select',   2,  0, 29,   $o++, true, $n, 13);
        $c->add('swf_loop',              $_MG_DEFAULT['swf_loop'],                 'select',   2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_bgcolor',           $_MG_DEFAULT['swf_bgcolor'],              'text',     2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_width',             $_MG_DEFAULT['swf_width'],                'text',     2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_height',            $_MG_DEFAULT['swf_height'],               'text',     2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_flashvars',         $_MG_DEFAULT['swf_flashvars'],            'text',     2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_version',           $_MG_DEFAULT['swf_version'],              'text',     2,  0, 0,    $o++, true, $n, 13);

        // ----------------------------------

        $c->add('sg_member_album',       NULL,                                     'subgroup', 3,  0, NULL, 0,    true, $n, 0);
        // ----------------------------------
        $c->add('tab_member_albums',     NULL,                                     'tab',      3,  0, NULL, 0,    true, $n, 14);
        $c->add('fs_member_albums',      NULL,                                     'fieldset', 3,  0, NULL, 0,    true, $n, 14);
        $c->add('member_albums',         $_MG_DEFAULT['member_albums'],            'select',   3,  0, 0,    $o++, true, $n, 14);
        $c->add('allow_remote',          $_MG_DEFAULT['allow_remote'],             'select',   3,  0, 0,    $o++, true, $n, 14);
        $c->add('member_use_fullname',   $_MG_DEFAULT['member_use_fullname'],      'select',   3,  0, 0,    $o++, true, $n, 14);
        $c->add('feature_member_album',  $_MG_DEFAULT['feature_member_album'],     'select',   3,  0, 0,    $o++, true, $n, 14);
        $c->add('member_quota',          $_MG_DEFAULT['member_quota'],             'text',     3,  0, 0,    $o++, true, $n, 14);
        $c->add('member_auto_create',    $_MG_DEFAULT['member_auto_create'],       'select',   3,  0, 0,    $o++, true, $n, 14);
        $c->add('member_create_new',     $_MG_DEFAULT['member_create_new'],        'select',   3,  0, 0,    $o++, true, $n, 14);
        $c->add('member_album_root',     $_MG_DEFAULT['member_album_root'],        'select',   3,  0, NULL, $o++, true, $n, 14); //////
        $c->add('member_album_archive',  $_MG_DEFAULT['member_album_archive'],     'select',   3,  0, NULL, $o++, true, $n, 14); //////
        // ----------------------------------
        $c->add('tab_member_allowedmediatypes', NULL,                                      'tab',      3,  0, NULL, 0,    true, $n, 15);
        $c->add('fs_member_allowedmediatypes',  NULL,                                      'fieldset', 3,  0, NULL, 0,    true, $n, 15);
        $c->add('member_valid_format_jpg',      $_MG_DEFAULT['member_valid_format_jpg'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_png',      $_MG_DEFAULT['member_valid_format_png'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_tif',      $_MG_DEFAULT['member_valid_format_tif'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_gif',      $_MG_DEFAULT['member_valid_format_gif'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_bmp',      $_MG_DEFAULT['member_valid_format_bmp'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_tga',      $_MG_DEFAULT['member_valid_format_tga'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_psd',      $_MG_DEFAULT['member_valid_format_psd'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_mp3',      $_MG_DEFAULT['member_valid_format_mp3'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_ogg',      $_MG_DEFAULT['member_valid_format_ogg'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_asf',      $_MG_DEFAULT['member_valid_format_asf'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_swf',      $_MG_DEFAULT['member_valid_format_swf'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_mov',      $_MG_DEFAULT['member_valid_format_mov'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_mp4',      $_MG_DEFAULT['member_valid_format_mp4'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_mpg',      $_MG_DEFAULT['member_valid_format_mpg'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_flv',      $_MG_DEFAULT['member_valid_format_flv'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_rflv',     $_MG_DEFAULT['member_valid_format_rflv'],  'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_emb',      $_MG_DEFAULT['member_valid_format_emb'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_zip',      $_MG_DEFAULT['member_valid_format_zip'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_other',    $_MG_DEFAULT['member_valid_format_other'], 'select',   3,  0, 0,    $o++, true, $n, 15);
        // ----------------------------------
        $c->add('tab_member_album_attributes',  NULL,                                      'tab',      3,  0, NULL, 0,    true, $n, 16);
        $c->add('fs_member_album_attributes',   NULL,                                      'fieldset', 3,  0, NULL, 0,    true, $n, 16);
        $c->add('member_enable_random',         $_MG_DEFAULT['member_enable_random'],      'select',   3,  0, 0,    $o++, true, $n, 16);
        $c->add('member_max_width',             $_MG_DEFAULT['member_max_width'],          'text',     3,  0, 0,    $o++, true, $n, 16);
        $c->add('member_max_height',            $_MG_DEFAULT['member_max_height'],         'text',     3,  0, 0,    $o++, true, $n, 16);
        $c->add('member_max_filesize',          $_MG_DEFAULT['member_max_filesize'],       'text',     3,  0, 0,    $o++, true, $n, 16);
        // ----------------------------------
        $c->add('tab_member_useruploads',       NULL,                                     'tab',       3,  0, NULL, 0,    true, $n, 17);
        $c->add('fs_member_useruploads',        NULL,                                     'fieldset',  3,  0, NULL, 0,    true, $n, 17);
        $c->add('member_uploads',               $_MG_DEFAULT['member_uploads'],           'select',    3,  0, 0,    $o++, true, $n, 17);
        $c->add('member_moderate',              $_MG_DEFAULT['member_moderate'],          'select',    3,  0, 0,    $o++, true, $n, 17);
        $c->add('member_mod_group_id',          $_MG_DEFAULT['member_mod_group_id'],      'select',    3,  0, NULL, $o++, true, $n, 17);
        $c->add('member_email_mod',             $_MG_DEFAULT['member_email_mod'],         'select',    3,  0, 0,    $o++, true, $n, 17);
        // ----------------------------------
        $c->add('tab_member_accessrights',      NULL,                                     'tab',       3,  0, NULL, 0,    true, $n, 18);
        $c->add('fs_member_permissions',        NULL,                                     'fieldset',  3,  0, NULL, 0,    true, $n, 18);
        $c->add('member_permissions',           $_MG_DEFAULT['member_permissions'],       '@select',   3,  0, 12,   $o++, true, $n, 18);
    }

    return true;
}

function mediagallery_update_ConfValues_1_7_0()
{
    global $_MG_CONF, $_MG_DEFAULT, $_TABLES, $_DB_table_prefix;

    // Read old config data
    $_MG_CONF_OLD = array();
    $result = DB_query("SELECT * FROM " . $_TABLES['mg_config'], 1);
    while ($A = DB_fetchArray($result)) {
        $_MG_CONF_OLD[$A['config_name']] = $A['config_value'];
    }
    if (count($_MG_CONF_OLD) > 1) {
        $_MG_DEFAULT = array_merge($_MG_DEFAULT, $_MG_CONF_OLD);
    }
    $_MG_DEFAULT['album_display_columns'] = 3;
    $_MG_DEFAULT['album_display_rows']    = 4;
    $_MG_DEFAULT['dfid']                  = 19;
    $_MG_DEFAULT['indexskin']             = 'default';

    $ad_skin = array(
        'ad_image_skin',
        'ad_display_skin',
        'ad_album_skin'
    );
    foreach($ad_skin as $ads) {
        $_MG_DEFAULT[$ads] = $_MG_CONF_OLD[$ads];
        if (!in_array($_MG_CONF_OLD[$ads], array('border', 'default', 'mgAlbum',
          'mgShadow', 'new_border', 'new_shadow', 'none'))) {
            $_MG_DEFAULT[$ads] = 'default';
        }
    }


/*
    $mg_config = array();
    $sql = "SELECT * FROM {$_TABLES['mg_config']}";
    $result = DB_query($sql);
    while ($A = DB_fetchArray($result)) {
        $mg_config[$A['config_name']] = $A['config_value'];
    }
    if (count($mg_config) > 1) {
        $_MG_DEFAULT = array_merge($_MG_DEFAULT, $mg_config);
    }
*/


    $c = config::get_instance();
    $n = 'mediagallery';
    $o = 1;
    if (!$c->group_exists($n)) {

        $c->add('sg_main',               NULL,                                     'subgroup', 0,  0, NULL, 0,    true, $n, 0);
        // ----------------------------------
        $c->add('tab_main',              NULL,                                     'tab',      0,  0, NULL, 0,    true, $n, 0);
        $c->add('fs_main',               NULL,                                     'fieldset', 0,  0, NULL, 0,    true, $n, 0);
        $c->add('gallery_only',          $_MG_DEFAULT['gallery_only'],             'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('loginrequired',         $_MG_DEFAULT['loginrequired'],            'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('htmlallowed',           $_MG_DEFAULT['htmlallowed'],              'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('usage_tracking',        $_MG_DEFAULT['usage_tracking'],           'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('whatsnew',              $_MG_DEFAULT['whatsnew'],                 'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('title_length',          $_MG_DEFAULT['title_length'],             'text',     0,  0, 0,    $o++, true, $n, 0);
        $c->add('whatsnew_time',         $_MG_DEFAULT['whatsnew_time'],            'text',     0,  0, 0,    $o++, true, $n, 0);
        $c->add('preserve_filename',     $_MG_DEFAULT['preserve_filename'],        'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('discard_original',      $_MG_DEFAULT['discard_original'],         'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('verbose',               $_MG_DEFAULT['verbose'],                  'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('disable_whatsnew_comments', $_MG_DEFAULT['disable_whatsnew_comments'], 'select',   0,  0, 0,    $o++, true, $n, 0);
        $c->add('profile_hook',          $_MG_DEFAULT['profile_hook'],             'select',   0,  0, 0,    $o++, true, $n, 0);

        // ----------------------------------
        $c->add('fs_autotag',            NULL,                                     'fieldset', 0,  1, NULL, 0,    true, $n, 0);
        $c->add('at_border',             $_MG_DEFAULT['at_border'],                'select',   0,  1, 0,    $o++, true, $n, 0);
        $c->add('at_align',              $_MG_DEFAULT['at_align'],                 'select',   0,  1, 7,    $o++, true, $n, 0);
        $c->add('at_width',              $_MG_DEFAULT['at_width'],                 'text',     0,  1, 0,    $o++, true, $n, 0);
        $c->add('at_height',             $_MG_DEFAULT['at_height'],                'text',     0,  1, 0,    $o++, true, $n, 0);
        $c->add('at_src',                $_MG_DEFAULT['at_src'],                   'select',   0,  1, 8,    $o++, true, $n, 0);
        $c->add('at_autoplay',           $_MG_DEFAULT['at_autoplay'],              'select',   0,  1, 0,    $o++, true, $n, 0);
        $c->add('at_enable_link',        $_MG_DEFAULT['at_enable_link'],           'select',   0,  1, 9,    $o++, true, $n, 0);
        $c->add('at_delay',              $_MG_DEFAULT['at_delay'],                 'text',     0,  1, 0,    $o++, true, $n, 0);
        $c->add('at_showtitle',          $_MG_DEFAULT['at_showtitle'],             'select',   0,  1, 0,    $o++, true, $n, 0);
        // ----------------------------------
        $c->add('fs_rssfeed',            NULL,                                     'fieldset', 0,  2, NULL, 0,    true, $n, 0);
        $c->add('rss_full_enabled',      $_MG_DEFAULT['rss_full_enabled'],         'select',   0,  2, 0,    $o++, true, $n, 0);
        $c->add('rss_feed_type',         $_MG_DEFAULT['rss_feed_type'],            'select',   0,  2, 30,   $o++, true, $n, 0);
        $c->add('rss_ignore_empty',      $_MG_DEFAULT['rss_ignore_empty'],         'select',   0,  2, 0,    $o++, true, $n, 0);
        $c->add('rss_anonymous_only',    $_MG_DEFAULT['rss_anonymous_only'],       'select',   0,  2, 0,    $o++, true, $n, 0);
        $c->add('hide_author_email',     $_MG_DEFAULT['hide_author_email'],        'select',   0,  2, 0,    $o++, true, $n, 0);
        $c->add('rss_feed_name',         $_MG_DEFAULT['rss_feed_name'],            'text',     0,  2, 0,    $o++, true, $n, 0);

        // ----------------------------------
        $c->add('tab_display',           NULL,                                     'tab',      0,  0, NULL, 0,    true, $n, 1);
        $c->add('fs_display',            NULL,                                     'fieldset', 0,  0, NULL, 0,    true, $n, 1);
        $c->add('dfid',                  $_MG_DEFAULT['dfid'],                     'select',   0,  0, NULL, $o++, true, $n, 1);
        $c->add('displayblocks',         $_MG_DEFAULT['displayblocks'],            'select',   0,  0, 10,   $o++, true, $n, 1);
        $c->add('subalbum_select',       $_MG_DEFAULT['subalbum_select'],          'select',   0,  0, 0,    $o++, true, $n, 1);
        $c->add('jpg_orig_quality',      $_MG_DEFAULT['jpg_orig_quality'],         'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('jpg_quality',           $_MG_DEFAULT['jpg_quality'],              'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('tn_jpg_quality',        $_MG_DEFAULT['tn_jpg_quality'],           'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('gallery_tn_size',       $_MG_DEFAULT['gallery_tn_size'],          'select',   0,  0, 11,   $o++, true, $n, 1);
        $c->add('gallery_tn_height',     $_MG_DEFAULT['gallery_tn_height'],        'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('gallery_tn_width',      $_MG_DEFAULT['gallery_tn_width'],         'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('enable_media_id',       $_MG_DEFAULT['enable_media_id'],          'select',   0,  0, 0,    $o++, true, $n, 1);
        $c->add('full_in_popup',         $_MG_DEFAULT['full_in_popup'],            'select',   0,  0, 0,    $o++, true, $n, 1);
        $c->add('seperator',             $_MG_DEFAULT['seperator'],                'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('use_flowplayer',        $_MG_DEFAULT['use_flowplayer'],           'select',   0,  0, 13,   $o++, true, $n, 1);
        $c->add('custom_image_height',   $_MG_DEFAULT['custom_image_height'],      'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('custom_image_width',    $_MG_DEFAULT['custom_image_width'],       'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('popup_from_album',      $_MG_DEFAULT['popup_from_album'],         'select',   0,  0, 0,    $o++, true, $n, 1);
        $c->add('autotag_caption',       $_MG_DEFAULT['autotag_caption'],          'select',   0,  0, 0,    $o++, true, $n, 1);
        $c->add('random_width',          $_MG_DEFAULT['random_width'],             'text',     0,  0, 0,    $o++, true, $n, 1);
        $c->add('random_skin',           $_MG_DEFAULT['random_skin'],              'select',   0,  0, NULL, $o++, true, $n, 1);
        $c->add('truncate_breadcrumb',   $_MG_DEFAULT['truncate_breadcrumb'],      'text',     0,  0, 0,    $o++, true, $n, 1);
        // ----------------------------------
        $c->add('fs_searchresults',      NULL,                                     'fieldset', 0,  1, NULL, 0,    true, $n, 1);
        $c->add('search_columns',        $_MG_DEFAULT['search_columns'],           'text',     0,  1, 0,    $o++, true, $n, 1);
        $c->add('search_rows',           $_MG_DEFAULT['search_rows'],              'text',     0,  1, 0,    $o++, true, $n, 1);
        $c->add('search_playback_type',  $_MG_DEFAULT['search_playback_type'],     'select',   0,  1, 14,   $o++, true, $n, 1);
        $c->add('search_enable_views',   $_MG_DEFAULT['search_enable_views'],      'select',   0,  1, 0,    $o++, true, $n, 1);
        $c->add('search_enable_rating',  $_MG_DEFAULT['search_enable_rating'],     'select',   0,  1, 0,    $o++, true, $n, 1);
        $c->add('search_album_skin',     $_MG_DEFAULT['search_album_skin'],        'select',   0,  1, NULL, $o++, true, $n, 1);
        $c->add('search_frame_skin',     $_MG_DEFAULT['search_frame_skin'],        'select',   0,  1, NULL, $o++, true, $n, 1);
        $c->add('search_tn_size',        $_MG_DEFAULT['search_tn_size'],           'select',   0,  1, 31,   $o++, true, $n, 1);

        // ----------------------------------
        $c->add('tab_batch',             NULL,                                     'tab',      0,  0, NULL, 0,    true, $n, 2);
        $c->add('fs_batch',              NULL,                                     'fieldset', 0,  0, NULL, 0,    true, $n, 2);
        $c->add('def_refresh_rate',      $_MG_DEFAULT['def_refresh_rate'],         'text',     0,  0, 0,    $o++, true, $n, 2);
        $c->add('def_item_limit',        $_MG_DEFAULT['def_item_limit'],           'text',     0,  0, 0,    $o++, true, $n, 2);
        $c->add('def_time_limit',        $_MG_DEFAULT['def_time_limit'],           'text',     0,  0, 0,    $o++, true, $n, 2);

        // ----------------------------------
        $c->add('tab_userprefs',         NULL,                                     'tab',      0,  0, NULL, 0,    true, $n, 3);
        $c->add('fs_userprefs',          NULL,                                     'fieldset', 0,  0, NULL, 0,    true, $n, 3);
        $c->add('up_display_rows_enabled',    $_MG_DEFAULT['up_display_rows_enabled'],    'select',   0,  0, 0,    $o++, true, $n, 3);
        $c->add('up_display_columns_enabled', $_MG_DEFAULT['up_display_columns_enabled'], 'select',   0,  0, 0,    $o++, true, $n, 3);
        $c->add('up_av_playback_enabled',     $_MG_DEFAULT['up_av_playback_enabled'],     'select',   0,  0, 0,    $o++, true, $n, 3);
        $c->add('up_thumbnail_size_enabled',  $_MG_DEFAULT['up_thumbnail_size_enabled'],  'select',   0,  0, 0,    $o++, true, $n, 3);

        // ----------------------------------
        $c->add('tab_graphics',          NULL,                                     'tab',      0,  0, NULL, 0,    true, $n, 4);
        $c->add('fs_graphics',           NULL,                                     'fieldset', 0,  0, NULL, 0,    true, $n, 4);
        $c->add('jhead_enabled',         $_MG_DEFAULT['jhead_enabled'],            'select',   0,  0, 0,    $o++, true, $n, 4);
        $c->add('jhead_path',            $_MG_DEFAULT['jhead_path'],               'text',     0,  0, 0,    $o++, true, $n, 4);
        $c->add('jpegtran_enabled',      $_MG_DEFAULT['jpegtran_enabled'],         'select',   0,  0, 0,    $o++, true, $n, 4);
        $c->add('jpegtran_path',         $_MG_DEFAULT['jpegtran_path'],            'text',     0,  0, 0,    $o++, true, $n, 4);
        $c->add('ffmpeg_enabled',        $_MG_DEFAULT['ffmpeg_enabled'],           'select',   0,  0, 0,    $o++, true, $n, 4);
        $c->add('ffmpeg_path',           $_MG_DEFAULT['ffmpeg_path'],              'text',     0,  0, 0,    $o++, true, $n, 4);
        $c->add('zip_enabled',           $_MG_DEFAULT['zip_enabled'],              'select',   0,  0, 0,    $o++, true, $n, 4);
        $c->add('zip_path',              $_MG_DEFAULT['zip_path'],                 'text',     0,  0, 0,    $o++, true, $n, 4);
        $c->add('tmp_path',              $_MG_DEFAULT['tmp_path'],                 'text',     0,  0, 0,    $o++, true, $n, 4);
        $c->add('ftp_path',              $_MG_DEFAULT['ftp_path'],                 'text',     0,  0, 0,    $o++, true, $n, 4);

        // ----------------------------------
        $c->add('sg_album',              NULL,                                     'subgroup', 1,  0, NULL, 0,    true, $n, 0);
        $c->add('tab_album',             NULL,                                     'tab',      1,  0, NULL, 0,    true, $n, 5);
        $c->add('fs_root_album',         NULL,                                     'fieldset', 1,  0, NULL, 0,    true, $n, 5);
        $c->add('root_album_name',       $_MG_DEFAULT['root_album_name'],          'text',     1,  0, 0,    $o++, true, $n, 5);
        $c->add('album_display_columns', $_MG_DEFAULT['album_display_columns'],    'text',     1,  0, 0,    $o++, true, $n, 5);
        $c->add('album_display_rows',    $_MG_DEFAULT['album_display_rows'],       'text',     1,  0, 0,    $o++, true, $n, 5);
        $c->add('indextheme',            $_MG_DEFAULT['indextheme'],               'select',   1,  0, NULL, $o++, true, $n, 5);
        $c->add('indexskin',             $_MG_DEFAULT['indexskin'],                'select',   1,  0, NULL, $o++, true, $n, 5);
        // ----------------------------------
        $c->add('fs_album',              NULL,                                     'fieldset', 1,  1, NULL, 0,    true, $n, 5);
        $c->add('ad_skin',               $_MG_DEFAULT['ad_skin'],                  'select',   1,  1, NULL, $o++, true, $n, 5);
        $c->add('ad_enable_comments',    $_MG_DEFAULT['ad_enable_comments'],       'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_exif_display',       $_MG_DEFAULT['ad_exif_display'],          'select',   1,  1, 15,   $o++, true, $n, 5);
        $c->add('ad_enable_rating',      $_MG_DEFAULT['ad_enable_rating'],         'select',   1,  1, 16,   $o++, true, $n, 5);
        $c->add('ad_enable_album_views', $_MG_DEFAULT['ad_enable_album_views'],    'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_enable_views',       $_MG_DEFAULT['ad_enable_views'],          'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_enable_keywords',    $_MG_DEFAULT['ad_enable_keywords'],       'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_display_album_desc', $_MG_DEFAULT['ad_display_album_desc'],    'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_filename_title',     $_MG_DEFAULT['ad_filename_title'],        'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_enable_rss',         $_MG_DEFAULT['ad_enable_rss'],            'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_rsschildren',        $_MG_DEFAULT['ad_rsschildren'],           'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_podcast',            $_MG_DEFAULT['ad_podcast'],               'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_mp3ribbon',          $_MG_DEFAULT['ad_mp3ribbon'],             'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_enable_sort',        $_MG_DEFAULT['ad_enable_sort'],           'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_album_sort_order',   $_MG_DEFAULT['ad_album_sort_order'],      'select',   1,  1, 17,   $o++, true, $n, 5);
        $c->add('ad_playback_type',      $_MG_DEFAULT['ad_playback_type'],         'select',   1,  1, 18,   $o++, true, $n, 5);
        $c->add('ad_enable_slideshow',   $_MG_DEFAULT['ad_enable_slideshow'],      'select',   1,  1, 19,   $o++, true, $n, 5);
        $c->add('ad_enable_random',      $_MG_DEFAULT['ad_enable_random'],         'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_albums_first',       $_MG_DEFAULT['ad_albums_first'],          'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_allow_download',     $_MG_DEFAULT['ad_allow_download'],        'select',   1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_full_display',       $_MG_DEFAULT['ad_full_display'],          'select',   1,  1, 20,   $o++, true, $n, 5);
        $c->add('ad_tn_size',            $_MG_DEFAULT['ad_tn_size'],               'select',   1,  1, 11,   $o++, true, $n, 5);
        $c->add('ad_tn_width',           $_MG_DEFAULT['ad_tn_width'],              'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_tn_height',          $_MG_DEFAULT['ad_tn_height'],             'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_max_image_width',    $_MG_DEFAULT['ad_max_image_width'],       'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_max_image_height',   $_MG_DEFAULT['ad_max_image_height'],      'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_max_filesize',       $_MG_DEFAULT['ad_max_filesize'],          'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_display_image_size', $_MG_DEFAULT['ad_display_image_size'],    'select',   1,  1, 21,   $o++, true, $n, 5);
        $c->add('ad_display_rows',       $_MG_DEFAULT['ad_display_rows'],          'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_display_columns',    $_MG_DEFAULT['ad_display_columns'],       'text',     1,  1, 0,    $o++, true, $n, 5);
        $c->add('ad_image_skin',         $_MG_DEFAULT['ad_image_skin'],            'select',   1,  1, NULL, $o++, true, $n, 5);
        $c->add('ad_display_skin',       $_MG_DEFAULT['ad_display_skin'],          'select',   1,  1, NULL, $o++, true, $n, 5);
        $c->add('ad_album_skin',         $_MG_DEFAULT['ad_album_skin'],            'select',   1,  1, NULL, $o++, true, $n, 5);
        // ----------------------------------
        $c->add('tab_watermark',         NULL,                                     'tab',      1,  0, NULL, 0,    true, $n, 6);
        $c->add('fs_watermark',          NULL,                                     'fieldset', 1,  0, NULL, 0,    true, $n, 6);
        $c->add('ad_wm_auto',            $_MG_DEFAULT['ad_wm_auto'],               'select',   1,  0, 0,    $o++, true, $n, 6);
        $c->add('ad_wm_opacity',         $_MG_DEFAULT['ad_wm_opacity'],            'select',   1,  0, 22,   $o++, true, $n, 6);
        $c->add('ad_wm_location',        $_MG_DEFAULT['ad_wm_location'],           'select',   1,  0, 23,   $o++, true, $n, 6);
        $c->add('ad_wm_id',              $_MG_DEFAULT['ad_wm_id'],                 'select',   1,  0, NULL, $o++, true, $n, 6);
        // ----------------------------------
        $c->add('tab_allowedmediatypes', NULL,                                     'tab',      1,  0, NULL, 0,    true, $n, 7);
        $c->add('fs_allowedmediatypes',  NULL,                                     'fieldset', 1,  0, NULL, 0,    true, $n, 7);
        $c->add('ad_valid_format_jpg',   $_MG_DEFAULT['ad_valid_format_jpg'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_png',   $_MG_DEFAULT['ad_valid_format_png'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_tif',   $_MG_DEFAULT['ad_valid_format_tif'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_gif',   $_MG_DEFAULT['ad_valid_format_gif'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_bmp',   $_MG_DEFAULT['ad_valid_format_bmp'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_tga',   $_MG_DEFAULT['ad_valid_format_tga'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_psd',   $_MG_DEFAULT['ad_valid_format_psd'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_mp3',   $_MG_DEFAULT['ad_valid_format_mp3'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_ogg',   $_MG_DEFAULT['ad_valid_format_ogg'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_asf',   $_MG_DEFAULT['ad_valid_format_asf'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_swf',   $_MG_DEFAULT['ad_valid_format_swf'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_mov',   $_MG_DEFAULT['ad_valid_format_mov'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_mp4',   $_MG_DEFAULT['ad_valid_format_mp4'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_mpg',   $_MG_DEFAULT['ad_valid_format_mpg'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_flv',   $_MG_DEFAULT['ad_valid_format_flv'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_rflv',  $_MG_DEFAULT['ad_valid_format_rflv'],     'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_emb',   $_MG_DEFAULT['ad_valid_format_emb'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_zip',   $_MG_DEFAULT['ad_valid_format_zip'],      'select',   1,  0, 0,    $o++, true, $n, 7);
        $c->add('ad_valid_format_other', $_MG_DEFAULT['ad_valid_format_other'],    'select',   1,  0, 0,    $o++, true, $n, 7);

        // ----------------------------------
        $c->add('tab_useruploads',       NULL,                                     'tab',      1,  0, NULL, 0,    true, $n, 8);
        $c->add('fs_useruploads',        NULL,                                     'fieldset', 1,  0, NULL, 0,    true, $n, 8);
        $c->add('ad_member_uploads',     $_MG_DEFAULT['ad_member_uploads'],        'select',   1,  0, 0,    $o++, true, $n, 8);
        $c->add('ad_moderate',           $_MG_DEFAULT['ad_moderate'],              'select',   1,  0, 0,    $o++, true, $n, 8);
        $c->add('ad_mod_group_id',       $_MG_DEFAULT['ad_mod_group_id'],          'select',   1,  0, NULL, $o++, true, $n, 8);
        $c->add('ad_email_mod',          $_MG_DEFAULT['ad_email_mod'],             'select',   1,  0, 0,    $o++, true, $n, 8);

        // ----------------------------------
        $c->add('tab_accessrights',      NULL,                                     'tab',      1,  0, NULL, 0,    true, $n, 9);
        $c->add('fs_accessrights',       NULL,                                     'fieldset', 1,  0, NULL, 0,    true, $n, 9);
        $c->add('ad_group_id',           $_MG_DEFAULT['ad_group_id'],              'select',   1,  0, NULL, $o++, true, $n, 9);
        // ----------------------------------
        $c->add('fs_permissions',        NULL,                                     'fieldset', 1,  1, NULL, 0,    true, $n, 9);
        $c->add('ad_permissions',        $_MG_DEFAULT['ad_permissions'],           '@select',  1,  1, 12,   $o++, true, $n, 9);

        // ----------------------------------

        $c->add('sg_av',                 NULL,                                     'subgroup', 2,  0, NULL, 0,    true, $n, 0);
        // ----------------------------------
        $c->add('tab_wmedia',            NULL,                                     'tab',      2,  0, NULL, 0,    true, $n, 10);
        $c->add('fs_wmedia',             NULL,                                     'fieldset', 2,  0, NULL, 0,    true, $n, 10);
        $c->add('asf_autostart',         $_MG_DEFAULT['asf_autostart'],            'select',   2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_enablecontextmenu', $_MG_DEFAULT['asf_enablecontextmenu'],    'select',   2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_stretchtofit',      $_MG_DEFAULT['asf_stretchtofit'],         'select',   2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_showstatusbar',     $_MG_DEFAULT['asf_showstatusbar'],        'select',   2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_uimode',            $_MG_DEFAULT['asf_uimode'],               'select',   2,  0, 24,   $o++, true, $n, 10);
        $c->add('asf_playcount',         $_MG_DEFAULT['asf_playcount'],            'text',     2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_bgcolor',           $_MG_DEFAULT['asf_bgcolor'],              'text',     2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_width',             $_MG_DEFAULT['asf_width'],                'text',     2,  0, 0,    $o++, true, $n, 10);
        $c->add('asf_height',            $_MG_DEFAULT['asf_height'],               'text',     2,  0, 0,    $o++, true, $n, 10);
        // ----------------------------------
        $c->add('tab_quicktime',         NULL,                                     'tab',      2,  0, NULL, 0,    true, $n, 11);
        $c->add('fs_quicktime',          NULL,                                     'fieldset', 2,  0, NULL, 0,    true, $n, 11);
        $c->add('mov_autoref',           $_MG_DEFAULT['mov_autoref'],              'select',   2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_autoplay',          $_MG_DEFAULT['mov_autoplay'],             'select',   2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_controller',        $_MG_DEFAULT['mov_controller'],           'select',   2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_kioskmode',         $_MG_DEFAULT['mov_kioskmode'],            'select',   2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_scale',             $_MG_DEFAULT['mov_scale'],                'select',   2,  0, 25,   $o++, true, $n, 11);
        $c->add('mov_loop',              $_MG_DEFAULT['mov_loop'],                 'select',   2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_bgcolor',           $_MG_DEFAULT['mov_bgcolor'],              'text',     2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_width',             $_MG_DEFAULT['mov_width'],                'text',     2,  0, 0,    $o++, true, $n, 11);
        $c->add('mov_height',            $_MG_DEFAULT['mov_height'],               'text',     2,  0, 0,    $o++, true, $n, 11);
        // ----------------------------------
        $c->add('tab_mp3',               NULL,                                     'tab',      2,  0, NULL, 0,    true, $n, 12);
        $c->add('fs_mp3',                NULL,                                     'fieldset', 2,  0, NULL, 0,    true, $n, 12);
        $c->add('mp3_autostart',         $_MG_DEFAULT['mp3_autostart'],            'select',   2,  0, 0,    $o++, true, $n, 12);
        $c->add('mp3_enablecontextmenu', $_MG_DEFAULT['mp3_enablecontextmenu'],    'select',   2,  0, 0,    $o++, true, $n, 12);
        $c->add('mp3_showstatusbar',     $_MG_DEFAULT['mp3_showstatusbar'],        'select',   2,  0, 0,    $o++, true, $n, 12);
        $c->add('mp3_loop',              $_MG_DEFAULT['mp3_loop'],                 'select',   2,  0, 0,    $o++, true, $n, 12);
        $c->add('mp3_uimode',            $_MG_DEFAULT['mp3_uimode'],               'select',   2,  0, 24,   $o++, true, $n, 12);
        // ----------------------------------
        $c->add('tab_flashmedia',        NULL,                                     'tab',      2,  0, NULL, 0,    true, $n, 13);
        $c->add('fs_flashmedia',         NULL,                                     'fieldset', 2,  0, NULL, 0,    true, $n, 13);

        $c->add('swf_play',              $_MG_DEFAULT['swf_play'],                 'select',   2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_menu',              $_MG_DEFAULT['swf_menu'],                 'select',   2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_scale',             $_MG_DEFAULT['swf_scale'],                'select',   2,  0, 26,   $o++, true, $n, 13);
        $c->add('swf_wmode',             $_MG_DEFAULT['swf_wmode'],                'select',   2,  0, 27,   $o++, true, $n, 13);
        $c->add('swf_allowscriptaccess', $_MG_DEFAULT['swf_allowscriptaccess'],    'select',   2,  0, 28,   $o++, true, $n, 13);
        $c->add('swf_quality',           $_MG_DEFAULT['swf_quality'],              'select',   2,  0, 29,   $o++, true, $n, 13);
        $c->add('swf_loop',              $_MG_DEFAULT['swf_loop'],                 'select',   2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_bgcolor',           $_MG_DEFAULT['swf_bgcolor'],              'text',     2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_width',             $_MG_DEFAULT['swf_width'],                'text',     2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_height',            $_MG_DEFAULT['swf_height'],               'text',     2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_flashvars',         $_MG_DEFAULT['swf_flashvars'],            'text',     2,  0, 0,    $o++, true, $n, 13);
        $c->add('swf_version',           $_MG_DEFAULT['swf_version'],              'text',     2,  0, 0,    $o++, true, $n, 13);

        // ----------------------------------

        $c->add('sg_member_album',       NULL,                                     'subgroup', 3,  0, NULL, 0,    true, $n, 0);
        // ----------------------------------
        $c->add('tab_member_albums',     NULL,                                     'tab',      3,  0, NULL, 0,    true, $n, 14);
        $c->add('fs_member_albums',      NULL,                                     'fieldset', 3,  0, NULL, 0,    true, $n, 14);
        $c->add('member_albums',         $_MG_DEFAULT['member_albums'],            'select',   3,  0, 0,    $o++, true, $n, 14);
        $c->add('allow_remote',          $_MG_DEFAULT['allow_remote'],             'select',   3,  0, 0,    $o++, true, $n, 14);
        $c->add('member_use_fullname',   $_MG_DEFAULT['member_use_fullname'],      'select',   3,  0, 0,    $o++, true, $n, 14);
        $c->add('feature_member_album',  $_MG_DEFAULT['feature_member_album'],     'select',   3,  0, 0,    $o++, true, $n, 14);
        $c->add('member_quota',          $_MG_DEFAULT['member_quota'],             'text',     3,  0, 0,    $o++, true, $n, 14);
        $c->add('member_auto_create',    $_MG_DEFAULT['member_auto_create'],       'select',   3,  0, 0,    $o++, true, $n, 14);
        $c->add('member_create_new',     $_MG_DEFAULT['member_create_new'],        'select',   3,  0, 0,    $o++, true, $n, 14);
        $c->add('member_album_root',     $_MG_DEFAULT['member_album_root'],        'select',   3,  0, NULL, $o++, true, $n, 14); //////
        $c->add('member_album_archive',  $_MG_DEFAULT['member_album_archive'],     'select',   3,  0, NULL, $o++, true, $n, 14); //////
        // ----------------------------------
        $c->add('tab_member_allowedmediatypes', NULL,                                      'tab',      3,  0, NULL, 0,    true, $n, 15);
        $c->add('fs_member_allowedmediatypes',  NULL,                                      'fieldset', 3,  0, NULL, 0,    true, $n, 15);
        $c->add('member_valid_format_jpg',      $_MG_DEFAULT['member_valid_format_jpg'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_png',      $_MG_DEFAULT['member_valid_format_png'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_tif',      $_MG_DEFAULT['member_valid_format_tif'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_gif',      $_MG_DEFAULT['member_valid_format_gif'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_bmp',      $_MG_DEFAULT['member_valid_format_bmp'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_tga',      $_MG_DEFAULT['member_valid_format_tga'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_psd',      $_MG_DEFAULT['member_valid_format_psd'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_mp3',      $_MG_DEFAULT['member_valid_format_mp3'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_ogg',      $_MG_DEFAULT['member_valid_format_ogg'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_asf',      $_MG_DEFAULT['member_valid_format_asf'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_swf',      $_MG_DEFAULT['member_valid_format_swf'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_mov',      $_MG_DEFAULT['member_valid_format_mov'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_mp4',      $_MG_DEFAULT['member_valid_format_mp4'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_mpg',      $_MG_DEFAULT['member_valid_format_mpg'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_flv',      $_MG_DEFAULT['member_valid_format_flv'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_rflv',     $_MG_DEFAULT['member_valid_format_rflv'],  'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_emb',      $_MG_DEFAULT['member_valid_format_emb'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_zip',      $_MG_DEFAULT['member_valid_format_zip'],   'select',   3,  0, 0,    $o++, true, $n, 15);
        $c->add('member_valid_format_other',    $_MG_DEFAULT['member_valid_format_other'], 'select',   3,  0, 0,    $o++, true, $n, 15);
        // ----------------------------------
        $c->add('tab_member_album_attributes',  NULL,                                      'tab',      3,  0, NULL, 0,    true, $n, 16);
        $c->add('fs_member_album_attributes',   NULL,                                      'fieldset', 3,  0, NULL, 0,    true, $n, 16);
        $c->add('member_enable_random',         $_MG_DEFAULT['member_enable_random'],      'select',   3,  0, 0,    $o++, true, $n, 16);
        $c->add('member_max_width',             $_MG_DEFAULT['member_max_width'],          'text',     3,  0, 0,    $o++, true, $n, 16);
        $c->add('member_max_height',            $_MG_DEFAULT['member_max_height'],         'text',     3,  0, 0,    $o++, true, $n, 16);
        $c->add('member_max_filesize',          $_MG_DEFAULT['member_max_filesize'],       'text',     3,  0, 0,    $o++, true, $n, 16);
        // ----------------------------------
        $c->add('tab_member_useruploads',       NULL,                                     'tab',       3,  0, NULL, 0,    true, $n, 17);
        $c->add('fs_member_useruploads',        NULL,                                     'fieldset',  3,  0, NULL, 0,    true, $n, 17);
        $c->add('member_uploads',               $_MG_DEFAULT['member_uploads'],           'select',    3,  0, 0,    $o++, true, $n, 17);
        $c->add('member_moderate',              $_MG_DEFAULT['member_moderate'],          'select',    3,  0, 0,    $o++, true, $n, 17);
        $c->add('member_mod_group_id',          $_MG_DEFAULT['member_mod_group_id'],      'select',    3,  0, NULL, $o++, true, $n, 17);
        $c->add('member_email_mod',             $_MG_DEFAULT['member_email_mod'],         'select',    3,  0, 0,    $o++, true, $n, 17);
        // ----------------------------------
        $c->add('tab_member_accessrights',      NULL,                                     'tab',       3,  0, NULL, 0,    true, $n, 18);
        $c->add('fs_member_permissions',        NULL,                                     'fieldset',  3,  0, NULL, 0,    true, $n, 18);
        $c->add('member_permissions',           $_MG_DEFAULT['member_permissions'],       '@select',   3,  0, 12,   $o++, true, $n, 18);
    }

    return true;
}
?>