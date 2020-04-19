<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | functions.inc                                                            |
// |                                                                          |
// | This file does two things: 1) it implements the necessary glFusion Plugin|
// | API method and 2) implements all the common code needed by the Media     |
// | Gallery PHP files.                                                       |
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

if (strpos(strtolower($_SERVER['PHP_SELF']), strtolower(basename(__FILE__))) !== false) {
    die('This file can not be used on its own!');
}

/**
* Include language file
*/
$plugin_path = $_CONF['path'] . 'plugins/mediagallery/';
$langfile = $plugin_path . 'language/' . $_CONF['language'] . '.php';
if (file_exists($langfile)) {
    require_once $langfile;
} else {
    require_once $plugin_path . 'language/english.php';
}

//global $_MG_CONF;
$_MG_CONF = array();
$_MG_CONF['pi_version'] = '1.7.2.1';
$_MG_CONF['installed_version'] = DB_getItem($_TABLES['plugins'], 'pi_version', "pi_name = 'mediagallery'");
$_MG_CONF['var_current_code'] = version_compare($_MG_CONF['installed_version'], $_MG_CONF['pi_version'], '>=');

/**
* Add to $_TABLES array the tables mediagallery plugin uses
*/
$_TABLES['mg_albums']           = $_DB_table_prefix . 'mg_albums';
$_TABLES['mg_media']            = $_DB_table_prefix . 'mg_media';
$_TABLES['mg_media_albums']     = $_DB_table_prefix . 'mg_media_albums';
$_TABLES['mg_usage_tracking']   = $_DB_table_prefix . 'mg_usage_tracking';
$_TABLES['mg_mediaqueue']       = $_DB_table_prefix . 'mg_mediaqueue'; // renamed from mg_media_queue to mg_mediaqueue
$_TABLES['mg_media_album_queue']= $_DB_table_prefix . 'mg_media_album_queue';
$_TABLES['mg_playback_options'] = $_DB_table_prefix . 'mg_playback_options';
$_TABLES['mg_userprefs']        = $_DB_table_prefix . 'mg_userprefs';
$_TABLES['mg_exif_tags']        = $_DB_table_prefix . 'mg_exif_tags';
$_TABLES['mg_watermarks']       = $_DB_table_prefix . 'mg_watermarks';
$_TABLES['mg_category']         = $_DB_table_prefix . 'mg_category';
$_TABLES['mg_sessions']         = $_DB_table_prefix . 'mg_sessions';
$_TABLES['mg_session_items']    = $_DB_table_prefix . 'mg_session_items';
$_TABLES['mg_session_log']      = $_DB_table_prefix . 'mg_session_log';
$_TABLES['mg_sort']             = $_DB_table_prefix . 'mg_sort';
$_TABLES['mg_rating']           = $_DB_table_prefix . 'mg_rating';
//$_TABLES['mg_mediasubmission']  = $_DB_table_prefix . 'mg_mediaqueue';
//$_TABLES['mg_mediasubmission']  = $_DB_table_prefix . 'mg_media_queue';

$_TABLES['mg_old_mediaqueue']   = $_DB_table_prefix . 'mg_media_queue';
$_TABLES['mg_config']           = $_DB_table_prefix . 'mg_config';

/**
 * Media Gallery Global Configuration Options
 */

/*
 * Media Gallery Path Configuration. If you want to move the directory where
 * the Media Gallery programs are stored and accessed, change the
 * $_MG_CONF['path_mg'] to the new directory name.
 *
 * You normally do not need to change any of the other paths.
 */
$_MG_CONF['path_html']  = $_CONF['path_html'] . 'mediagallery/';
$_MG_CONF['site_url']   = $_CONF['site_url'] . '/mediagallery';
$_MG_CONF['admin_url']  = $_CONF['site_admin_url'] . '/plugins/mediagallery/';
$_MG_CONF['path_admin'] = $_CONF['path_html'] . 'admin/plugins/mediagallery/';

$_MG_CONF['path_mediaobjects'] = $_CONF['path_html'] . 'mediagallery/mediaobjects/';
$_MG_CONF['mediaobjects_url']  = $_CONF['site_url']  . '/mediagallery/mediaobjects';

$_MG_CONF['template_path']     = $_CONF['path'] . 'plugins/mediagallery/templates';

$_MG_CONF['watermark_types'] = array('image/jpeg','image/jpg','image/png','image/bmp');

$_MG_CONF['link_to_member_album'] = 1;


/*
 * By default, Media Gallery plays MP4 video files as QuickTime
 * videos, using the QuickTime player.
 *
 * If you prefer to use the Flash Video Player for MP4 files,
 * set this value to true.
 *
 */
$_MG_CONF['play_mp4_flv'] = false;
//$_MG_CONF['play_mp4_flv'] = true;

/*
 * Number of seconds to wait between ratings
 */
$_MG_CONF['rating_speedlimit'] = 45;

/*
 * Set the maximum number of media items to display in the Media Manage Screen
 */

$_MG_CONF['mediamanage_items'] = 200;

/*
 * If set to 1, MG will not automatically populate the video playback
 *  resolution, instead it will use the default settings
 */

$_MG_CONF['use_default_resolution'] = 0;

/*
 * if set to 1, MG will use the larger stars in the album view for ratings
 */

$_MG_CONF['use_large_stars'] = 0;

/*
 * By default, Media Gallery will play MPEG videos with Apple's QuickTime
 * player. If you wish to use Microsoft's Windows Media Player instead,
 * set this variable to 1.
 */

$_MG_CONF['use_wmp_mpeg'] = 0;

/*
 * By default, Media Gallery will attempt to extract the actual image capture
 * date from the meta data included by many digital cameras and graphics
 * packages.  If you prefer to always use the upload date (the date you added
 * the image to your Media Gallery album) as the displayed date, set this
 * variable to 1.
 */

$_MG_CONF['use_upload_time'] = 0;

/**
 * FFMPEG Command line options
 *
 * There are lots of versions of ffmpeg out there, some work with one set of
 * switches and some don't.  You can easily modify the commands used to create
 * thumbnails from videos by modifying the arguments below.  The first %s is
 * the source video and the second %s is the destination thumbnail, both %s
 * need to be in the command.
 */

$_MG_CONF['ffmpeg_command_args'] = ' -i %s -f mjpeg -t 0.01 -y %s';

// $_MG_CONF['ffmpeg_command_args'] = ' -i %s -f mjpeg -an -r 1 -ss 00:00:03 -vframes 1 -y %s';
// $_MG_CONF['ffmpeg_command_args'] = ' -i %s -f singlejpeg -an -r 1 -ss 00:00:03 -vframes 1 -y %s';
// --- RIVA FLV Encoded / converter for Windows command line
// $_MG_CONF['ffmpeg_command_args'] = ' -an -y  -i "%s" -t 0.001 -s 640x480 -deinterlace   -hq -f image2 "%s"';


/*
 * Default thumbnails for non-media type files
 *
 * This allows you to specify new default thumbnails for non-media file types.
 * Place the thumbnail image in the mediaobjects/ directory.
 *
 */

$_MG_CONF['dt']['pdf'] = 'pdf.png';
$_MG_CONF['dt']['zip'] = 'zip.png';

/*
 * The JavaScript used by the lightbox slideshow can cause
 * conflicts with other JS libraries.  You can set this to true to disable
 * the lightbox slideshow feature.
 */
$_MG_CONF['disable_lightbox'] = false;

/*
 * If you want Media Gallery to update the 'last updated' time for all parent
 * albums, set this to true.
 */
$_MG_CONF['update_parent_lastupdated'] = true; //false;

/*
 * If you want to allow users who 'own' (uploaded) a media item to be able
 * to also edit the media item, set this value to true.
 */
$_MG_CONF['allow_user_edit'] = false; // Do we need this?

/*
 * Media Gallery provides limited support for including remotely hosted
 * images in your albums.  Your web host must support the CURL PHP extension
 * or allow opening remote URLs to take full advantage of this features.
 * To enable remote image support, set this value to true.
 */
$_MG_CONF['enable_remote_images'] = true;

/*
 * Set whether or not click image and go next image
 *   true: display the next image
 *   false: change the display size (full size or normal size)
 */
$_MG_CONF['click_image_and_go_next'] = true;

/*
 * Set whether or not hide the dialog select the destination album, on the media display page
 */
$_MG_CONF['hide_jumpbox_on_mediaview'] = true;

/*
 * Set whether or not loop turning over the media display page at the top or the end
 */
$_MG_CONF['enable_loop_pagination'] = true;

/*
 * Set the default of the shape (aspect ratio) of a random image on the side block area
 * 0:original, 1:square
 */
$_MG_CONF['random_img_ratio'] = 0;


/* --------------------------------------------------------------------------------- */

define("MG_JPG",1);
define("MG_PNG",2);
define("MG_TIF",4);
define("MG_GIF",8);
define("MG_BMP",16);
define("MG_TGA",32);
define("MG_PSD",64);
define("MG_MP3",128);
define("MG_OGG",256);
define("MG_ASF",512);
define("MG_SWF",1024);
define("MG_MOV",2048);
define("MG_MP4",4096);
define("MG_MPG",8192);
define("MG_ZIP",16384);
define("MG_OTHER",32768);
define("MG_PDF",65536);
define("MG_FLV",131072);
define("MG_RFLV",262144);
define("MG_EMB",524288);

if (!defined ('UC_SELECTED')) define('UC_SELECTED', (XHTML == '') ? 'selected' : 'selected="selected"');
if (!defined ('UC_CHECKED'))  define('UC_CHECKED',  (XHTML == '') ? 'checked'  : 'checked="checked"'  );
if (!defined ('UC_READONLY')) define('UC_READONLY', (XHTML == '') ? 'readonly' : 'readonly="readonly"');
if (!defined ('UC_DISABLED')) define('UC_DISABLED', (XHTML == '') ? 'disabled' : 'disabled="disabled"');

// This is a list of valid extensions for image media thumbnails / display images
$_MG_CONF['validExtensions'] = array('.jpg','.png','.gif','.bmp');

// placeholder until implemented
$_MG_CONF['ad_shopping_cart'] = 0;

$swfjsinclude = 0;
$mgLightBox = 0;

$_MG_CONF['random_skin'] = 'default';

$_MG_CONF['tmp_path'] = $_CONF['path'] . 'plugins/mediagallery/tmp/';

$_MG_CONF['rater_units_num'] = 5;

/**
* Load the plugin configuration
*/

require_once $_CONF['path_system'] . 'classes/config.class.php';
$plg_config = config::get_instance();
$temp = $plg_config->get_config('mediagallery');
if (is_array($temp)) {
    $_MG_CONF = array_merge($_MG_CONF, $temp);
}

if ($_CONF['loginrequired'] == 1) {
    $_MG_CONF['loginrequired'] = 1;
}

/*
$_MG_CONF['up_mp3_player_enabled'] = 0;

if ($_MG_CONF['installed_version']) {
    $_MG_CONF['dfid'] = '19';
    $_MG_CONF['dateformat'] = DB_getItem($_TABLES['dateformats'], 'format', "dfid = {$_MG_CONF['dfid']}");
    // safety checks....
    if ($_MG_CONF['album_display_columns'] < 1) {
        $_MG_CONF['album_display_columns'] = 1;
    }
    if ($_MG_CONF['album_display_rows'] < 1) {
        $_MG_CONF['album_display_rows'] = 9;
    }
}
*/
if (version_compare($_MG_CONF['installed_version'], '1.7.0', '>=')) {
    $_MG_CONF['dateformat'] = DB_getItem($_TABLES['dateformats'], 'format', "dfid = {$_MG_CONF['dfid']}");
}

$_MG_CONF['path_to_imagemagick'] = '';
if ($_CONF['image_lib'] == 'imagemagick') {
    $tmpstr = $_CONF['path_to_mogrify'];
    if (strtolower(substr($tmpstr, -4)) == '.exe') {
        $tmpstr = substr($tmpstr, 0, strlen($tmpstr) - 4);
    }
    if (strtolower(substr($tmpstr, -8)) == '/mogrify') {
        $tmpstr = substr($tmpstr, 0, strlen($tmpstr) - 7); // Note that the path must end in a slash
    }
    $_MG_CONF['path_to_imagemagick'] = $tmpstr;
}


$_MG_CONF['ad_valid_formats'] =
    $_MG_CONF['ad_valid_format_jpg'] * MG_JPG +
    $_MG_CONF['ad_valid_format_png'] * MG_PNG +
    $_MG_CONF['ad_valid_format_tif'] * MG_TIF +
    $_MG_CONF['ad_valid_format_gif'] * MG_GIF +
    $_MG_CONF['ad_valid_format_bmp'] * MG_BMP +
    $_MG_CONF['ad_valid_format_tga'] * MG_TGA +
    $_MG_CONF['ad_valid_format_psd'] * MG_PSD +
    $_MG_CONF['ad_valid_format_mp3'] * MG_MP3 +
    $_MG_CONF['ad_valid_format_ogg'] * MG_OGG +
    $_MG_CONF['ad_valid_format_asf'] * MG_ASF +
    $_MG_CONF['ad_valid_format_swf'] * MG_SWF +
    $_MG_CONF['ad_valid_format_mov'] * MG_MOV +
    $_MG_CONF['ad_valid_format_mp4'] * MG_MP4 +
    $_MG_CONF['ad_valid_format_mpg'] * MG_MPG +
    $_MG_CONF['ad_valid_format_flv'] * MG_FLV +
    $_MG_CONF['ad_valid_format_rflv'] * MG_RFLV +
    $_MG_CONF['ad_valid_format_emb'] * MG_EMB +
    $_MG_CONF['ad_valid_format_zip'] * MG_ZIP +
    $_MG_CONF['ad_valid_format_other'] * MG_OTHER;

$_MG_CONF['member_valid_formats'] =
    $_MG_CONF['member_valid_format_jpg'] * MG_JPG +
    $_MG_CONF['member_valid_format_png'] * MG_PNG +
    $_MG_CONF['member_valid_format_tif'] * MG_TIF +
    $_MG_CONF['member_valid_format_gif'] * MG_GIF +
    $_MG_CONF['member_valid_format_bmp'] * MG_BMP +
    $_MG_CONF['member_valid_format_tga'] * MG_TGA +
    $_MG_CONF['member_valid_format_psd'] * MG_PSD +
    $_MG_CONF['member_valid_format_mp3'] * MG_MP3 +
    $_MG_CONF['member_valid_format_ogg'] * MG_OGG +
    $_MG_CONF['member_valid_format_asf'] * MG_ASF +
    $_MG_CONF['member_valid_format_swf'] * MG_SWF +
    $_MG_CONF['member_valid_format_mov'] * MG_MOV +
    $_MG_CONF['member_valid_format_mp4'] * MG_MP4 +
    $_MG_CONF['member_valid_format_mpg'] * MG_MPG +
    $_MG_CONF['member_valid_format_flv'] * MG_FLV +
    $_MG_CONF['member_valid_format_rflv'] * MG_RFLV +
    $_MG_CONF['member_valid_format_emb'] * MG_EMB +
    $_MG_CONF['member_valid_format_zip'] * MG_ZIP +
    $_MG_CONF['member_valid_format_other'] * MG_OTHER;

if ($_MG_CONF['usage_tracking']) {
    $_MG_CONF['last_usage_purge'] = DB_getItem($_TABLES['vars'], 'value', "name = 'mg_last_usage_purge'");
}

$_MG_CONF['verbose'] = false;

/**
* Check to see if this Plugin uses comments
*
* @return   boolean true or false
*
*/
function plugin_commentsupport_mediagallery()
{
    return true;
}

/**
 * Plugin function to save a comment
 *
 * @param   string  $title  comment title
 * @param   string  $comment comment text
 * @param   string  $id     Item id to which $cid belongs
 * @param   int     $pid    comment parent
 * @param   string  $postmode 'html' or 'text'
 * @return  mixed   false for failure, HTML string (redirect?) for success
 */
function plugin_savecomment_mediagallery($title, $comment, $id, $pid, $postmode)
{
    global $_CONF, $_MG_CONF, $_TABLES, $LANG03;

    $retval = '';

    $sql = "SELECT a.enable_comments "
         . "FROM {$_TABLES['mg_albums']} AS a "
         . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON a.album_id = ma.album_id "
         . "WHERE ma.media_id = '$id'";
    $result = DB_query($sql);
    $A = DB_fetchArray($result);

    if ($A['enable_comments'] != 1) {
        COM_redirect($_CONF['site_url'] . '/index.php');
    }

    $ret = CMT_saveComment($title, $comment, $id, $pid, 'mediagallery', $postmode);

    if ($ret > 0) { // failure //FIXME: some failures should not return to comment form
        $retval .= CMT_commentForm($title, $comment, $id, $pid, 'mediagallery',
                                  $LANG03[14], $postmode);
        if (!defined('COMMENT_ON_SAME_PAGE') || !COMMENT_ON_SAME_PAGE) {
            $retval = COM_createHTMLDocument($retval, array('pagetitle' => $LANG03[1]));
        }
        return $retval;
    } else { // success
        $comments = DB_count($_TABLES['comments'], array('sid', 'type'), array(DB_escapeString($id), 'mediagallery'));
        DB_change($_TABLES['mg_media'], 'media_comments', $comments, 'media_id', DB_escapeString($id));
        COM_redirect($_MG_CONF['site_url'] . "/media.php?s=$id");
    }

    return false;
}

/**
 * Plugin function to delete a comment
 *
 * @param   int     $cid    Comment to be deleted
 * @param   string  $id     Item id to which $cid belongs
 * @return  mixed   false for failure, HTML string (redirect?) for success
 */
function plugin_deletecomment_mediagallery($cid, $id)
{
    global $_CONF, $_MG_CONF, $_TABLES;

    // find the album that holds this peice of media
    $sql = "SELECT album_id FROM {$_TABLES['mg_media_albums']} WHERE media_id='" . DB_escapeString($id) . "'";
    $result = DB_query($sql);
    $nRows  = DB_numRows($result);
    $access = 0;
    if ($nRows > 0) {
        $row = DB_fetchArray($result);
        require_once $_CONF['path'].'plugins/mediagallery/include/classAlbum.php';
        $root_album = new mgAlbum(0);                // root album
        $album      = new mgAlbum($row['album_id']); // current album
        $access = 3;
        if (!$root_album->owner_id) {
            $access = $album->access;
        }
    }

    if ($access == 3 && SEC_hasRights('mediagallery.admin')) {
        if (CMT_deleteComment($cid, $id, 'mediagallery') == 0) {
            $comments = DB_count($_TABLES['comments'], array('sid', 'type'), array(DB_escapeString($id), 'mediagallery'));
            DB_change($_TABLES['mg_media'], 'media_comments', $comments, 'media_id', DB_escapeString($id));
            COM_redirect($_MG_CONF['site_url'] . "/media.php?s=$id");
        }
    }

    return false;
}

function plugin_getcommenturlid_mediagallery()
{
    global $_CONF, $_MG_CONF;
    $retval = array();
    $retval[] = $_MG_CONF['site_url'] . '/media.php';
    $retval[] = 's';
    $retval[] = 'page=';
    return $retval;
}

/**
 * Plugin should display [a] comment[s]
 *
 * @param   string  $id     Unique idenifier for item comment belongs to
 * @param   int     $cid    Comment id to display (possibly including sub-comments)
 * @param   string  $title  Page/comment title
 * @param   string  $order  'ASC' or 'DESC' or blank
 * @param   string  $format 'threaded', 'nested', or 'flat'
 * @param   int     $page   Page number of comments to display
 * @param   boolean $view   True to view comment (by cid), false to display (by $pid)
 * @return  mixed   results of calling the plugin_displaycomment_ function
 */
function plugin_displaycomment_mediagallery($id, $cid, $title, $order, $format, $page, $view)
{
    global $_TABLES, $LANG_ACCESS, $LANG_MG00;

    $retval = '';

    $sql = "SELECT COUNT(*) AS count, a.enable_comments, "
         . "a.owner_id, a.group_id, a.perm_owner, "
         . "a.perm_group, a.perm_members, a.perm_anon "
         . "FROM {$_TABLES['mg_albums']} AS a "
         . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON a.album_id = ma.album_id "
         . "WHERE ma.media_id = '$id'" . COM_getPermSQL('AND')
         . " GROUP BY ma.media_id";
    $result = DB_query($sql);
    $A = DB_fetchArray($result);
    $allowed = $A['count'];

    if ($allowed == 1 && $A['enable_comments'] == 1) {
        $delete_option = (SEC_hasRights('mediagallery.admin') &&
                (SEC_hasAccess($A['owner_id'], $A['group_id'],
                    $A['perm_owner'], $A['perm_group'], $A['perm_members'],
                    $A['perm_anon']) == 3));
        $commentcode = 0;
        $retval .= CMT_userComments($id, $title, 'mediagallery', $order, $format,
                       $cid, $page, $view, $delete_option, $commentcode);
    } else {
        $retval .= COM_startBlock($LANG_ACCESS['accessdenied'], '',
                        COM_getBlockTemplate('_msg_block', 'header'))
                . $LANG_MG00['deny_msg']
                . COM_endBlock(COM_getBlockTemplate ('_msg_block', 'footer'));
    }

    return $retval;
}

/**
* Returns the items for this plugin that should appear on the main menu
*
* NOTE: this MUST return the url/value pairs in the following format
* $<arrayname>[<label>] = <url>
*
* Universal Plugin:  Uncomment and fill in variables;
*
* @return    array   Associative array of menu items to be added to menu
*
*/

function plugin_getmenuitems_mediagallery()
{
    global $_CONF, $_USER, $_MG_CONF, $LANG_MG03;

    if (COM_isAnonUser() && $_MG_CONF['loginrequired'] == 1) return '';

    $menulabel = $LANG_MG03['menulabel'];
    $menurul   = $_MG_CONF['site_url'] . "/index.php";     // Menu url here

    $menuitems = array();
    $menuitems["$menulabel"] = $menurul;

    return $menuitems;
}


function plugin_getheadercode_mediagallery()
{
    global $_CONF, $_MG_CONF, $_SCRIPTS, $mgLightBox, $swfjsinclude;

    $retval = '';

    //
    // Check to see if the template path is using the plugins/ directory, if yes,
    // we must point to the stylesheet in public_html/mediagallery/
    //
    if ($_MG_CONF['template_path'] == $_CONF['path'] . 'plugins/mediagallery/templates') {
        $styleFile = $_MG_CONF['site_url'] . '/style.css';
    } else {
        $styleFile = $_CONF['layout_url'] . '/mediagallery/style.css';
    }
    $retval .= LB . '<link rel="stylesheet" type="text/css" href="' . $styleFile . '"' . XHTML . '>' . LB;

    $_SCRIPTS->setJavaScriptFile('swfobject', '/mediagallery/js/swfobject_2.1.js', false);

    $_SCRIPTS->setJavaScriptFile('mediagallery', '/mediagallery/js/mediagallery.js');

    $_SCRIPTS->setJavaScriptLibrary('jquery');

    if (! $_MG_CONF['disable_lightbox']) {
        $_SCRIPTS->setJavaScriptFile('jquery.slimbox2', '/mediagallery/js/slimbox2.js');
    }

    $_SCRIPTS->setJavaScriptFile('jquery.cyclelite', '/mediagallery/js/jquery.cycle.lite.min.js');

    $swfjsinclude = 1;

    $_SCRIPTS->setCSSFile('mg.framecss' . $_MG_CONF['random_skin'] , '/mediagallery/frames/' . $_MG_CONF['random_skin'] . '/style.css', false);

    return $retval;
}

/**
  * Whats New Block API Support
  * Return the Headline and Byline for the new section in the Whatsnew Block
*/
function plugin_whatsnewsupported_mediagallery()
{
    global $_CONF, $_MG_CONF, $LANG_MG00;

    if ($_MG_CONF['whatsnew'] == 0) return '';
    if ($_MG_CONF['installed_version'] != $_MG_CONF['pi_version']) return '';
    if (COM_isAnonUser() && $_MG_CONF['loginrequired'] == 1) return '';

    $label = sprintf($LANG_MG00['whatsnew_prompt'], $_MG_CONF['whatsnew_time']);

    return array($LANG_MG00['whatsnew_title'], $label);
}

/**
  * API function provides the content of our "What's New" feed
*/
function plugin_getwhatsnew_mediagallery()
{
    global $_TABLES, $_CONF, $_MG_CONF, $_USER, $LANG_MG03;

    require_once $_CONF['path'].'plugins/mediagallery/include/classAlbum.php';

    $retval = '';
    $newMediaCount = 0;
    $newCommentCount = 0;
    $items = array();
    $newAge = time() - ($_MG_CONF['whatsnew_time'] * 86400);
    $sql = "SELECT album_id FROM {$_TABLES['mg_albums']} WHERE last_update >= '" . $newAge . "' ORDER BY last_update DESC";
    $result = DB_query($sql,1);
    while ($row = DB_fetchArray($result)) {
        $album = new mgAlbum($row['album_id']);
        if ($album->access > 0 && $album->hidden != 1 && $album->getOffset() != -1) {
            $title = $album->title;
            if (strlen($title) > $_MG_CONF['title_length']) {
                $dTitle = substr($title,0,$_MG_CONF['title_length'] - 3) . '...';
            } else {
                $dTitle = $title;
            }
            $items[] = COM_createLink($dTitle,
                                      $_MG_CONF['site_url'] . '/album.php?aid=' . $row['album_id'],
                                      array('title' => htmlspecialchars($title, ENT_COMPAT, COM_getCharset())));
            $newMediaCount++;
        }
    }
    if ($newMediaCount == 0) {
        $retval .= $LANG_MG03['no_new_items'] . '<br' . XHTML . '>' . LB;
    }
    if ($_MG_CONF['disable_whatsnew_comments'] == 0) {
        $sql = "SELECT * FROM {$_TABLES['comments']} AS c "
             . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON c.sid=ma.media_id "
             . "LEFT JOIN {$_TABLES['mg_albums']} AS a ON ma.album_id=a.album_id "
             . "WHERE c.date >=  DATE_SUB(NOW(), INTERVAL " . $_MG_CONF['whatsnew_time'] . " DAY ) "
             . "AND c.type='mediagallery' GROUP BY c.sid ORDER BY c.date DESC LIMIT 15";

        $result = DB_query($sql, 1);
        $nrows2 = DB_numRows($result);
        if ($nrows2 == 0) {
            $retval .= '' . '<br' . XHTML . '>' . LB;
        } else {
            while ($A = DB_fetchArray($result)) {
                $access = SEC_hasAccess($A['owner_id'],$A['group_id'],$A['perm_owner'],
                                        $A['perm_group'],$A['perm_members'],$A['perm_anon']);
                if ($access > 0) {
                    $titleLength = $_MG_CONF['title_length'];        // Compensate for the added HTML
                    $title = "<strong>C:</strong>&nbsp;" . $A['title'];
                    $items[] = COM_createLink(substr($title, 0, $titleLength),
                               "{$_CONF['site_url']}/comment.php?mode=view&amp;cid={$A['cid']}&amp;sid={$A['sid']}&amp;type=mediagallery");
                    $newCommentCount++;
                }
            }
        }
        if ($newCommentCount == 0) {
            $retval .= $LANG_MG03['no_comments'] . '<br' . XHTML . '>' . LB;
        }
    }
    if ($newMediaCount == 0 && $newCommentCount == 0) {
        return $retval;
    } else {
        return $items;
    }
}

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
function plugin_showstats_mediagallery($showsitestats)
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/statistics.php';
    return MG_showstats($showsitestats);
}


function plugin_statssummary_mediagallery()
{
    global $_CONF, $_MG_CONF, $_TABLES, $LANG_MG00, $_USER;

    if (COM_isAnonUser() && $_MG_CONF['loginrequired'] == 1) return '';

    // This shows in the summary box
    $total_items=DB_count($_TABLES['mg_media']);
    $summary_label = $LANG_MG00['items_in'] . ' ' .  $LANG_MG00['plugin'];
    $retval[] = $summary_label;
    $retval[] = $total_items;
    return $retval;
}



/**
* This will put an option for the plugin in the command and control block on moderation.php
*
* Add the plugin name, icon and link to the command and control block in moderation.php
*
* @return   array   Array containing (plugin name, admin url, url of plugin icon)
*
*/

function plugin_cclabel_mediagallery()
{
    global $_CONF, $_MG_CONF, $LANG_MG00;

    if (SEC_hasRights('mediagallery.config')) {
        return array($LANG_MG00['plugin'],
            $_MG_CONF['admin_url'] . 'index.php',
            plugin_geticon_mediagallery());
    }

    return false;
}

/**
* Returns the URL of the plugin's icon
*
* @return   string      URL of the icon
*
*/
function plugin_geticon_mediagallery()
{
    global $_CONF;

    return $_CONF['site_url'] . '/mediagallery/images/mediagallery.png';
}

/**
* Returns the administrative option for this plugin
*
* Adds the plugin to the Admin menu
*
* Universal Plugin:  Modify/Uncomment use it
*
* @return   array   Array containing (plugin name, plugin admin url, # of items in plugin or '')
*
*/
function plugin_getadminoption_mediagallery()
{
    global $_CONF, $_TABLES, $LANG_MG00, $_MG_CONF;

    if (SEC_hasRights('mediagallery.config')) {
        return array($LANG_MG00['plugin'], $_MG_CONF['admin_url'] . 'index.php', DB_count($_TABLES['mg_albums']));
    }
}


function plugin_autotags_mediagallery ($op, $content = '', $autotag = '')
{
    global $_CONF, $_TABLES, $_MG_CONF;

    if ($_MG_CONF['installed_version'] != $_MG_CONF['pi_version']) {
        return $content;
    }
    if ($op == 'tagname') {
        return array('album','media','img','slideshow','fslideshow','video','audio',
                     'download','image','oimage','mlink','alink','playall',
                     'gallery');
    } else if ($op == 'parse') {
        require_once $_CONF['path'] . 'plugins/mediagallery/include/autotags.php';
        return MG_autotags($op, $content, $autotag);
    }
}


/**
* returns list of moderation values
*
* The array returned contains (in order): the row 'id' label, main plugin
* table, moderation fields (comma seperated), and plugin submission table
*
* @return       array        Returns array of useful moderation values
*
*/
function plugin_moderationvalues_mediagallery()
{
    global $_TABLES;

    return array (
        'media_id',
        $_TABLES['mg_media'],
        'media_id, media_filename, media_original_filename, media_mime_ext, media_exif, mime_type, '
        . 'media_title, media_desc, media_keywords, media_time, media_views, media_comments, '
        . 'media_votes, media_rating, media_resolution_x, media_resolution_y, remote_media, '
        . 'remote_url, media_tn_attached, media_tn_image, include_ss, media_user_id, '
        . 'media_user_ip, media_approval, media_type, media_upload_time, media_category, '
        . 'media_watermarked, artist, album, genre, v100, maint',
        $_TABLES['mg_mediaqueue']);
}

/**
* show files for moderation on submissions page
*
* Uses the Plugin class to return data required by moderation.php to list
* plugin objects that need to be moderated.
*
* @return       Plugin       return Plugin class containing submission data
*
*/
function plugin_itemlist_mediagallery()
{
    global $_CONF, $_TABLES, $LANG_MG01;

    if (plugin_ismoderator_mediagallery()) {
        $plugin = new Plugin();
        $plugin->submissionlabel = $LANG_MG01['media_submissions'];
        if (file_exists($_CONF['path_html'] . 'help/ccmediagallerysubmission.html')) {
            $plugin->submissionhelpfile = 'ccmediagallerysubmission.html';
        }
        $plugin->getsubmissionssql = "SELECT media_id AS id, media_title AS title, FROM_UNIXTIME(media_upload_time), mime_type "
                                   . "FROM {$_TABLES['mg_mediaqueue']} ORDER BY media_title ASC";

        $plugin->addSubmissionHeading($LANG_MG01['title']);
        $plugin->addSubmissionHeading($LANG_MG01['date']);
        $plugin->addSubmissionHeading($LANG_MG01['mime_type']);

        return $plugin;
    }
    return;
}

/**
* Returns the plugins submission form
*
* Returns a string contain the HTML of the submission form for the plugin.
*
* @return       string       The HTML form
*
*/
function plugin_submit_mediagallery()
{
    $retval = '<h1>submit file</h1>';
    $retval .= '<p>This would be the input form for uploading files. ';
    $retval .= 'But that functionality is already implemented elsewhere.</p>';

    return($retval);
}

/**
*
* Checks that the current user has the rights to moderate the
* plugin, returns true if this is the case, false otherwise
*
* @return        boolean       Returns true if moderator
*
*/
function plugin_ismoderator_mediagallery()
{
    return SEC_hasRights('mediagallery.admin');
}

/**
* Returns the number of submissions waiting moderation
*
* Calculates the number of submissions that are currently waiting
* to be moderated and returns that number.
*
* @return      int       number of submissions awaiting moderation
*/
function plugin_submissioncount_mediagallery()
{
    global $_MG_CONF, $_TABLES;

    $retval = 0;

    $table = $_TABLES['mg_old_mediaqueue'];
    if (version_compare($_MG_CONF['installed_version'], $_MG_CONF['pi_version']) >= 0) {
        $table = $_TABLES['mg_mediaqueue'];
    }

    if (plugin_ismoderator_mediagallery()) {
        $retval = DB_count($table);
    }

    return $retval;
}

/**
* Performs plugin exclusive work for items approved by moderation
*
* While moderation.php handles the actual move from mediagallery submission
* to mediagallery tables, within the function we handle all other approval
* relate tasks
*
* @param      string       $id      Identifying string
* @return     string       Any wanted HTML output
*
*/
function plugin_moderationapprove_mediagallery($id)
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/moderate.php';
    MG_approveSubmission($id);

    return '';
}

/**
* Performs plugin exclusive work for items deleted by moderation
*
* While moderation.php handles the actual removal from mediagallery submission
* table, within this function we handle all other deletion
* relate tasks
*
* @param      string       $id      Identifying string
* @return     string       Any wanted HTML output
*
*/
function plugin_moderationdelete_mediagallery($id)
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/moderate.php';
    MG_deleteSubmission($id);

    return '';
}

/**
* saves submission to mediagallery submission table
*
* takes data input by submission form and populates the mediagallery submission
* table.
*
* @param       array       $A       array (key => value) of form items
* @return      boolean     returns true if successful
*
*/
function plugin_savesubmission_mediagallery($A)
{
    return false;
}

function plugin_searchtypes_mediagallery()
{
    global $_MG_CONF, $LANG_MG00;

    if (COM_isAnonUser() && $_MG_CONF['loginrequired'] == 1) return '';

    $tmp['mediagallery'] = $LANG_MG00['plugin'];
    return $tmp;
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
function plugin_dopluginsearch_mediagallery($query, $datestart, $dateend, $topic, $type, $author, $keyType, $page, $perpage)
{
    global $_TABLES, $_CONF, $_MG_CONF, $LANG_MG00, $LANG_MG01, $LANG09;

    if (COM_isAnonUser() && $_MG_CONF['loginrequired'] == 1) return '';

    $query = trim(DB_escapeString(htmlspecialchars($query)));

    $sql = "SELECT DISTINCT "
         . "m.media_id AS id, "
         . "m.media_user_id AS uid, "
         . "m.media_desc AS description, "
         . "m.media_title AS title, "
         . "m.media_time AS date, "
         . "m.media_views AS hits, "
         . "CONCAT('/mediagallery/media.php?s=', m.media_id) AS url "
         . "FROM {$_TABLES['mg_media']} AS m "
         . "INNER JOIN {$_TABLES['mg_media_albums']} AS ma ON m.media_id = ma.media_id "
         . "INNER JOIN {$_TABLES['mg_albums']} AS a ON ma.album_id = a.album_id "
         . "WHERE 1=1 ";
    $sql .= COM_getPermSQL('AND') . ' ';

    if (!SEC_inGroup('Root')) {
        $sql .= "AND hidden = 0 ";
    }

    if (!empty($author)) {
        $sql .= "AND m.media_user_id = '$author' ";
    }

    $search = new SearchCriteria('mediagallery', $LANG_MG00['plugin']);
    $columns = array('title' => 'm.media_title', 'm.media_desc');
    $sql .= $search->getDateRangeSQL('AND', 'm.media_time', $datestart, $dateend);
    list($sql,$ftsql) = $search->buildSearchSQL($keyType, $query, $columns, $sql);
    $search->setSQL($sql);
    $search->setFTSQL($ftsql);
    $search->setRank(3);

    return $search;
}


/**
* Returns the user option for this plugin
*
* Adds the plugin to the user menu
*
* Universal Plugin:  modify/uncomment use it
*
* @return   array   Array containing (plugin name, plugin user url, 0)
*
*/
function plugin_getuseroption_mediagallery()
{
    global $_CONF, $_MG_CONF, $_USER, $LANG_MG01, $LANG_MG03, $_TABLES;

    if (COM_isAnonUser()) return '';

    $menuitems = array();

    if ($_MG_CONF['up_display_rows_enabled'] != 0 ||
        $_MG_CONF['up_display_columns_enabled'] != 0 ||
        $_MG_CONF['up_mp3_player_enabled'] != 0 ||
        $_MG_CONF['up_av_playback_enabled'] != 0 ||
        $_MG_CONF['up_thumbnail_size_enabled'] != 0) {
        $menuitems[] = array($LANG_MG01['user_prefs'], $_MG_CONF['site_url'] . '/userprefs.php', 0);
    }

    if ($_MG_CONF['member_albums'] == 1 && $_MG_CONF['link_to_member_album'] == 1) {
        $sql = "SELECT album_id FROM {$_TABLES['mg_albums']} WHERE owner_id=" . $_USER['uid']
             . " AND album_parent='" . $_MG_CONF['member_album_root'] . "' LIMIT 1";
        $result = DB_query($sql);
        $numRows = DB_numRows($result);
        if ($numRows > 0) {
            $A = DB_fetchArray($result);
            $album_id = $A['album_id'];
            $menuitems[] = array($LANG_MG03['your_member_album'], $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id, 0);
        }
    }

    return $menuitems;
}

function phpblock_mg_randommedia($parm1=array(), $parm2='')
{
    global $_CONF, $_MG_CONF, $_TABLES, $_USER, $LANG_MG03, $_DB_dbms;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';

    $retval = '';

    if ($_MG_CONF['installed_version'] != $_MG_CONF['pi_version']) {
        return $retval;
    }

    $tn_size = 120;
    if (!empty($_MG_CONF['random_width'])) {
        $tn_size = intval($_MG_CONF['random_width']);
    }

    $tn_mode = $_MG_CONF['random_img_ratio'];
    if (!empty($parm2)) {
        $args = explode(',', $parm2);
        switch ($args[0]) {
            case 'cube':
            case 'square':
            case 1:
                $tn_mode = 1;
                break;
            case 'normal':
            case 'original':
            case 0:
                $tn_mode = 0;
                break;
        }
        if (!empty($args[1])) {
            $tn_size = intval($args[1]);
        }
    }
    if ($tn_size < 10)  $tn_size = 100;
    if ($tn_size > 200) $tn_size = 200;
    $tn_fname_add = ($tn_mode == 1) ? '_150x150' : '';

    $randomSelect = ($_DB_dbms == 'mssql') ? "NewID()" : "RAND(NOW())";
    $sql = "SELECT ma.album_id,m.media_id,m.media_filename,m.media_title "
         . "FROM {$_TABLES['mg_albums']} AS a LEFT JOIN {$_TABLES['mg_media_albums']} AS ma "
         . "ON a.album_id=ma.album_id LEFT JOIN {$_TABLES['mg_media']} AS m ON ma.media_id=m.media_id "
         . "WHERE m.media_type=0 AND a.enable_random=1 AND a.hidden=0 " . COM_getPermSQL('and')
         . " ORDER BY " . $randomSelect . " LIMIT 1";
    $result = DB_query($sql,1);
    $nRows  = DB_numRows($result);
    if ($nRows <= 0) return '';
    $row = DB_fetchArray($result);

    $url_media = $_MG_CONF['site_url'] . '/media.php?s=' . $row['media_id'];
    $url_album = $_MG_CONF['site_url'] . '/album.php?aid=' . $row['album_id'] .'&amp;s='.$row['media_id'].'#'.$row['media_id'];

    $msize = false;
    foreach ($_MG_CONF['validExtensions'] as $ext) {
        if (file_exists($_MG_CONF['path_mediaobjects'] . 'tn/' . $row['media_filename'][0] .'/' . $row['media_filename'] . $tn_fname_add . $ext)) {
            $media_thumbnail= $_MG_CONF['mediaobjects_url'] . '/tn/' . $row['media_filename'][0] .'/' . $row['media_filename'] . $tn_fname_add . $ext;
            $msize = @getimagesize($_MG_CONF['path_mediaobjects'] . 'disp/' . $row['media_filename'][0] .'/' . $row['media_filename'] . $ext);
            break;
        }
    }

    if ($msize == false) return '';

    if ($tn_mode == 1) {
        $width  = $tn_size;
        $height = $tn_size;
    } else {
        require_once $_CONF['path'] . 'plugins/mediagallery/include/classMedia.php';
        list($width, $height) = Media::getImageWH($msize[0], $msize[1], $tn_size, $tn_size);
    }

    if ($parm2 == 'album') {
        $url_media = $url_album;
    }

    $media_item_thumbnail = MG_getFramedImage($_MG_CONF['random_skin'], $row['media_title'], $url_media, $media_thumbnail, $width, $height);

    $T = COM_newTemplate(MG_getTemplatePath(0));
    $T->set_file('rblock', 'random_block.thtml');
    $T->set_var(array(
        'url_media'         => $url_media,
        'url_album'         => $url_album,
        'media_thumbnail'   => $media_item_thumbnail,
        'media_height'      => $height,
        'media_width'       => $width,
        'media_title'       => PLG_replaceTags(strip_tags($row['media_title'])),
        'lang_browse_album' => $LANG_MG03['browse_album'],
    ));
    $T->parse('output', 'rblock');
    $retval .= $T->finish($T->get_var('output'));

    return $retval;
}

function phpblock_mg_maenroll()
{
    global $_CONF, $_MG_CONF, $_TABLES, $_USER, $LANG_MG03;

    if (COM_isAnonUser()) return;

    $_MG_USERPREFS = MG_getUserPrefs();
    if ($_MG_CONF['member_albums'] != 1 || $_MG_USERPREFS['member_gallery'] == 1 || $_USER['uid'] < 3) {
        return;
    }

    if (SEC_inGroup("Remote Users") && $_MG_CONF['allow_remote'] != 1) {
        return;
    }

    $url = sprintf($LANG_MG03['member_album_signup'], $_MG_CONF['site_url'] . '/enroll.php');
    return $url;
}

/* Discard
function plugin_centerblock_mediagallery($where=1, $page=1, $topic ='')
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/centerblock.php';
    return MG_centerblock($where, $page, $topic);
}
*/

function plugin_chkVersion_mediagallery()
{
    global $_MG_CONF;

    return $_MG_CONF['pi_version'];
}


function MG_clean_directories($path, $needle = '')
{
    if ($path[strlen($path)-1] != '/') {
        $path .= '/';
    }
    if ($dir = @opendir($path)) {
        while ($entry = readdir($dir)) {
            if ($entry == '.' || $entry == '..' || is_link($entry)) {
                continue;
            } elseif (is_dir($path . $entry)) {
                MG_clean_directories($path . $entry, $needle);
                @rmdir($entry);
            } elseif (empty($needle) || strpos($entry, $needle) !== false) {
                @unlink($path . $entry);
            }
        }
        @closedir($dir);
    }
}

function plugin_upgrade_mediagallery()
{
    global $_CONF, $_TABLES, $_MG_CONF;
/*
    require_once $_CONF['path'].'plugins/mediagallery/upgrade.php';

    return mediagallery_upgrade();
*/
    require_once $_CONF['path'].'plugins/mediagallery/autoinstall.php';

    return MG_upgrade();
}

/**
* Automatic uninstall function for plugins
*
* @return   array
*
* This code is automatically uninstalling the plugin.
* It passes an array to the core code function that removes
* tables, groups, features and php blocks from the tables.
* Additionally, this code can perform special actions that cannot be
* foreseen by the core code (interactions with other plugins for example)
*
*/
function plugin_autouninstall_mediagallery()
{
    $out = array (
        /* give the name of the tables, without $_TABLES[] */
        'tables' => array('mg_albums', 'mg_media', 'mg_media_albums', 'mg_usage_tracking',
                          'mg_mediaqueue', 'mg_media_album_queue', 'mg_playback_options',
                          'mg_userprefs', 'mg_exif_tags', 'mg_watermarks', 'mg_category', 'mg_sessions',
                          'mg_session_items', 'mg_session_log', 'mg_sort', 'mg_rating'),
        /* give the full name of the group, as in the db */
        'groups' => array('mediagallery Admin', 'mediagallery Config'),
        /* give the full name of the feature, as in the db */
        'features' => array('mediagallery.admin', 'mediagallery.config'),
        /* give the full name of the block, including 'phpblock_', etc */
        'php_blocks' => array('phpblock_mg_randommedia', 'phpblock_mg_maenroll'),
        /* give all vars with their name */
        'vars'=> array('mediagallery_gid', 'mediagallery_cid', 'mg_last_usage_purge')
    );
    return $out;
}

/**
* Removes the data structures for this plugin from the Geeklog database.
* This routine will get called from the Plugin install program if user select De-Install or if Delete is used in the Plugin Editor.
* The Plugin Installer will also call this routine upon and install error to remove anything it has created.
* The Plugin installer will pass the optional parameter which will then double check that plugin has first been disabled.
*
* For this plugin, this routine will also remove the Block definition.
*
* Returns True if all Plugin related data is removed without error
*
* @param    string   $installCheck     Defult is blank but if set, check if plugin is disabled first
*
* @return   boolean True if successful false otherwise
*
*/
function plugin_uninstall_mediagallery($installCheck='')
{
    global $_CONF, $_DB_table_prefix, $_TABLES, $LANG_MG00;

    // Check and see if plugin is still enabled - if so display warning and exit
    if ($installCheck != '' && DB_getItem($_TABLES['plugins'],'pi_enabled', 'pi_name = "' .$pi_name. '"')) {
        COM_errorLog("Plugin is installed and enabled. Disable first if you want to de-install it",1);
        $display .= COM_startBlock($LANG_MG00['warning']);
        $display .= $LANG_MG00['enabled'];
        $display .= COM_endBlock();
        echo $display;
        return false;
    }

    require_once $_CONF['path'] . 'plugins/mediagallery/install.inc';

    $rc = plugin_remove_mediagallery();

    return $rc;
}

function plugin_user_create_mediagallery($uid, $force = 0)
{
    global $_TABLES, $_MG_CONF, $LANG_MG01, $_CONF;

    $sql = "INSERT INTO {$_TABLES['mg_userprefs']} (uid, active, display_rows, display_columns, mp3_player, playback_mode, tn_size, quota, member_gallery) "
         . "VALUES (" . intval($uid) . ",1,0,0,-1,-1,-1," . $_MG_CONF['member_quota'] . ",0)";
    DB_query($sql, 1);

    $retval = -1;
    if ($force == 1 || ($_MG_CONF['member_albums'] == 1 && $_MG_CONF['member_auto_create'] == 1)) {
        $username  = DB_getItem($_TABLES['users'], 'username', 'uid=' . intval($uid));
        $fullname  = DB_getItem($_TABLES['users'], 'fullname', 'uid=' . intval($uid));
        $grp_id    = DB_getItem($_TABLES['groups'], 'grp_id', 'grp_name="mediagallery Admin"');

        if ($grp_id == NULL || $grp_id == '' || $grp_id < 2) {
            $grp_id = 2;
        }

        require_once $_CONF['path'] . 'plugins/mediagallery/include/classAlbum.php';
        $album = new mgAlbum();

        $title = $username . $LANG_MG01['member_album_postfix'];
        if (!empty($fullname) && $_MG_CONF['member_use_fullname'] == 1) {
            $title = $fullname . $LANG_MG01['member_album_postfix'];
        }
        
        $album->title           = htmlspecialchars(strip_tags(COM_checkWords($title)));
        $album->parent          = $_MG_CONF['member_album_root'];
        $album->group_id        = $grp_id;
        $album->mod_group_id    = $grp_id;
        $album->owner_id        = $uid;
        $album->moderate        = $_MG_CONF['member_moderate'];
        $album->email_mod       = $_MG_CONF['member_email_mod'];
        $album->perm_owner      = $_MG_CONF['member_permissions'][0];
        $album->perm_group      = $_MG_CONF['member_permissions'][1];
        $album->perm_members    = $_MG_CONF['member_permissions'][2];
        $album->perm_anon       = $_MG_CONF['member_permissions'][3];
        $retval = $album->id;
        $album->saveAlbum();
        DB_change($_TABLES['mg_userprefs'], 'member_gallery', 1, 'uid', intval($uid));
    }
    return $retval;
}

function plugin_user_delete_mediagallery($uid)
{
    global $_USER, $_CONF, $_TABLES, $_MG_CONF, $LANG_MG00, $LANG_MG01;

    // remove any user preferences in the database

    DB_delete($_TABLES['mg_userprefs'], 'uid', intval($uid));

    if ($_MG_CONF['member_albums']) {
        if ($_MG_CONF['member_album_archive'] == 0) {
            $sql = "SELECT album_id FROM {$_TABLES['mg_albums']} WHERE owner_id=" . intval($uid);
            $result = DB_query($sql);
            while ($A = DB_fetchArray($result)) {
                MG_deleteMemberAlbums($A['album_id']);
            }
        } else {
            // update parent album to be archive album
            DB_change($_TABLES['mg_albums'], 'album_parent', $_MG_CONF['member_album_archive'],
                      array('owner_id', 'album_parent'),
                      array(intval($uid), $_MG_CONF['member_album_root']));
        }
    }
}

function MG_deleteMemberAlbums($album_id) 
{
    global $_CONF, $_MG_CONF, $_TABLES, $_USER;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
    $sql = MG_buildMediaSql(array(
        'album_id'  => $album_id,
        'fields'    => array('media_id', 'media_filename', 'media_mime_ext'),
        'sortorder' => -1
    ));
    $result = DB_query($sql);
    while ($A = DB_fetchArray($result)) {
        $count = DB_count($_TABLES['mg_media_albums'], 'media_id', DB_escapeString($A['media_id']));
        if ($count <= 1) {
            $fn = $A['media_filename'];
            @unlink($_MG_CONF['path_mediaobjects'] . 'tn/'   . $fn[0] . '/' . $fn . '.*');
            @unlink($_MG_CONF['path_mediaobjects'] . 'disp/' . $fn[0] . '/' . $fn . '.*');
            @unlink($_MG_CONF['path_mediaobjects'] . 'orig/' . $fn[0] . '/' . $fn . '.' . $A['media_mime_ext']);
            DB_delete($_TABLES['mg_media'], 'media_id', DB_escapeString($A['media_id']));
            DB_delete($_TABLES['comments'], 'sid', DB_escapeString($A['media_id']));
            DB_delete($_TABLES['mg_playback_options'], 'media_id', DB_escapeString($A['media_id']));
        }
    }
    DB_delete($_TABLES['mg_media_albums'], 'album_id', intval($album_id));
    DB_delete($_TABLES['mg_albums'], 'album_id', intval($album_id));
}


// display user info in profile

function plugin_profileblocksdisplay_mediagallery( $uid )
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/profile.php';
    return MG_profileblocksdisplay($uid);
}

/*
 * Helper functions for executing external programs...
 */

function MG_exec($cmd)
{
    global $_CONF, $_MG_CONF;

    $debugfile = "";
    $status="";
    $results=array();
    if ($_MG_CONF['verbose']) {
        COM_errorLog(sprintf("MG_exec: Executing: %s",$cmd));
    }

    $debugfile = $_CONF['path'] . 'logs/debug.log';

    if (PHP_OS == "WINNT") {
        $cmd .= ">NUL 2>&1";
//      exec("cmd.exe /c " . $cmd, $results, $status);
        exec('"' . $cmd . '"', $results, $status);
    } else {
        exec($cmd, $results, $status);
    }

    return array($results, $status);
}

// Used in climport.php, lib-batch.php and lib-upload.php
function MG_execWrapper($cmd)
{
    list($results, $status) = MG_exec($cmd);

    if ($status == 0) {
        return true;
    } else {
        COM_errorLog("MG_execWrapper: Failed Command: " . $cmd);
        return false;
    }
}

/**
* Return information for a media item
*
* @param    string  $id         media id or *
* @param    string  $what       comma-separated list of properties
* @param    int     $uid        user ID or 0 = current user
* @param    array   $options    (reserved for future extensions)
* @return   mixed               string or array of strings with the information
*
*/
function plugin_getiteminfo_mediagallery($id, $what, $uid = 0, $options = array())
{
    global $_CONF, $_TABLES, $_MG_CONF, $LANG_MG00, $LANG_MG03;

    $properties = explode(',', $what);
    $fields = array();
    foreach ($properties as $p) {
        switch ($p) {
            case 'date-modified':
                $fields[] = 'UNIX_TIMESTAMP(media_upload_time) AS unixdate';
                break;
            case 'description':
            case 'excerpt':
            case 'raw-description' :
                $fields[] = 'media_desc';
                break;
            case 'id':
                $fields[] = 'a.media_id';
                break;
            case 'title':
                $fields[] = 'media_title';
                break;
            case 'label':
            case 'url':
                $fields[] = 'a.media_id';
                break;
            default:
                break;
        }
    }

    $fields = array_unique($fields);

    if (count($fields) == 0) {
        return array();
    }

    $sql = "SELECT " . implode(',', $fields)
         . ", owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon "
         . "FROM {$_TABLES['mg_media']} AS a "
         . "LEFT JOIN {$_TABLES['mg_media_albums']} AS b ON a.media_id=b.media_id "
         . "LEFT JOIN {$_TABLES['mg_albums']} AS c ON b.album_id=c.album_id";

    if ($id != '*') {
        $sql .= " WHERE a.media_id = '" . DB_escapeString($id) . "' LIMIT 1";
    }

    $result = DB_query($sql);
    $numRows = DB_numRows($result);

    $mgadmin = SEC_hasRights('mediagallery.admin');
    $retval = array();
    for ($i = 0; $i < $numRows; $i++) {
        $A = DB_fetchArray($result);
        $access = 3;
        if (!$mgadmin) {
            $access = SEC_hasAccess($A['owner_id'], $A['group_id'], $A['perm_owner'],
                                    $A['perm_group'], $A['perm_members'], $A['perm_anon']);
        }
        if ($access > 0) {
            $props = array();
            foreach ($properties as $p) {
                switch ($p) {
                    case 'date-modified':
                        $props['date-modified'] = $A['unixdate'];
                        break;
                    case 'description':
                    case 'excerpt':
                        $prop[$p] = PLG_replaceTags($A['media_desc']);
                        break;
                    case 'raw-description' :
                        $props[$p] = $A['media_desc'];
                        break;
                    case 'id':
                        $props['id'] = $A['media_id'];
                        break;
                    case 'title':
                        $props['title'] = stripslashes($A['media_title']);
                        break;
                    case 'url':
                        if (empty($A['id'])) {
                            $props['url'] = $_MG_CONF['site_url'].'/media.php?f=0&amp;sort=0&amp;s='.$id;
                        } else {
                            $props['url'] = $_MG_CONF['site_url'].'/media.php?f=0&amp;sort=0&amp;s='.$A['media_id'];
                        }
                        break;
                    case 'label':
                        $props['label'] = $LANG_MG03['menulabel'];
                        break;
                    case 'status':
                        $props['status'] = 1; // stub - default
                        break;
                    default:
                        $props[$p] = '';
                        break;
                }
            }
        }

        $mapped = array();
        foreach ($props as $key => $value) {
            if ($id == '*') {
                if ($value != '') {
                    $mapped[$key] = $value;
                }
            } else {
                $mapped[$key] = $value;
            }
        }

        if ($id == '*') {
            $retval[] = $mapped;
        } else {
            $retval = $mapped;
            break;
        }
    }

    if (($id != '*') && (count($retval) == 1)) {
        $retval = array_shift($retval);
    }

    return $retval;
}

/**
* Provide URL of a documentation file
*
* @param    string  $file   documentation file being requested, e.g. 'config'
* @return   mixed           URL or false when not available
*
*/
function plugin_getdocumentationurl_mediagallery($file)
{
    global $_CONF;

    static $docurl;

    switch ($file) {
    case 'index':
    case 'config':
        if (isset($docurl)) {
            $retval = $docurl;
        } else {
            $doclang = COM_getLanguageName();
            $docs = 'mediagallery/docs/' . $doclang . '/mediagallery.html';
            if (file_exists($_CONF['path_html'] . $docs)) {
                $retval = $_CONF['site_url'] . '/' . $docs;
            } else {
                $retval = $_CONF['site_url'] . '/mediagallery/docs/english/mediagallery.html';
            }
            $docurl = $retval;
        }
        break;

    default:
        $retval = false;
        break;
    }

    return $retval;
}

/**
* Provides text for a Configuration tooltip
*
* @param    string  $id     Id of config value
* @return   mixed           Text to use regular tooltip, NULL to use config 
*                           tooltip hack, or empty string when not available
*
*/
function plugin_getconfigtooltip_mediagallery($id)
{   
    // Use config tooltip hack where tooltip is read from the config documentation
    return;
}

function plugin_configmanager_select_dfid_mediagallery()
{
    global $_TABLES, $LANG_MG00, $LANG_dateformats;

    $retval = array();
    $retval[$LANG_MG00['no_date']] = 99;
    $result = DB_query("SELECT dfid,description FROM {$_TABLES['dateformats']}");
    while ($A = DB_fetchArray($result)) {
        if ($A['dfid'] == 0) {
            $retval[$LANG_dateformats[0]] = $A['dfid'];
        } else {
            $retval[$A['description']] = $A['dfid'];
        }
    }
    return $retval;
}

function plugin_configmanager_select_indextheme_mediagallery()
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
    $retval = array();
    $themes = MG_getThemes();
    foreach ($themes as $theme) {
        $words = explode('_', $theme);
        $bwords = array();
        foreach ($words as $th) {
            if ((strtolower($th[0]) == $th[0]) &&
                (strtolower($th[1]) == $th[1])) {
                $bwords[] = ucfirst($th);
            } else {
                $bwords[] = $th;
            }
        }
        $retval[implode(' ', $bwords)] = $theme;
    }
    return $retval;
}
function plugin_configmanager_select_ad_skin_mediagallery()
{
    return plugin_configmanager_select_indextheme_mediagallery();
}
function plugin_configmanager_select_search_album_skin_mediagallery()
{
    return plugin_configmanager_select_indextheme_mediagallery();
}

function plugin_configmanager_select_indexskin_mediagallery()
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
    $retval = array();
    $frames = MG_getFrames();
    foreach ($frames as $frame) {
        $retval[$frame['name']] = $frame['dir'];
    }
    return $retval;
}
function plugin_configmanager_select_random_skin_mediagallery()
{
    return plugin_configmanager_select_indexskin_mediagallery();
}
function plugin_configmanager_select_ad_image_skin_mediagallery()
{
    return plugin_configmanager_select_indexskin_mediagallery();
}
function plugin_configmanager_select_ad_display_skin_mediagallery()
{
    return plugin_configmanager_select_indexskin_mediagallery();
}
function plugin_configmanager_select_ad_album_skin_mediagallery()
{
    return plugin_configmanager_select_indexskin_mediagallery();
}
function plugin_configmanager_select_search_frame_skin_mediagallery()
{
    return plugin_configmanager_select_indexskin_mediagallery();
}

function plugin_configmanager_select_ad_wm_id_mediagallery()
{
    global $_TABLES, $LANG_MG01, $_USER;

    $retval = array();
    $retval[$LANG_MG01['no_watermark']] = '0';

    // now select what watermarks we have permission to use...
    $where = " WHERE wm_id<>0";
    if (!SEC_hasRights('mediagallery.config')) {
        $where .= " AND (owner_id=" . $_USER['uid'] . " OR owner_id=0)";
    }
    $sql = "SELECT wm_id, filename FROM {$_TABLES['mg_watermarks']}" . $where . " ORDER BY owner_id";
    $result = DB_query($sql);
    while ($A = DB_fetchArray($result)) {
        $retval[$A['filename']] = $A['wm_id'];
    }

    return $retval;
}

function plugin_configmanager_select_ad_mod_group_id_mediagallery()
{
    $retval = array();
    $groups = SEC_getUserGroups();
    foreach ($groups as $key => $val) {
        if ($val == 2 || $val == 13) continue;
        $retval[$key] = $val;
    }
    return $retval;
}
function plugin_configmanager_select_ad_group_id_mediagallery()
{
    return plugin_configmanager_select_ad_mod_group_id_mediagallery();
}
function plugin_configmanager_select_member_mod_group_id_mediagallery()
{
    return plugin_configmanager_select_ad_mod_group_id_mediagallery();
}

function plugin_configmanager_select_member_album_root_mediagallery()
{
    global $_CONF, $_MG_CONF;

    require_once $_CONF['path'].'plugins/mediagallery/include/classAlbum.php';
    $retval = array();
    $root_album = new mgAlbum(0);
    $root_album->getAlbumArray($retval, 1, -1);

    return $retval;
}
function plugin_configmanager_select_member_album_archive_mediagallery()
{
    global $_CONF, $_MG_CONF, $LANG_MG01;

    require_once $_CONF['path'].'plugins/mediagallery/include/classAlbum.php';
    $retval = array();
    $retval[$LANG_MG01['do_not_archive']] = '0';
    $root_album = new mgAlbum(0);
    $root_album->getAlbumArray($retval, 1, 0, -1);

    return $retval;
}

function MG_showAlbumTree($aid=0, $depth=0)
{
    global $_CONF;
    require_once $_CONF['path'].'plugins/mediagallery/include/common.php';
    return MG_showTree($aid, $depth);
}

?>