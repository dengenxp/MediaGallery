<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | common.php                                                               |
// |                                                                          |
// | Startup and general purpose routines                                     |
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

function p($var)
{
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}
function px($var)
{
    print_r($var);
    exit;
}

// read user prefs
function MG_getUserPrefs()
{
    global $_TABLES, $_USER;

    static $prefs = array();

    if (isset($_USER['uid'])) {
        if (empty($prefs)) {
            $result = DB_query("SELECT * FROM " . $_TABLES['mg_userprefs']
                             . " WHERE uid='" . intval($_USER['uid']) . "'");
            if (DB_numRows($result) == 1) {
                $prefs = DB_fetchArray($result);
            }
        }
    }
    return $prefs;
}

function MG_getSortOrder($enable_sort, $sortOrder)
{
    if ($enable_sort == 0) {
        return ' ORDER BY ma.media_order DESC';
    }

    switch ($sortOrder) {
        case 0 :    // default
            $orderBy = ' ORDER BY ma.media_order DESC';
            break;
        case 1 :    // default, reverse order
            $orderBy = ' ORDER BY ma.media_order ASC';
            break;
        case 2 :    //  upload time, DESC
            $orderBy = ' ORDER BY m.media_upload_time DESC';
            break;
        case 3 :
            $orderBy = ' ORDER BY m.media_upload_time ASC';
            break;
        case 4 :    // capture time, DESC
            $orderBy = ' ORDER BY m.media_time DESC';
            break;
        case 5 :
            $orderBy = ' ORDER BY m.media_time ASC';
            break;
        case 6 :
            $orderBy = ' ORDER BY m.media_rating DESC';
            break;
        case 7 :
            $orderBy = ' ORDER BY m.media_rating ASC';
            break;
        case 8 :
            $orderBy = ' ORDER BY m.media_views DESC';
            break;
        case 9 :
            $orderBy = ' ORDER BY m.media_views ASC';
            break;
        case 10 :
            $orderBy = ' ORDER BY m.media_title DESC';
            break;
        case 11 :
            $orderBy = ' ORDER BY m.media_title ASC';
            break;
        case 12 :
            $orderBy = ' ORDER BY m.media_original_filename DESC';
            break;
        case 13 :
            $orderBy = ' ORDER BY m.media_original_filename ASC';
            break;
        default :
            $orderBy = ' ORDER BY ma.media_order DESC';
            break;
    }
    return $orderBy;
}

function MG_buildMediaSql($options=array())
{
    global $_TABLES, $_DB_dbms;

    $album_id  = isset($options['album_id'])  ? intval($options['album_id'])  : 0;
    $sortorder = isset($options['sortorder']) ? intval($options['sortorder']) : 0;
    $offset    = isset($options['offset'])    ? intval($options['offset'])    : 0;
    $limit     = isset($options['limit'])     ? intval($options['limit'])     : 0;
    $fields    = isset($options['fields'])    ? $options['fields']            : '';

    if (empty($fields)) {
        $target = '*';
    } else if (is_array($fields)) {
        $farray = array();
        foreach($fields as $val) {
            $val = trim($val);
            if (!empty($val)) {
                $farray[] = 'm.' . $val;
            }
        }
        $target = implode(', ', $farray);
    } else {
        $target = $fields;
    }

    if ($_DB_dbms == "mssql") {
        if ($target == '*') {
            $target = "*,CAST(media_desc AS TEXT) AS media_desc";
        } else {
            $target = str_replace("m.media_desc", "CAST(media_desc AS TEXT) AS media_desc", $target);
        }
    }

    $where = '';
    if (isset($options['where'])) {
        $where = "WHERE " . $options['where'] . ' ';
    }
    if ($album_id > 0) {
        $where .= (empty($where) ? "WHERE" : "AND")
                . " ma.album_id = " . intval($album_id);
    }

    $orderstr = '';
    if ($sortorder >= 0) {
        $orderstr = MG_getSortOrder($album_id, $sortorder);
    }

    $limitstr = '';
    if ($limit > 0) {
        $limitstr = " LIMIT ";
        if ($offset > 0) {
            $limitstr .= $offset . ",";
        }
        $limitstr .= $limit;
    }

    $sql = "SELECT $target FROM {$_TABLES['mg_media_albums']} AS ma "
         . "INNER JOIN {$_TABLES['mg_media']} AS m "
         . "ON ma.media_id = m.media_id "
         . $where . $orderstr . $limitstr;

    return $sql;
}

function MG_createHTMLDocument(&$content, $title='', $meta='')
{
    global $_MG_CONF;

    $information = array(
        'pagetitle'  => $title,
        'headercode' => $meta
    );

    switch ($_MG_CONF['displayblocks']) {
        case 0 : // left only
            $information['what'] = 'menu';
            $information['rightblock'] = false;
            break;
        case 1 : // right only
            $information['what'] = 'none';
            $information['rightblock'] = true;
            break;
        case 2 : // left and right
            $information['what'] = 'menu';
            $information['rightblock'] = true;
            break;
        case 3 : // none
            $information['what'] = 'none';
            $information['rightblock'] = false;
            break;
        default :
            $information['what'] = 'menu';
            $information['rightblock'] = false;
            break;
    }

    return COM_createHTMLDocument($content, $information);
}

function MG_quotaUsage($uid)
{
    global $_MG_CONF, $_TABLES;

    $quota = 0;
    $sql = "SELECT album_disk_usage FROM {$_TABLES['mg_albums']} "
         . "WHERE owner_id=" . intval($uid);
    $result = DB_query($sql);
    while ($A = DB_fetchArray($result)) {
        $quota += $A['album_disk_usage'];
    }
    return $quota;
}

function MG_updateUsage($application, $album_title, $media_title, $media_id)
{
    global $_MG_CONF, $_USER, $_TABLES;

    $now = time();
    if ($now - $_MG_CONF['last_usage_purge'] > 5184000) {
        $purgetime = $now - 5184000; // 60 days
        DB_query("DELETE FROM {$_TABLES['mg_usage_tracking']} WHERE time < " . $purgetime);
        DB_change($_TABLES['vars'], 'value', $now, 'name', 'mg_last_usage_purge');
        COM_errorLog("Media Gallery: Purged old data from Usage Tracking Tables");
    }

    $log_time    = $now;
    $user_id     = intval($_USER['uid']);
    $user_ip     = DB_escapeString($REMOTE_ADDR);
    $user_name   = DB_escapeString($_USER['username']);
    $application = DB_escapeString($application);
    $title       = DB_escapeString($album_title);
    $ititle      = DB_escapeString($media_title);
    $media_id    = DB_escapeString($media_id);

    $sql = "INSERT INTO {$_TABLES['mg_usage_tracking']} "
         . "(time, user_id, user_ip, user_name, application, album_title, media_title, media_id) "
         . "VALUES ($log_time, $user_id, '$user_ip', '$user_name', '$application', '$title', '$ititle', '$media_id')";
    DB_query($sql);
}

//hacked COM_getUserDateTimeFormat to allow different format for Media Gallery

function MG_getUserDateTimeFormat($date = '')
{
    global $_TABLES, $_CONF, $_MG_CONF, $_SYSTEM;

    if ($date == '99') return '';

    // Get display format for time
    $dateformat = ($_MG_CONF['dfid'] == '0') ? $_CONF['date'] : $_MG_CONF['dateformat'];
    if (empty($date)) {
        // Date is empty, get current date/time
        $stamp = time();
    } elseif (is_numeric($date)) {
        // This is a timestamp
        $stamp = $date;
    } else {
        // This is a string representation of a date/time
        $stamp = strtotime($date);
    }

    // Format the date
    if (is_callable('COM_strftime')) {
        $date = COM_strftime($dateformat, $stamp);
    } else {
        $date = strftime($dateformat, $stamp);
    }

    if (isset($_SYSTEM['swedish_date_hack']) && ($_SYSTEM['swedish_date_hack'] == true) && function_exists('iconv')) {
        $date = iconv('ISO-8859-1', 'UTF-8', $date);
    }

    return array($date, $stamp);
}

function MG_replace_accents($str)
{
    $str = htmlentities($str, ENT_QUOTES, COM_getCharset());
    $str = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde);/', '$1', $str);
    return html_entity_decode($str);
}

function MG_getImageFile($image)
{
    global $_MG_CONF, $_CONF;

    if ($_MG_CONF['template_path'] == $_CONF['path'] . 'plugins/mediagallery/templates') {
        return $_MG_CONF['site_url'] . '/images/' . $image;
    }
    return $_CONF['layout_url'] . '/mediagallery/images/' . $image;
}

function MG_getImageFilePath($image)
{
    global $_MG_CONF, $_CONF;

    if ($_MG_CONF['template_path'] == $_CONF['path'] . 'plugins/mediagallery/templates') {
        return $_MG_CONF['path_html'] . 'images/' . $image;
    }
    return $_CONF['layout_path'] . '/mediagallery/images/' . $image;
}

function MG_getTemplatePath($aid)
{
    global $_TABLES, $_MG_CONF;

    if ($aid == 0) { // root album
        $skin = isset($_MG_CONF['indextheme']) ? $_MG_CONF['indextheme'] : 'default';
    } else {
        $skin = DB_getItem($_TABLES['mg_albums'], 'skin', "album_id = " . intval($aid));
    }
    if ($skin == 'default' || empty($skin)) {
        return $_MG_CONF['template_path'];
    }
    return (array($_MG_CONF['template_path'] . '/themes/' . $skin,
                  $_MG_CONF['template_path']));
}

function MG_getTemplatePath_byName($name = '')
{
    global $_MG_CONF;

    $skin = (!empty($name)) ? $name : 'default';
    if ($skin == 'default') {
        return $_MG_CONF['template_path'];
    }
    return (array($_MG_CONF['template_path'] . '/themes/' . $skin,
                  $_MG_CONF['template_path']));
}

function MG_getThemePublicJSandCSS(&$skin)
{
    global $_MG_CONF, $_SCRIPTS;

    if (empty($skin))  return '';
    if (file_exists($_MG_CONF['path_html'] . 'themes/' . $skin . '/javascript.js')) {
        $_SCRIPTS->setJavaScriptFile('mg.skin' . $skin, '/mediagallery/themes/' . $skin . '/javascript.js');
    }
    if (file_exists($_MG_CONF['path_html'] . 'themes/' . $skin . '/style.css')) {
        $_SCRIPTS->setCSSFile('mg.skincss' . $skin , '/mediagallery/themes/' . $skin . '/style.css', false);
    }
    return '';
}

function MG_getCSS(&$frame)
{
    global $_MG_CONF, $_SCRIPTS;

    $name = 'default';
    if (!empty($frame) && file_exists($_MG_CONF['path_html'] . 'frames/' . $frame . '/style.css')) {
        $name = $frame;
    }
    $_SCRIPTS->setCSSFile('mg.framecss' . $name , '/mediagallery/frames/' . $name . '/style.css', false);
    return '';
}

function MG_getThemes()
{
    global $_MG_CONF, $_CONF;

    $themes = array();
    $themes[0] = 'default';
    $index = 1;
    $directory = $_MG_CONF['template_path'] . '/themes/';
    $fd = @opendir($directory);
    if ($fd != false) {
        clearstatcache();
        while (($dir = @readdir($fd)) == true) {
            if (in_array($dir, array('..', '.', 'CVS'))) continue;
            if (substr($dir, 0 , 1) == '.') continue;
            if (@is_dir($directory . $dir)) {
                $themes[$index] = $dir;
                $index++;
            }
        }
        closedir($fd);
    }

    return $themes;
}

function MG_getSize($size)
{
    $bytes = array('B','KB','MB','GB','TB');
    foreach ($bytes as $val) {
        if ($size < 1024) break;
        $size = $size / 1024;
    }
    $dec = ($val == 'B' || $val == 'KB') ? 0 : 2;
    return number_format($size, $dec) . " " . $val;
}

/**
* Get the path of the feed directory or a specific feed file
*
* @param    string  $feedFile   (option) feed file name
* @return   string              path of feed directory or file
*
*/
function MG_getFeedPath($feedFile = '')
{
    return SYND_getFeedPath($feedFile);
}

/**
* Get the URL of the feed directory or a specific feed file
*
* @param    string  $feedfile   (option) feed file name
* @return   string              URL of feed directory or file
*
*/
function MG_getFeedUrl($feedfile = '')
{
    global $_CONF;

    $feedpath = SYND_getFeedPath();
    $url = substr_replace($feedpath, $_CONF['site_url'], 0,
                          strlen($_CONF['path_html']) - 1);
    $url .= $feedfile;

    return $url;
}

/**
* Convert k/m/g size string to number of bytes
*
* @param    string      val    a string expressing size in K, M or G
* @return   int                 the resultant value in bytes
*
*/
function MG_return_bytes($val)
{
   $val  = trim($val);
   $last = strtolower(substr($val, -1));
   $num = (int) substr($val, 1);
   
   switch($last) {
       // The 'G' modifier is available since PHP 5.1.0
       case 'g':
           $retval = $num * 1024 * 1024 * 1024;
           break;
           
       case 'm':
           $retval = $num * 1024 * 1024;
           break;
           
       case 'k':
           $retval = $num * 1024;
           break;
           
       default:
           $retval = $num;
           break;
   }
   
   return $retval;
}

/**
* Return the max upload file size for the specified album
*
* @param    intval      album_id        the album_id to return the max upload file size for
* @return   intval      upload_limit    the upload size imit, in bytes
*
* if the type cannot be determined from the extension because the extension is
* not known, then the default value is returned (even if null)
*
* NOTE: the album array must be pre-initialized via MG_AlbumsInit()
*
*/
function MG_getUploadLimit($album_id)
{
    global $_TABLES;

    $post_max = MG_return_bytes(ini_get('post_max_size'));
    $album_max = DB_getItem($_TABLES['mg_albums'], 'max_filesize', 'album_id = ' . intval($album_id));
    if($album_max > 0 && $album_max < $post_max) {
        return $album_max;
    }
    return $post_max;
}

/**
* Return a string of valid upload filetypes for the specified album
*
* @param    intval      album_id        the album_id to return the max upload file size for
* @return   string      valid_types     string of filetypes allowed, delimited by semicolons
*
* if the type cannot be determined from the extension because the extension is
* not known, then the default value is returned (even if null)
*
* NOTE: the album array must be pre-initialized via MG_AlbumsInit()
*
*/
function MG_getValidFileTypes($album_id)
{
    global $_TABLES;

    if ($album_id > 0) {
        $valid_formats = DB_getItem($_TABLES['mg_albums'], 'valid_formats', 'album_id = ' . intval($album_id));
    } else {
        $valid_formats = MG_JPG || MG_PNG || MG_GIF || MG_MP3 || MG_OGG || MG_MOV ||
            MG_MP4 || MG_MPG || MG_FLV || MG_ZIP || MG_PDF;
    }
    
    if ($valid_formats & MG_OTHER) {
        $valid_types = '*.*';
    } else {
        $valid_types = '';
        $valid_types .= ( $valid_formats & MG_JPG ) ? '*.jpg; ' : '';
        $valid_types .= ( $valid_formats & MG_PNG ) ? '*.png; ' : '';
        $valid_types .= ( $valid_formats & MG_TIF ) ? '*.tif; ' : '';
        $valid_types .= ( $valid_formats & MG_GIF ) ? '*.gif; ' : '';
        $valid_types .= ( $valid_formats & MG_BMP ) ? '*.bmp; ' : '';
        $valid_types .= ( $valid_formats & MG_TGA ) ? '*.tga; ' : '';
        $valid_types .= ( $valid_formats & MG_PSD ) ? '*.psd; ' : '';
        $valid_types .= ( $valid_formats & MG_MP3 ) ? '*.mp3; ' : '';
        $valid_types .= ( $valid_formats & MG_OGG ) ? '*.ogg; ' : '';
        $valid_types .= ( $valid_formats & MG_ASF ) ? '*.asf; *.wma; *.wmv; ' : '';
        $valid_types .= ( $valid_formats & MG_SWF ) ? '*.swf; ' : '';
        $valid_types .= ( $valid_formats & MG_MOV ) ? '*.mov; *.qt; ' : '';
        $valid_types .= ( $valid_formats & MG_MP4 ) ? '*.mp4; ' : '';
        $valid_types .= ( $valid_formats & MG_MPG ) ? '*.mpg; ' : '';
        $valid_types .= ( $valid_formats & MG_FLV ) ? '*.flv; ' : '';
        $valid_types .= ( $valid_formats & MG_ZIP ) ? '*.zip; ' : '';
        $valid_types .= ( $valid_formats & MG_PDF ) ? '*.pdf; ' : '';
        $valid_types = substr( $valid_types, 0, strlen($valid_types)-1 );
    }
    return $valid_types;
}

/**
* Escapes a string for HTML output
*/
function MG_escape($str)
{
    static $charset = NULL;
    if ($charset == NULL) $charset = COM_getCharset();
    $str = str_replace(
        array('&lt;', '&gt;', '&amp;', '&quot;', '&#039;'),
        array(   '<',    '>',     '&',      '"',      "'"),
        $str
    );
    return htmlspecialchars($str, ENT_QUOTES, $charset);
}

function MG_getThumbPath($path, $type)
{
    $postfix = '';
    switch ($type) {
        case '0':
            $postfix = '_100.';
            break;
        case '1':
            $postfix = '_150.';
            break;
        case '2':
            $postfix = '_200.';
            break;
        case '3':
            $postfix = '_custom.';
            break;
        case '10':
            $postfix = '_100x100.';
            break;
        case '11':
            $postfix = '_150x150.';
            break;
        case '12':
            $postfix = '_200x200.';
            break;
        case '13':
            $postfix = '_cropcustom.';
    }

    $p = pathinfo($path);
    $retval = $p['dirname'] . '/' . $p['filename'] . $postfix;
    if (isset($p['extension'])) {
        $retval .= $p['extension'];
    }

    return $retval;
}

function MG_getTNSize($val, $custom_height=0, $custom_width=0)
{
    switch ($val) {
        case '0' :      // include small
        case '10' :     // crop small
            $tn_width  = 100;
            $tn_height = 100;
            break;
        case '1' :      // include medium
        case '11' :     // crop medium
            $tn_width  = 150;
            $tn_height = 150;
            break;
        case '2' :      // include large
        case '12' :     // crop large
            $tn_width  = 200;
            $tn_height = 200;
            break;
        case '3' :      // include custom
        case '13' :     // crop custom
            $tn_width  = ($custom_width  == 0) ? 200 : $custom_width;
            $tn_height = ($custom_height == 0) ? 200 : $custom_height;
            break;
        default :
            $tn_width  = 150;
            $tn_height = 150;
            break;
    }

    return array($tn_width, $tn_height);
}

function MG_getMediaExt($path_and_filename)
{
    global $_MG_CONF;

    $retval = '';
    foreach ($_MG_CONF['validExtensions'] as $ext)
        if (file_exists($path_and_filename . $ext)) {
            $retval = $ext;
            break;
        }
    return $retval;
}

function MG_getImageUrl($name)
{
    global $_MG_CONF;

    $url = '';
    $size = false;
    clearstatcache();
    foreach ($_MG_CONF['validExtensions'] as $ext) {
        if (file_exists($_MG_CONF['path_mediaobjects'] . $name . $ext)) {
            return array(
                $_MG_CONF['mediaobjects_url'] . '/' . $name . $ext,
                getimagesize($_MG_CONF['path_mediaobjects'] . $name . $ext)
            );
        }
    }
    return array($url, $size);
}

function MG_getFilePath($type, $filename, $ext = '', $atttn = 0)
{
    global $_MG_CONF;

    $tn = ($atttn == 1) ? 'tn_' : '';

    $path_and_filename = $_MG_CONF['path_mediaobjects'] . $type
                       . '/' . $filename[0] . '/' . $tn . $filename;
    if ($atttn == 1) {
        $ext = 'jpg';
    } else if (empty($ext)) {
        $ext = ltrim(MG_getMediaExt($path_and_filename), '.');
    }

    return $path_and_filename . '.' . $ext;
}

function MG_getFileUrl($type, $filename, $ext = '', $atttn = 0)
{
    global $_MG_CONF;

    $tn = ($atttn == 1) ? 'tn_' : '';

    $tmpstr = $type . '/' . $filename[0] . '/' . $tn  . $filename;
    $path_and_filename = $_MG_CONF['path_mediaobjects'] . $tmpstr;
    if ($atttn == 1) {
        $ext = 'jpg';
    } else if (empty($ext)) {
        $ext = ltrim(MG_getMediaExt($path_and_filename), '.');
    }

    return $_MG_CONF['mediaobjects_url'] . '/' . $tmpstr . '.' . $ext;
}

function MG_getFrames()
{
    global $_MG_CONF, $LANG_MG01;

    $skins = array();
    $i = 0;
    $directory = $_MG_CONF['path_html'] . 'frames/';
    $dh = @opendir($directory);
    if ($dh != false) {
        clearstatcache();
        while (($file = @readdir($dh)) != false) {
            if (in_array($file, array('..', '.', 'CVS'))) continue;
            if (substr($file, 0 , 1) == '.') continue;
            $skindir = $directory . $file;
            if (@is_dir($skindir)) {
                if (file_exists($skindir . '/' . 'frame.inc')) {
                    include ($skindir . '/' . 'frame.inc');
                    $skins[$i]['dir'] = $file;
                    $skins[$i]['name'] = $frameData['name'];
                    $i++;
                }
            }
        }
        closedir($dh);
    }
    $sSkins = MG_sortFrames($skins, 'name');
    return $sSkins;
}

function MG_sortFrames($array, $key)
{
    $sort_values = array();
    
    for ($i = 0; $i < count($array); $i++) {
        $sort_values[$i] = $array[$i][$key];
    }

    asort($sort_values);

    foreach ($sort_values as $arr_key => $arr_val) {
        $sorted_arr[] = $array[$arr_key];
    }

    return $sorted_arr;
}

function MG_getFramedImage($skin, $title, $u_pic, $u_image, $imageWidth, $imageHeight, $media_link_start=null, $media_link_end=null)
{
    global $_MG_CONF;

    if ($media_link_start === null) $media_link_start = '<a href="' . $u_pic . '">';
    if ($media_link_end   === null) $media_link_end   = '</a>';

    $F = COM_newTemplate($_MG_CONF['path_html'] . 'frames/' . $skin . '/');
    $F->set_file('media_frame', 'frame.thtml');
    $F->set_var(array(
        'media_link_start' => $media_link_start,
        'media_link_end'   => $media_link_end,
        'url_media_item'   => $u_pic,
        'url_display_item' => $u_pic,
        'media_thumbnail'  => $u_image,
        'media_size'       => 'width="' . '100%' . '" height="' . '100%' . '"',
        'media_height'     => $imageHeight,
        'media_width'      => $imageWidth,
        'media_title'      => (isset($title) && $title != ' ') ? PLG_replaceTags($title) : '',
        'media_tag'        => (isset($title) && $title != ' ') ? strip_tags($title) : '',
        'xhtml'            => XHTML,
    ));
    return $F->finish($F->parse('media', 'media_frame'));
}

function MG_getAlbumData($album_id, $data_array=array(), $check_access=false)
{
    global $_TABLES;

    $retval = array();
    if (empty($data_array)) return $retval;

    if ($check_access == true) {
        $data_array = array_merge($data_array,
                                  array('owner_id','group_id','perm_owner',
                                        'perm_group','perm_members','perm_anon'));
    }
    $c = count($data_array);
    if ($c > 1) {
        $tmp = implode(",", $data_array);
    } else {
        $tmp = $data_array[0];
    }

    $result = DB_query("SELECT $tmp FROM {$_TABLES['mg_albums']} WHERE album_id = " . intval($album_id));
    if (DB_numRows($result) == 1) {
        $retval = DB_fetchArray($result);
        if ($check_access == true) {
            $access = MG_hasAccess($retval['owner_id'] ,$retval['group_id'],
                                   $retval['perm_owner'], $retval['perm_group'],
                                   $retval['perm_members'], $retval['perm_anon']);
            $retval = array_merge($retval, array('access' => $access));
        }
    }

    return $retval;
}

function MG_hasAccess($owner_id, $group_id, $perm_owner, $perm_group, $perm_members, $perm_anon)
{
    global $_USER, $_GROUPS;

    if (SEC_hasRights('mediagallery.admin') || SEC_inGroup('Root')) return 3;

    $uid = empty($_USER['uid']) ? 1 : $_USER['uid'];

    if ($uid == $owner_id) return $perm_owner;

    if (in_array($group_id, $_GROUPS)) return $perm_group;

    if ($uid == 1) return $perm_anon;

    return $perm_members;
}

function MG_getAlbumChildren($album_id=NULL)
{
    global $_TABLES;

    $retval = array();
    if ($album_id === NULL) return $retval;
    $album_id = intval($album_id);
    $sql = "SELECT album_id FROM {$_TABLES['mg_albums']} "
         . "WHERE album_parent = $album_id ORDER BY album_order DESC";
    $result = DB_query($sql);
    while ($A = DB_fetchArray($result)) {
        $retval[] = $A['album_id'];
    }
    return $retval;
}

function MG_getAlbumChildCount($album_id)
{
    global $_TABLES;

    $numChildren = 0;
    $children = MG_getAlbumChildren($album_id);
    $idlist = implode(',', $children);
    if (empty($idlist)) return $numChildren;
    $sql = "SELECT hidden,owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon "
         . "FROM {$_TABLES['mg_albums']} "
         . "WHERE album_id IN ($idlist) ORDER BY album_order DESC";
    $result = DB_query($sql);
    while ($A = DB_fetchArray($result)) {
        $access = MG_hasAccess($A['owner_id'],$A['group_id'],$A['perm_owner'],
                               $A['perm_group'],$A['perm_members'],$A['perm_anon']);
        if ($access > 0) {
            if ($A['hidden'] == 1) {
                if ($access == 3) {
                    $numChildren++;
                }
            } else {
                $numChildren++;
            }
        }
    }
    return $numChildren;
}

function MG_getAlbumCover($album_id)
{
    global $_TABLES;

    $album_data = MG_getAlbumData($album_id, array('album_cover_filename'), false);
    if ($album_data['album_cover_filename'] != '') {
        return $album_data['album_cover_filename'];
    }

    $root_album_owner_id = SEC_hasRights('mediagallery.admin');

    $children = MG_getAlbumChildren($album_id);
    $idlist = implode(',', $children);
    if (empty($idlist)) return '';
    $sql = "SELECT album_id,album_cover_filename,hidden,owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon "
         . "FROM {$_TABLES['mg_albums']} "
         . "WHERE album_id IN ($idlist) ORDER BY album_order DESC";
    $result = DB_query($sql);
    while ($A = DB_fetchArray($result)) {
        $access = MG_hasAccess($A['owner_id'],$A['group_id'],$A['perm_owner'],
                               $A['perm_group'],$A['perm_members'],$A['perm_anon']);
        if ($access == 0) continue;
        if ($A['hidden'] != 1 || ($A['hidden'] && $root_album_owner_id)) {
            if ($A['album_cover_filename'] != '') {
                return $A['album_cover_filename'];
            }
        }
        if ($A['hidden'] != 1 || ($A['hidden'] && $root_album_owner_id == 1)) {
            $filename = MG_getAlbumCover($A['album_id']);
            if ($filename != '') {
                return $filename;
            }
        }
    }
    return '';
}

// update the thumbnail image for the album
function MG_resetAlbumCover($album_id)
{
    global $_TABLES;

    $current_cover = DB_getItem($_TABLES['mg_albums'], 'album_cover_filename', 'album_id', intval($album_id));
    if (!empty($current_cover)) {
        $sql = "SELECT m.media_id FROM {$_TABLES['mg_media']} AS m "
             . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON m.media_id=ma.media_id "
             . "WHERE ma.album_id=" . intval($album_id)
             . " AND m.media_filename='" . $current_cover . "'";
        $result = DB_query($sql);
        $nRows = DB_numRows($result);
        if ($nRows > 0) return;
    }

    $sql = "SELECT m.media_filename FROM {$_TABLES['mg_media']} AS m "
         . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON m.media_id=ma.media_id "
         . "WHERE ma.album_id=" . intval($album_id)
         . " AND m.media_type=0 "
         . "ORDER BY m.media_upload_time DESC LIMIT 1";
    $result = DB_query($sql);
    $filename = '';
    while ($row = DB_fetchArray($result)) {
        $filename = DB_escapeString($row['media_filename']);
    }
    DB_change($_TABLES['mg_albums'], 'album_cover', -1, 'album_id', intval($album_id));
    DB_change($_TABLES['mg_albums'], 'album_cover_filename', $filename, 'album_id', intval($album_id));
}

function MG_updateAlbumLastUpdate($album_id)
{
    global $_TABLES, $_MG_CONF;

    $sql = "SELECT media_upload_time "
         . "FROM {$_TABLES['mg_media_albums']} AS ma "
         . "INNER JOIN {$_TABLES['mg_media']} AS m "
         . "ON ma.media_id=m.media_id "
         . "WHERE ma.album_id=" . intval($album_id) . " "
         . "ORDER BY media_upload_time DESC LIMIT 1";
    $result = DB_query($sql);
    while ($row = DB_fetchArray($result)) {
        $last_update = $row['media_upload_time'];
        DB_change($_TABLES['mg_albums'], 'last_update', $last_update, 'album_id', intval($album_id));
        if ($_MG_CONF['update_parent_lastupdated'] == 1) {
            $currentAID = DB_getItem($_TABLES['mg_albums'], 'album_parent', 'album_id=' . intval($album_id));
            while ($currentAID != 0) {
                DB_change($_TABLES['mg_albums'], 'last_update', $last_update, 'album_id', $currentAID);
                $currentAID = DB_getItem($_TABLES['mg_albums'], 'album_parent', 'album_id=' . $currentAID);
            }
        }
    }
}

function MG_getMediaCount($album_id)
{
    global $_TABLES;

    $mediaCount = 0;
    $root_album_owner_id = SEC_hasRights('mediagallery.admin');
    $album_data = MG_getAlbumData($album_id, array('hidden', 'media_count'), true);

    if ($album_data['access'] != 0) {
        if ( ($album_data['hidden'] && $root_album_owner_id == 1 ) || $album_data['hidden'] != 1) {
            $mediaCount = $album_data['media_count'];
        }
    }

    $children = MG_getAlbumChildren($album_id);
    $idlist = implode(',', $children);
    if (empty($idlist)) return $mediaCount;
    $sql = "SELECT album_id,hidden,owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon "
         . "FROM {$_TABLES['mg_albums']} "
         . "WHERE album_id IN ($idlist) ORDER BY album_order DESC";
    $result = DB_query($sql);
    while ($A = DB_fetchArray($result)) {
        $access = MG_hasAccess($A['owner_id'],$A['group_id'],$A['perm_owner'],
                               $A['perm_group'],$A['perm_members'],$A['perm_anon']);
        if ($access > 0) {
            if ( ( $A['hidden'] && $root_album_owner_id == 1 ) || $A['hidden'] != 1 ) {
                $child_album = new mgAlbum($A['album_id']);
                $mediaCount += $child_album->getMediaCount();
            }
        }
    }
    return $mediaCount;
}

function MG_albumThumbnail($album_id)
{
    global $_MG_CONF, $_TABLES, $_USER, $LANG_MG00, $LANG_MG01, $LANG_MG03;

    $sql = "SELECT album_title,album_parent,album_views,enable_album_views,"
         . "media_count,album_desc,album_cover_filename,last_update,tn_attached "
         . "FROM {$_TABLES['mg_albums']} "
         . "WHERE album_id=" . intval($album_id);
    $result = DB_query($sql);
    $album_data = DB_fetchArray($result);

    $cover_filename = $album_data['album_cover_filename'];

    if ($album_data['media_count'] > 0) {
        if ($cover_filename != '' && $cover_filename != '0') {

            // Testing!
            if (strpos($cover_filename, 'tn_') === 0) {
                $tmpfilename = 'tn/' . $cover_filename[3] . '/' . $cover_filename;
            } else {
                $type = $_MG_CONF['gallery_tn_size']; // Root album
                if ($album_data['album_parent'] > 0) {
                    $type = DB_getItem($_TABLES['mg_albums'], 'tn_size', 'album_id=' . $album_data['album_parent']);
                }
                $tmpfilename = 'tn/' . $cover_filename[0] . '/' . $cover_filename;
                $tmpfilename = MG_getThumbPath($tmpfilename, $type);
                $tmpfilename = rtrim($tmpfilename, '.');
            }
            list($album_last_image, $mediasize) = MG_getImageUrl($tmpfilename);

            $album_last_update  = MG_getUserDateTimeFormat($album_data['last_update']);
            if ($mediasize == false) {
                $album_last_image = $_MG_CONF['mediaobjects_url'] . '/empty.png';
                $mediasize = @getimagesize($_MG_CONF['path_mediaobjects'] . 'empty.png');
            }
        } else {
            $filename = MG_getAlbumCover($album_id);
            if ($filename == '' || $filename == NULL || $filename == " ") {
                $album_last_image = $_MG_CONF['mediaobjects_url'] . '/empty.png';
                $mediasize = @getimagesize($_MG_CONF['path_mediaobjects'] . 'empty.png');
            } else {
                list($album_last_image, $mediasize) = MG_getImageUrl('tn/' . $filename[0] . '/' . $filename);
                if ($mediasize == false) {
                    $album_last_image = $_MG_CONF['mediaobjects_url'] . '/missing.png';
                    $mediasize = @getimagesize($_MG_CONF['path_mediaobjects'] . 'missing.png');
                }
            }
        }
        $album_media_count = $album_data['media_count'];
        if ($album_data['last_update'] > 0) {
            $album_last_update = MG_getUserDateTimeFormat($album_data['last_update']);
            $lang_updated = ($_MG_CONF['dfid']=='99' ? '' : $LANG_MG03['updated_prompt']);
        } else {
            $album_last_update[0] = '';
            $lang_updated = '';
        }
        $lang_updated = ($_MG_CONF['dfid']=='99' ? '' : $LANG_MG03['updated_prompt']);

        if (isset($_USER['uid']) && $_USER['uid'] > 1) {
            if (COM_versionCompare(VERSION, '2.2.2', '>=')) {
                $lastlogin = DB_getItem($_TABLES['user_attributes'], 'lastlogin', "uid = '" . $_USER['uid'] . "'");
            } else {
                $lastlogin = DB_getItem($_TABLES['userinfo'], 'lastlogin', "uid = '" . $_USER['uid'] . "'");
            }
            if ($album_data['last_update'] > $lastlogin) {
                $album_last_update[0] = '<span class="mgUpdated">' . $album_last_update[0] . '</span>';
            }
        }
    } else {  // nothing in the album yet...
        $filename = MG_getAlbumCover($album_id);
        if ($filename == '') {
            $album_last_image = $_MG_CONF['mediaobjects_url'] . '/empty.png';
            $mediasize = @getimagesize($_MG_CONF['path_mediaobjects'] . 'empty.png');
        } else {
            list($album_last_image, $mediasize) = MG_getImageUrl('tn/' . $filename[0] . '/' . $filename);
            if ($mediasize == false) {
                $album_last_image = $_MG_CONF['mediaobjects_url'] . '/missing.png';
                $mediasize = @getimagesize($_MG_CONF['path_mediaobjects'] . 'missing.png');
            }
        }
        $album_last_update[0] = '';
        $lang_updated = '';
    }

    if ($album_data['tn_attached'] == 1) {
        list($album_last_image, $mediasize) = MG_getImageUrl('covers/cover_' . $album_id);
        if ($mediasize == false) {
            $album_last_image = $_MG_CONF['mediaobjects_url'] . '/missing.png';
            $mediasize = @getimagesize($_MG_CONF['path_mediaobjects'] . 'missing.png');
        }
    }

    $children = MG_getAlbumChildren($album_id);
    $subalbums = count($children);
    $total_images_subalbums = MG_getMediaCount($album_id);

    $parent_album = new mgAlbum($album_data['album_parent']);

    $_MG_USERPREFS = MG_getUserPrefs();
    if (isset($_MG_USERPREFS['tn_size']) && $_MG_USERPREFS['tn_size'] != -1) {
        $tn_size = $_MG_USERPREFS['tn_size'];
    } else {
        $tn_size = $parent_album->tn_size;
    }

    list($tn_height, $tn_width) = MG_getTNSize($tn_size, $parent_album->tnHeight, $parent_album->tnWidth);

    list($newwidth, $newheight) = MG_getImageWH_3($mediasize[0], $mediasize[1], $tn_width, $tn_height);

    $media_item_thumbnail = MG_getFramedImage($parent_album->album_skin, $album_data['album_title'], 
                                              $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id .'&amp;page=1',
                                              $album_last_image, $newwidth, $newheight);

    $C = COM_newTemplate(MG_getTemplatePath($album_data['album_parent']));
    $C->set_file('cell', 'album_page_album_cell.thtml');
    $C->set_var(array(
        'media_item_thumbnail' => $media_item_thumbnail,
        'u_viewalbum'          => $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id .'&amp;page=1',
        'album_last_image'     => $album_last_image,
        'album_title'          => $album_data['album_title'],
        'album_media_count'    => $album_data['media_count'],
        'subalbum_media_count' => $total_images_subalbums,
        'album_desc'           => PLG_replaceTags($album_data['album_desc']),
        'album_last_update'    => $album_last_update[0],
        'img_height'           => $newheight,
        'img_width'            => $newwidth,
        's_media_size'         => 'width="' . $newwidth . '" height="' . $newheight . '"',
        'row_height'           => $tn_height,
        'updated'              => $lang_updated,
        'lang_album'           => $LANG_MG00['album'],
        'lang_views'           => $LANG_MG03['views'],
        'views'                => $album_data['album_views'],
        'lang_views'           => ($album_data['enable_album_views'] ? $LANG_MG03['views'] : ''),
        'views'                => ($album_data['enable_album_views'] ? $album_data['album_views'] : ''),
        'subalbumcount'        => (($subalbums > 0) ? '(' . $subalbums . ')' : ''),
        'lang_subalbums'       => (($subalbums > 0) ? $LANG_MG01['subalbums'] : ''),
    ));

    PLG_templateSetVars('mediagallery', $C);
    $C->parse('output', 'cell');
    $celldisplay = $C->finish($C->get_var('output'));
    return $celldisplay;
}

function MG_getBirdseed($album_id, $hot=0, $sortOrder=0, $page=0)
{
    global $_CONF, $_MG_CONF, $LANG_MG03;

    $items = array();
    $aid = $album_id;
    while ($aid != 0) {
        $album_data = MG_getAlbumData($aid, array('album_title', 'album_parent'));
        $url = NULL;
        if ($hot == 1) {
            $url = $_MG_CONF['site_url'] . '/album.php?aid=' . $aid . '&amp;sort=' . $sortOrder;
            if ($aid == $album_id && $page > 1) {
                $url .= '&amp;page=' . $page;
            }
        }
        $title = strip_tags($album_data['album_title']);
        if ($_MG_CONF['truncate_breadcrumb'] > 0) {
            $title = COM_truncate($title, $_MG_CONF['truncate_breadcrumb'], '...');
        }
        $items[] = array(
            'href'  => $url,
            'title' => $title,
        );
        $hot = 1;
        $aid = $album_data['album_parent'];
    }

    if ($_MG_CONF['gallery_only'] != 1) {
        $url = NULL;
        if ($hot == 1) {
            $url = $_MG_CONF['site_url'] . '/index.php';
            if ($album_id == 0 && $page > 1) {
                $url .= '?page=' . $page;
            }
        }
        $items[] = array(
            'href'  => $url,
            'title' => $_MG_CONF['root_album_name'],
        );
    }

    $items[] = array(
        'href'  => $_CONF['site_url'] . '/index.php',
        'title' => $LANG_MG03['home'],
    );

    $retval = '';
    $count = count($items) - 1;
    foreach ($items as $key => $item) {
        $birdseed = '';
        if ($key < $count) {
            $birdseed .= ' ' . $_MG_CONF['seperator'] . ' ';
        }
        if ($item['href'] !== NULL) {
            $birdseed .= COM_createLink($item['title'], $item['href']);
        } else {
            $birdseed .= $item['title'];
        }
        $retval = $birdseed . $retval;
    }

    return $retval;
}

// construct the Slideshow
function MG_buildSlideshow(&$album, &$T, $sortOrder)
{
    global $_MG_CONF, $LANG_MG03, $mgLightBox;

    $album_id = $album->id;

    $enable_slideshow = $album->enable_slideshow;
    if ($enable_slideshow == 2 && $_MG_CONF['disable_lightbox'] == true) {
        $enable_slideshow = 1;
    }

    $lbSlideShow = '';
    $url_slideshow = '';
    $lang_slideshow = '';
    $mgLightBox = 0; // global variable
    switch ($enable_slideshow) {
        case 0 :
            break;
        case 1 :
            $url_slideshow  = $_MG_CONF['site_url'] . '/slideshow.php?aid=' . $album_id . '&amp;sort=' . $sortOrder;
            $lang_slideshow = $LANG_MG03['slide_show'];
            break;
        case 2:
            $lbSlideShow = mgAlbum::buildLightboxSlideShow($album_id);
            $sql = MG_buildMediaSql(array(
                'album_id'  => $album_id,
                'fields'    => 'COUNT(m.media_id) AS lbss_count',
                'where'     => 'm.media_type = 0',
                'sortorder' => -1
            ));
            $result = DB_query($sql);
            list($lbss_count) = DB_fetchArray($result);
            if ($lbss_count != 0) {
                $mgLightBox = 1; // global variable
                $url_slideshow  = '#" onclick="return openGallery1()';
                $lang_slideshow = $LANG_MG03['slide_show'];
            }
            break;
        case 3:
            $url_slideshow  = $_MG_CONF['site_url'] . '/fslideshow.php?aid=' . $album_id . '&amp;src=disp';
            $lang_slideshow = $LANG_MG03['slide_show'];
            break;
        case 4:
            $url_slideshow  = $_MG_CONF['site_url'] . '/fslideshow.php?aid=' . $album_id . '&amp;src=orig';
            $lang_slideshow = $LANG_MG03['slide_show'];
            break;
        case 5:
            $url_slideshow  = $_MG_CONF['site_url'] . '/playall.php?aid=' . $album_id;
            $lang_slideshow = $LANG_MG03['play_full_album'];
            break;
    }

    $T->set_var(array(
        'lbslideshow'    => $lbSlideShow,
        'lang_slideshow' => $lang_slideshow,
        'url_slideshow'  => $url_slideshow,
    ));
}

function MG_getImageSize($val)
{
    global $_MG_CONF;

    switch ($val) {
        case 0 :
            $width  = 500;
            $height = 375;
            break;
        case 1 :
            $width  = 600;
            $height = 450;
            break;
        case 2 :
            $width  = 620;
            $height = 465;
            break;
        case 3 :
            $width  = 720;
            $height = 540;
            break;
        case 4 :
            $width  = 800;
            $height = 600;
            break;
        case 5 :
            $width  = 912;
            $height = 684;
            break;
        case 6 :
            $width  = 1024;
            $height = 768;
            break;
        case 7 :
            $width  = 1152;
            $height = 804;
            break;
        case 8 :
            $width  = 1280;
            $height = 1024;
            break;
        case 9 :
            $width  = $_MG_CONF['custom_image_width'];
            $height = $_MG_CONF['custom_image_height'];
            break;
        default :
            $width  = 620;
            $height = 465;
            break;
    }
    return array($width, $height);
}

function MG_getImageWH($imageinfo, $maxsize=0)
{
    if ($imageinfo == false) return array('', '');
    if (empty($maxsize)) {
        $maxsize = max($imageinfo[0], $imageinfo[1]);
    }
    if ($imageinfo[0] > $imageinfo[1]) {
        return array($maxsize, round($imageinfo[1] / $imageinfo[0] * $maxsize));
    }
    return array(round($imageinfo[0] / $imageinfo[1] * $maxsize), $maxsize);
}

function MG_getImageWH_2($imgwidth, $imgheight, $maxsize=0)
{
    if (empty($maxsize)) {
        $maxsize = max($imgwidth, $imgheight);
    }
    if ($imgwidth > $imgheight) {
        return array($maxsize, round($imgheight / $imgwidth * $maxsize));
    }
    return array(round($imgwidth / $imgheight * $maxsize), $maxsize);
}

function MG_getImageWH_3($imgwidth, $imgheight, $maxwidth, $maxheight, $stretch=true)
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/classMedia.php';
    
    return Media::getImageWH($imgwidth, $imgheight, $maxwidth, $maxheight, $stretch);
}

// construct the album jumpbox
function MG_buildAlbumJumpbox(&$album, $album_id, $access=1, $hide=0)
{
    global $_MG_CONF, $LANG_MG03;

    $retval  = '<form name="jumpbox" id="jumpbox" action="' . $_MG_CONF['site_url'] . '/album.php' . '" method="get" class="uk-form"><div>' . LB;
    $retval .= $LANG_MG03['jump_to'] . ':&nbsp;<select name="aid" onchange="forms[\'jumpbox\'].submit()">' . LB;
    $album->buildJumpBox($retval, $album_id, $access, $hide);
    $retval .= '</select>' . LB;
    $retval .= '<input type="submit" value="' . $LANG_MG03['go'] . '"' . XHTML . '>' . LB;
    $retval .= '<input type="hidden" name="page" value="1"' . XHTML . '>' . LB;
    $retval .= '</div></form>' . LB;

    return $retval;
}

// construct the album selectbox ...
function MG_buildAlbumBox(&$album, $album_id, $access=1, $hide=0, $type='upload')
{
    global $_MG_CONF, $LANG_MG03;

    $retval = '';
    $items = '';
    $album->buildAlbumBox($items, $album_id, $access, $hide, $type);
    if (!empty($items)) {
        $retval  = '<select name="album_id" onchange="onAlbumChange()">' . LB;
        $retval .= $items;
        $retval .= '</select>' . LB;
    }

    return $retval;
}

function MG_showTree($aid=0, $depth=0, $level=0)
{
    global $_CONF, $_MG_CONF;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/classAlbum.php';
    $album = new mgAlbum($aid);

    if ($album->hidden == 1 && $album->access != 3) {
        return '';
    }

    $retval = '';

    if ($album->access > 0) {
        $px = $level * 15;
        if ($depth == 0 || $level <= $depth) {
            $retval .= '<div style="margin-left:' . $px . 'px;">'
                     . '<a href="' . $_MG_CONF['site_url'] . '/album.php?aid=' . $album->id . '&amp;page=1">'
                     . strip_tags($album->title) . '</a></div>';
        }
    }

    $children = $album->getChildren();
    $level++;
    foreach ($children as $child) {
        $retval .= MG_showTree($child, $depth, $level);
    }

    return $retval;
}

function MG_options($info)
{
    $retval = '';
    foreach ($info['values'] as $key => $val) {
        $retval .= '<option value="' . $key . '"'
                 . ($key == $info['current'] ? ' selected="selected"' : '')
                 . '>' . $val . '</option>' . LB;
    }

    return $retval;
}

function MG_optionlist($info)
{
    $disabled = isset($info['disabled']) ? $info['disabled'] : '';
    $retval = '<select name="' . $info['name'] . '"'
            . ($disabled ? ' disabled="disabled"' : '') . '>' . LB;
    foreach ($info['values'] as $key => $val) {
        $retval .= '<option value="' . $key . '"'
                 . ($key == $info['current'] ? ' selected="selected"' : '')
                 . '>' . $val . '</option>' . LB;
    }
    $retval .= '</select>';

    return $retval;
}

function MG_checkbox($info)
{
    $retval = '<input type="checkbox" name="' . $info['name'] . '" '
            . 'value="' . $info['value'] . '"'
            . ($info['checked'] ? ' checked="checked"' : '') . XHTML . '>';

    return $retval;
}

function MG_input($info)
{
    $retval = '<input';
    foreach ($info as $key => $val)
        $retval .= ' ' . $key . '="' . $val . '"';
    $retval .= XHTML . '>';

    return $retval;
}
