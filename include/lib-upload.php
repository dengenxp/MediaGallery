<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | lib-upload.php                                                           |
// |                                                                          |
// | Upload library                                                           |
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

require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-exif.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-watermark.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/lib/imglib/lib-image.php';

global $_SPECIAL_IMAGES_MIMETYPE;
$_SPECIAL_IMAGES_MIMETYPE = array(
    'image/x-targa',
    'image/tga',
    'image/photoshop',
    'image/x-photoshop',
    'image/psd',
    'application/photoshop',
    'application/psd',
    'image/tiff'
);

function MG_videoThumbnail($aid, $srcImage, $media_filename)
{
    global $_MG_CONF;

    if ($_MG_CONF['ffmpeg_enabled'] == 1) {
        $pThumbnail = Media::getFilePath('tn', $media_filename, 'jpg', 1);
        $ffmpeg_cmd = sprintf('"' . $_MG_CONF['ffmpeg_path'] . '/ffmpeg' . '" ' . $_MG_CONF['ffmpeg_command_args'], $srcImage, $pThumbnail);
        $rc = MG_execWrapper($ffmpeg_cmd);
        COM_errorLog("MG Upload: FFMPEG returned: " . $rc);
        if ($rc != 1) {
            @unlink($pThumbnail);
            return 0;
        }
        return 1;
    }
    return 0;
}

function MG_processOriginal($srcImage, $mimeExt, $mimeType, $aid, $dnc)
{
    global $_CONF, $_TABLES, $_MG_CONF;

    $dnc = 1;
    $rc = true;
    $msg = '';

    $newSrc = $srcImage;

    if ($_MG_CONF['verbose'] ) {
        COM_errorLog("MG Upload: Entering MG_processOriginal()");
    }
    $imgsize = @getimagesize($srcImage);
    $imgwidth = $imgsize[0];
    $imgheight = $imgsize[1];

    if ($imgwidth == 0 || $imgheight == 0) {
        $imgwidth = 620;
        $imgheight = 465;
    }

    // now check and see if discard_original is OFF and if our image is too big??
    $sql = "SELECT max_image_width, max_image_height "
         . "FROM {$_TABLES['mg_albums']} WHERE album_id = " . intval($aid);
    $result = DB_query($sql);
    $A = DB_fetchArray($result);
    if ($_MG_CONF['discard_original'] != 1 && $A['max_image_width'] != 0 && $A['max_image_height'] != 0) {
        if ($imgwidth > $A['max_image_width'] || $imgheight > $A['max_image_height']) {

            if ($imgwidth > $imgheight) {
                $ratio = $imgwidth / $A['max_image_width'];
                $newwidth = $A['max_image_width'];
                $newheight = round($imgheight / $ratio);
            } else {
                $ratio = $imgheight / $A['max_image_height'];
                $newheight = $A['max_image_height'];
                $newwidth = round($imgwidth / $ratio);
            }

            list($rc, $msg) = MG_resizeImage($srcImage, $srcImage, $newheight, $newwidth, $mimeType, 0, $_MG_CONF['jpg_orig_quality']);
        }
    }
    return array($rc, $msg);
}

// --
// Create the thumbnail image
// --
function MG_createThumbnail($srcImage, $imageThumb, $mimeType, $aid)
{
    global $_CONF, $_TABLES, $_MG_CONF, $_SPECIAL_IMAGES_MIMETYPE;

    $tmpImage = '';

    if (in_array($mimeType, $_SPECIAL_IMAGES_MIMETYPE)) {
        $tmpImage = $_MG_CONF['tmp_path'] . 'wip' . rand() . '.jpg';
        list($rc, $msg) = MG_convertImageFormat($srcImage, $tmpImage, 'image/jpeg', 0);
        if ($rc == false) {
            COM_errorLog("MG_createThumbnail: Error converting uploaded image to jpeg format.");
            @unlink($srcImage);
            return array(false, $msg);
        }
    }

    $sql = "SELECT tnheight, tnwidth "
         . "FROM {$_TABLES['mg_albums']} "
         . "WHERE album_id = " . intval($aid);
    $result = DB_query($sql);
    $A = DB_fetchArray($result);
    $types = array('0','1','2','3','10','11','12','13');
    foreach ($types as $t) {
        list($tnHeight, $tnWidth) = MG_getTNSize($t, $A['tnheight'], $A['tnwidth']);
        $imageThumbPath = MG_getThumbPath($imageThumb, $t);
        if ($tmpImage != '') {
            $src = $tmpImage;
            $mt = '';
        } else {
            $src = $srcImage;
            $mt = $mimeType;
        }
        $func = 'MG_resizeImage';
        if (in_array($t, array('10','11','12','13'))) {
            $func = 'MG_resizeImage_crop';
        }
        list($rc, $msg) = $func($src, $imageThumbPath, $tnHeight, $tnWidth, $mt, 0, $_MG_CONF['tn_jpg_quality']);

        if ($rc == false) {
            COM_errorLog("MG_createThumbnail: Error resizing uploaded image to thumbnail size.");
            @unlink($srcImage);
            return array(false, $msg);
        }
    }

    return array(true, '');
}


// --
// Create the display image
// --
function MG_createDisplayImage($srcImage, $imageDisplay, $mimeExt, $mimeType, $aid, $dnc=1)
{
    global $_CONF, $_TABLES, $_MG_CONF, $_SPECIAL_IMAGES_MIMETYPE;

    $imgsize = @getimagesize($srcImage);

    if ($imgsize == false && !in_array($mimeType, $_SPECIAL_IMAGES_MIMETYPE)) {
        return array(false, 'Unable to determine src image dimensions');
    }
    $imgwidth  = $imgsize[0];
    $imgheight = $imgsize[1];

    $sql = "SELECT display_image_size "
         . "FROM {$_TABLES['mg_albums']} WHERE album_id = " . intval($aid);
    $result = DB_query($sql);
    $A = DB_fetchArray($result);
    list($dImageWidth, $dImageHeight) = MG_getImageSize($A['display_image_size']);
    if ($imgwidth == 0 || $imgheight == 0) {
        $imgwidth   = $dImageWidth;
        $imgheight  = $dImageHeight;
    }

    if ($mimeType == 'image/x-targa' || $mimeType == 'image/tga') {
        $fp = @fopen($srcImage, 'rb');
        if ($fp == false) {
            $imgwidth  = 0;
            $imgheight = 0;
        } else {
            $data = fread($fp, filesize($srcImage));
            fclose($fp);
            $imgwidth  = base_convert(bin2hex(strrev(substr($data,12,2))),16,10);
            $imgheight = base_convert(bin2hex(strrev(substr($data,12,2))),16,10);
        }
    }

    $tmpImage = '';

    if (in_array($mimeType, $_SPECIAL_IMAGES_MIMETYPE)) {
        $tmpImage = $_MG_CONF['tmp_path'] . 'wip' . rand() . '.jpg';
        list($rc, $msg) = MG_convertImageFormat($srcImage, $tmpImage, 'image/jpeg', 0);
        if ($rc == false) {
            COM_errorLog("MG_createDisplayImage: Error converting uploaded image to jpeg format.");
            @unlink($srcImage);
            return array(false, $msg);
        }
    }
    if ($tmpImage != '') {
        list($rc,$msg) = MG_resizeImage($tmpImage, $imageDisplay, $dImageHeight, $dImageWidth, '', 0, $_MG_CONF['jpg_quality']);
//      list($rc,$msg) = MG_resizeImage($tmpImage, $imageDisplay, $imgheight,    $imgwidth,    '', 0, $_MG_CONF['jpg_quality']);
    } else {
        list($rc,$msg) = MG_resizeImage($srcImage, $imageDisplay, $dImageHeight, $dImageWidth, $mimeType, 0, $_MG_CONF['jpg_quality']);
//      list($rc,$msg) = MG_resizeImage($srcImage, $imageDisplay, $imgheight,    $imgwidth,    $mimeType, 0, $_MG_CONF['jpg_quality']);
    }
    if ($rc == false) {
        @unlink($srcImage);
        @unlink($tmpImage);
        return array(false, $msg);
    }
    if ($tmpImage != '') {
        @unlink($tmpImage);
    }

    if ($_MG_CONF['discard_original'] != 1) { // discard original image file
        list($rc, $msg) = MG_processOriginal($srcImage, $mimeExt, $mimeType, $aid, $dnc);
        if ($rc == false) {
            @unlink($srcImage);
            @unlink($imageDisplay);
            @unlink($tmpImage);
            return array(false, $msg);
        }
    }

    return array(true, '');
}



function MG_convertImage($srcImage, $imageThumb, $imageDisplay, $mimeExt, $mimeType, $aid, $baseFilename, $dnc)
{
    global $_CONF, $_TABLES, $_MG_CONF;

    if ($_MG_CONF['verbose']) {
        COM_errorLog("MG Upload: Entering MG_convertImage()");
    }

    // create the thumbnail image
    list($rc, $msg) = MG_createThumbnail($srcImage, $imageThumb, $mimeType, $aid);
    if ($rc == false) {
        return array(false, $msg);
    }

    // create the display image
    list($rc, $msg) = MG_createDisplayImage($srcImage, $imageDisplay, $mimeExt, $mimeType, $aid, $dnc);
    if ($rc == false) {
        return array(false, $msg);
    }

    @chmod($imageThumb, 0644);
    @chmod($imageDisplay, 0644);
    return array(true, '');
}



function MG_processZip($filename, $album_id, $purgefiles, $tmpdir)
{
    global $_CONF, $_MG_CONF, $LANG_MG02;

    $rc = @mkdir($_MG_CONF['tmp_path'] . $tmpdir);
    if ($rc == FALSE) {
        $status = $LANG_MG02['error_create_tmp'];
        return $status;
    }

    $rc = MG_execWrapper('"' . $_MG_CONF['zip_path'] . "/unzip" . '"' . " -d " . $_MG_CONF['tmp_path'] . $tmpdir . " " . $filename);

    $status = MG_processDir($_MG_CONF['tmp_path'] . $tmpdir, $album_id, $purgefiles, 1);
    MG_deleteDir($_MG_CONF['tmp_path'] . $tmpdir);
    return $status;
}

function MG_processDir($dir, $album_id, $purgefiles, $recurse)
{
    global $_TABLES, $LANG_MG02;

    if (!@is_dir($dir)) {
        $display = COM_showMessageText($LANG_MG02['invalid_directory']
               . '  [ <a href=\'javascript:history.go(-1)\'>' . $LANG_MG02['go_back'] . '</a> ]');
        $display = MG_createHTMLDocument($display);
        COM_output($display);
        exit;
    }
    if (!$dh = @opendir($dir)) {
        $display = COM_showMessageText($LANG_MG02['directory_error']
               . '  [ <a href=\'javascript:history.go(-1)\'>' . $LANG_MG02['go_back'] . '</a> ]');
        $display = MG_createHTMLDocument($display);
        COM_output($display);
        exit;
    }
    while (($file = readdir($dh)) != false) {
        if ($file == '..' || $file == '.') {
            continue;
        }
        set_time_limit(60);
        $filename = $file;
        if (PHP_OS == "WINNT") {
            $filetmp = $dir . "\\" . $file;
        } else {
            $filetmp  = $dir . '/' . $file;
        }

        if (is_dir($filetmp)) {
            if ($recurse) {
                $statusMsg .= MG_processDir($filetmp, $album_id, $purgefiles, $recurse);
            }
        } else {
            $max_filesize = DB_getItem($_TABLES['mg_albums'], 'max_filesize', 'album_id=' . intval($album_id));
            if ($max_filesize != 0 && filesize($filetmp) > $max_filesize) {
                COM_errorLog("MG Upload: File " . $file . " exceeds maximum filesize for this album.");
                $statusMsg = sprintf($LANG_MG02['upload_exceeds_max_filesize'] . '<br' . XHTML . '>',$file);
                continue;
            }

            $filetype = "application/force-download";

            $opt = array(
                'upload'     => 0,
                'purgefiles' => $purgefiles,
                'filetype'   => $filetype,
            );
            list($rc, $msg) = MG_getFile($filetmp, $file, $album_id, $opt);

            $statusMsg .= $file . ' ' . $msg . '<br' . XHTML . '>';
        }
    }
    closedir($dh);
    return $statusMsg;
}

function MG_deleteDir($dir)
{
    if (substr($dir, strlen($dir)-1, 1) != '/')
        $dir .= '/';

    if ($handle = opendir($dir)) {
       while ($obj = readdir($handle)) {
           if ($obj != '.' && $obj != '..') {
               if (is_dir($dir.$obj)) {
                   if (!MG_deleteDir($dir.$obj))
                       return false;
               } elseif (is_file($dir.$obj)) {
                   if (!unlink($dir.$obj))
                       return false;
               }
           }
       }

       closedir($handle);

       if (!@rmdir($dir))
           return false;
       return true;
   }
   return false;
}

function MG_file_exists($potential_file)
{
    global $_MG_CONF;

    $image_path = $_MG_CONF['path_mediaobjects'] . 'disp/' . $potential_file[0];

    $potential_file_regex = '/' . $potential_file . '/i';

    if ($dir = opendir($image_path)) {
        while ($file = readdir($dir)) {
            if (preg_match($potential_file_regex , $file)) {
                closedir($dir);
                return true;
            }
        }
    }
    closedir($dir);

    $image_path = $_MG_CONF['path_mediaobjects'] . 'orig/' . $potential_file[0];
    if ($dir = opendir($image_path)) {
        while ($file = readdir($dir)) {
            if (preg_match($potential_file_regex , $file)) {
                closedir($dir);
                return true;
            }
        }
    }
    closedir($dir);
    return false;
}

/**
* Set content type based upon file extension
*
* @param    string      file_ext    file extention to check
* @param    string      default     default content type to set
* @return   string      filetype    mime type of content based upon extension
*
* if the type cannot be determined from the extension because the extension is
* not known, then the default value is returned (even if null)
*
*/
function MG_getFileTypeFromExt($file_ext, $default='')
{
    //This will set the Content-Type to the appropriate setting for the file
    switch ($file_ext) {
        case 'exe':
            return 'application/octet-stream';
            break;
        case 'zip':
            return 'application/zip';
            break;
        case 'mp3':
            return 'audio/mpeg';
            break;
        case 'mpg':
            return 'video/mpeg';
            break;
        case 'avi':
            return 'video/x-msvideo';
            break;
        case 'tga':
            return 'image/tga';
            break;
        case 'psd':
            return 'image/psd';
            break;
        default:
            return $default;
            break;
    }
}

function MG_getFile($filename, $file, $album_id, $opt = array())
{
    global $_CONF, $_MG_CONF, $_USER, $_TABLES, $LANG_MG00, $LANG_MG01, $LANG_MG02,
           $_SPECIAL_IMAGES_MIMETYPE, $new_media_id;

    $caption     = isset($opt['caption'])     ? $opt['caption']     : '';
    $description = isset($opt['description']) ? $opt['description'] : '';
    $upload      = isset($opt['upload'])      ? $opt['upload']      : 1;
    $purgefiles  = isset($opt['purgefiles'])  ? $opt['purgefiles']  : 0;
    $filetype    = isset($opt['filetype'])    ? $opt['filetype']    : '';
    $atttn       = isset($opt['atttn'])       ? $opt['atttn']       : 0;
    $thumbnail   = isset($opt['thumbnail'])   ? $opt['thumbnail']   : '';
    $keywords    = isset($opt['keywords'])    ? $opt['keywords']    : '';
    $category    = isset($opt['category'])    ? $opt['category']    : 0;
    $dnc         = isset($opt['dnc'])         ? $opt['dnc']         : 0;
    $replace     = isset($opt['replace'])     ? $opt['replace']     : 0;

    $artist                     = '';
    $musicAlbum                 = '';
    $genre                      = '';
    $video_attached_thumbnail   = 0;
    $successfulWatermark        = 0;
    $dnc                        = 1; // What is this?
    $errors                     = 0;
    $errMsg                     = '';

    require_once $_CONF['path'] . 'plugins/mediagallery/include/classAlbum.php';
    $album = new mgAlbum($album_id);
    $root_album = new mgAlbum(0);

    if ($_MG_CONF['verbose']) {
        COM_errorLog("MG Upload: *********** Beginning media upload process...");
        COM_errorLog("Filename to process: " . $filename);
        COM_errorLog("UID=" . $_USER['uid']);
        COM_errorLog("album access=" . $album->access);
        COM_errorLog("album owner_id=" . $album->owner_id);
        COM_errorLog("member_uploads=" . $album->member_uploads);
    }

    clearstatcache();
    if (!file_exists($filename)) {
        $errMsg = $LANG_MG02['upload_not_found'];
        return array(false, $errMsg);
    }
    if (!is_readable($filename)) {
        $errMsg = $LANG_MG02['upload_not_readable'];
        return array(false, $errMsg);
    }

    // make sure we have the proper permissions to upload to this album....

    if (!isset($album->id)) {
        $errMsg = $LANG_MG02['album_nonexist']; // "Album does not exist, unable to process uploads";
        return array(false, $errMsg);
    }

    if ($album->access != 3 && !$root_album->owner_id && $album->member_uploads == 0) {
        COM_errorLog("Someone has tried to illegally upload to an album in Media Gallery. "
                   . "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: " . $_SERVER['REMOTE_ADDR'], 1);
        return array(false, $LANG_MG00['access_denied_msg']);
    }

    sleep(0.1);                       // We do this to make sure we don't get dupe sid's

    /*
     * The following section of code will generate a unique name for a temporary
     * file and copy the uploaded file to the Media Gallery temp directory.
     * We do this to prevent any SAFE MODE issues when we later open the
     * file to determine the mime type.
     */

    if (empty($_USER['username'])) {
        $_USER['username'] = 'guestuser';
    }

    $tmpPath = $_MG_CONF['tmp_path'] . $_USER['username'] . COM_makesid() . '.tmp';

    if ($upload) {
        $rc = @move_uploaded_file($filename, $tmpPath);
    } else {
        $rc = @copy($filename, $tmpPath);
        $importSource = $filename;
    }
    if ($rc != 1) {
        COM_errorLog("Media Upload - Error moving uploaded file in generic processing....");
        COM_errorLog("Media Upload - Unable to copy file to: " . $tmpPath);
        $errors++;
        $errMsg .= sprintf($LANG_MG02['move_error'], $filename);
        @unlink($tmpPath);
        COM_errorLog("MG Upload: Problem uploading a media object");
        return array(false, $errMsg);
    }

    $filename = $tmpPath;

    $new_media_id = ($replace > 0) ? $replace : COM_makesid();

    $media_time = time();
    $media_upload_time = $media_time;

    if (!isset($_USER['uid']) || $_USER['uid'] < 1) {
        $media_user_id = 1;
    } else {
        $media_user_id = $_USER['uid'];
    }

    $mimeInfo = MG_getMediaMetaData($filename);
    $mimeExt = strtolower(substr(strrchr($file, '.'), 1));
    $mimeInfo['type'] = $mimeExt;

    // override the determination for some filetypes
    $filetype = MG_getFileTypeFromExt($mimeExt, $filetype);

    if (empty($mimeInfo['mime_type'])) {
        COM_errorLog("MG Upload: getID3 was unable to detect mime type - using PHP detection");
        $mimeInfo['mime_type'] = $filetype;
    }

    $gotTN = 0;
    if ($mimeInfo['id3v2']['APIC'][0]['mime'] == 'image/jpeg') {
        $mp3AttachdedThumbnail = $mimeInfo['id3v2']['APIC'][0]['data'];
        $gotTN = 1;
    }

    if ($_MG_CONF['verbose']) {
        COM_errorLog("MG Upload: found mime type of " . $mimeInfo['type']);
    }

    if ($mimeExt == '' || $mimeInfo['mime_type'] == 'application/octet-stream' || $mimeInfo['mime_type'] == '') {
        // assume format based on file upload info...
        switch ($filetype) {
            case 'audio/mpeg':
                $mimeInfo['type'] = 'mp3';
                $mimeInfo['mime_type'] = 'audio/mpeg';
                $mimeExt = 'mp3';
                break;
            case 'image/tga':
                $mimeInfo['type'] = 'tga';
                $mimeInfo['mime_type'] = 'image/tga';
                $mimeExt = 'tga';
                break;
            case 'image/psd':
                $mimeInfo['type'] = 'psd';
                $mimeInfo['mime_type'] = 'image/psd';
                $mimeExt = 'psd';
                break;
            case 'image/gif':
                $mimeInfo['type'] = 'gif';
                $mimeInfo['mime_type'] = 'image/gif';
                $mimeExt = 'gif';
                break;
            case 'image/jpeg':
            case 'image/jpg':
                $mimeInfo['type'] = 'jpg';
                $mimeInfo['mime_type'] = 'image/jpeg';
                $mimeExt = 'jpg';
                break;
            case 'image/png':
                $mimeInfo['type'] = 'png';
                $mimeInfo['mime_type'] = 'image/png';
                $mimeExt = 'png';
                break;
            case 'image/bmp':
                $mimeInfo['type'] = 'bmp';
                $mimeInfo['mime_type'] = 'image/bmp';
                $mimeExt = 'bmp';
                break;
            case 'application/x-shockwave-flash':
                $mimeInfo['type'] = 'swf';
                $mimeInfo['mime_type'] = 'application/x-shockwave-flash';
                $mimeExt = 'swf';
                break;
            case 'application/zip':
                $mimeInfo['type'] = 'zip';
                $mimeInfo['mime_type'] = 'application/zip';
                $mimeExt = 'zip';
                break;
            case 'audio/mpeg':
                $mimeInfo['type'] = 'mp3';
                $mimeInfo['mime_type'] = 'audio/mpeg';
                $mimeExt = 'mp3';
                break;
            case 'video/quicktime':
                $mimeInfo['type'] = 'mov';
                $mimeInfo['mime_type'] = 'video/quicktime';
                $mimeExt = 'mov';
                break;
            case 'video/x-m4v':
                $mimeInfo['type'] = 'mov';
                $mimeInfo['mime_type'] = 'video/x-m4v';
                $mimeExt = 'mov';
                break;
            case 'video/x-flv':
                $mimeInfo['type'] = 'flv';
                $mimeInfo['mime_type'] = 'video/x-flv';
                $mimeExt = 'flv';
                break;
            case 'audio/x-ms-wma':
                $mimeInfo['type'] = 'wma';
                $mimeInfo['mime_type'] = 'audio/x-ms-wma';
                $mimeExt = 'wma';
                break;
            default :
                switch ($mimeExt) {
                    case 'flv':
                        $mimeInfo['type'] = 'flv';
                        $mimeInfo['mime_type'] = 'video/x-flv';
                        break;
                    case 'wma':
                        $mimeInfo['type'] = 'wma';
                        $mimeInfo['mime_type'] = 'audio/x-ms-wma';
                        break;
                    default:
                        $mimeInfo['type'] = 'file';
                        $mimeInfo['mime_type'] = 'application/octet-stream';
                        if ($filetype != '') {
                            $mimeInfo['mime_type'] = $filetype;
                        }
                        break;
                }
                break;
        }
        if ($_MG_CONF['verbose']) {
            COM_errorLog("MG Upload: override mime type to: " . $mimeInfo['type']
                       . ' based upon file extension of: ' . $filetype);
        }
    }

    switch ($mimeInfo['mime_type']) {
        case 'audio/mpeg' :
            $format_type = MG_MP3;
            break;
        case 'image/gif' :
            $format_type = MG_GIF;
            break;
        case 'image/jpeg' :
        case 'image/jpg' :
            $format_type = MG_JPG;
            break;
        case 'image/png' :
            $format_type = MG_PNG;
            break;
        case 'image/bmp' :
            $format_type = MG_BMP;
            break;
        case 'application/x-shockwave-flash' :
            $format_type = MG_SWF;
            break;
        case 'application/zip' :
            $format_type = MG_ZIP;
            break;
        case 'video/mpeg' :
        case 'video/x-motion-jpeg' :
        case 'video/quicktime' :
        case 'video/mpeg' :
        case 'video/x-mpeg' :
        case 'video/x-mpeq2a' :
        case 'video/x-qtc' :
        case 'video/x-m4v' :
            $format_type = MG_MOV;
            break;
        case 'video/x-flv' :
            $format_type = MG_FLV;
            break;
        case 'image/tiff' :
            $format_type = MG_TIF;
            break;
        case 'image/x-targa' :
        case 'image/tga' :
            $format_type = MG_TGA;
            break;
        case 'image/psd' :
            $format_type = MG_PSD;
            break;
        case 'application/ogg' :
            $format_type = MG_OGG;
            break;
        case 'audio/x-ms-wma' :
        case 'audio/x-ms-wax' :
        case 'audio/x-ms-wmv' :
        case 'video/x-ms-asf' :
        case 'video/x-ms-asf-plugin' :
        case 'video/avi' :
        case 'video/msvideo' :
        case 'video/x-msvideo' :
        case 'video/avs-video' :
        case 'video/x-ms-wmv' :
        case 'video/x-ms-wvx' :
        case 'video/x-ms-wm' :
        case 'application/x-troff-msvideo' :
        case 'application/x-ms-wmz' :
        case 'application/x-ms-wmd' :
            $format_type = MG_ASF;
            break;
        case 'application/pdf' :
            $format_type = MG_OTHER;
            break;
        default:
            $format_type = MG_OTHER;
            break;
    }

    if (!($album->valid_formats & $format_type)) {
        return array(false, $LANG_MG02['format_not_allowed']);
    }

    $mimeType = $mimeInfo['mime_type'];
    if ($_MG_CONF['verbose']) {
        COM_errorLog("MG Upload: PHP detected mime type is : " . $filetype);
    }
    if ($filetype == 'video/x-m4v') {
        $mimeType = 'video/x-m4v';
        $mimeInfo['mime_type'] = 'video/x-m4v';
    }

    if ($replace > 0) {
        $sql = "SELECT * FROM {$_TABLES['mg_media']} WHERE media_id='" . addslashes($replace) . "'";
        $result = DB_query($sql);
        $row = DB_fetchArray($result);
        $media_filename = $row['media_filename'];
    } else {
        if ($_MG_CONF['preserve_filename'] == 1) {
            $loopCounter = 0;
            $digitCounter = 1;

            $file_name = stripslashes($file);
            $file_name = MG_replace_accents($file_name);
            $file_name = preg_replace("#[ ]#", "_", $file_name);  // change spaces to underscore
            $file_name = preg_replace('#[^\.\-,\w]#', '_', $file_name);  //only parenthesis, underscore, letters, numbers, comma, hyphen, period - others to underscore
            $file_name = preg_replace('#(_)+#', '_', $file_name);  //eliminate duplicate underscore

            $pos = strrpos($file_name, '.');
            if ($pos === false) {
                $basefilename = $file_name;
            } else {
                $basefilename = strtolower(substr($file_name, 0, $pos));
            }
            do {
                clearstatcache();
                $media_filename = substr(md5(uniqid(rand())), 0, $digitCounter) . '_' . $basefilename;
                $loopCounter++;
                if ($loopCounter > 16) {
                    $digitCounter++;
                    $loopCounter = 0;
                }
            } while (MG_file_exists($media_filename));

        } else {
            do {
                clearstatcache();
                $media_filename = md5(uniqid(rand()));
            } while (MG_file_exists($media_filename));

        }
    }
    // replace a few mime extentions here...
    //
    if ($mimeExt == 'php') {
        $mimeExt = 'phps';
    }
    if (in_array($mimeExt, array('pl', 'cgi', 'py', 'sh', 'rb'))) {
        $mimeExt = 'txt';
    }

    $disp_media_filename = $media_filename . '.' . $mimeExt;

    if ($_MG_CONF['verbose']) {
        COM_errorLog("MG Upload: Stored filename is : " . $disp_media_filename);
        COM_errorLog("MG Upload: Mime Type: " . $mimeType);
    }

    switch ($mimeType) {
        case 'image/psd' :
        case 'image/x-targa' :
        case 'image/tga' :
        case 'image/photoshop' :
        case 'image/x-photoshop' :
        case 'image/psd' :
        case 'application/photoshop' :
        case 'application/psd' :
        case 'image/tiff' :
        case 'image/gif' :
        case 'image/jpeg' :
        case 'image/jpg' :
        case 'image/png' :
        case 'image/bmp' :
            $dispExt = $mimeExt;

            if (in_array($mimeType, $_SPECIAL_IMAGES_MIMETYPE)) {
                $dispExt = 'jpg';
            }
            $media_orig = MG_getFilePath('orig', $media_filename, $mimeExt);
            $media_disp = MG_getFilePath('disp', $media_filename, $dispExt);
            $media_tn   = MG_getFilePath('tn',   $media_filename, $dispExt);

            $mimeType = $mimeInfo['mime_type'];
            // process image file
            $media_time = getOriginationTimestamp($filename);
            if ($media_time == null || $media_time < 0) {
                $media_time = time();
            }

            if ($_MG_CONF['verbose']) {
                COM_errorLog("MG Upload: About to move/copy file");
            }
            $rc = @copy($filename, $media_orig);

            if ($rc != 1) {
                COM_errorLog("Media Upload - Error moving uploaded file....");
                COM_errorLog("Media Upload - Unable to copy file to: " . $media_orig);
                $errors++;
                $errMsg .= sprintf($LANG_MG02['move_error'], $filename);
            } else {
                if ($purgefiles) {
                    @unlink($importSource);
                }
                @chmod($media_orig, 0644);

                list($rc, $msg) = MG_convertImage($media_orig, $media_tn, $media_disp, $mimeExt, $mimeType, $album_id, $media_filename, $dnc);
                if ($rc == false) {
                    $errors++;
                    $errMsg .= $msg; // sprintf($LANG_MG02['convert_error'],$filename);
                } else {
                    $mediaType = 0;
                    if ($_MG_CONF['discard_original'] == 1 &&
                        ($mimeType == 'image/jpeg' || $mimeType == 'image/jpg' ||
                         $mimeType == 'image/png'  || $mimeType == 'image/bmp' ||
                         $mimeType == 'image/gif')) {
                        if ($_MG_CONF['jhead_enabled'] && ($mimeType == 'image/jpeg' || $mimeType == 'image/jpg')) {
                            $rc = MG_execWrapper('"' . $_MG_CONF['jhead_path'] . "/jhead" . '"' . " -te " . $media_orig . " " . $media_disp);
                        }
                        @unlink($media_orig);
                    }

                    if ($album->wm_auto) {
                        if ($_MG_CONF['discard_original'] == 1) {
                            $rc = MG_watermark($media_disp, $album_id, 1);
                            if ($rc == true) {
                                $successfulWatermark = 1;
                            }
                        } else {
                            $rc1 = MG_watermark($media_orig, $album_id, 1);
                            $rc2 = MG_watermark($media_disp, $album_id, 0);
                            if ($rc1 == ture && $rc2 == true) {
                                $successfulWatermark = 1;
                            }
                        }
                    }
                    if ($dnc != 1) {
                        if (!in_array($mimeType, $_SPECIAL_IMAGES_MIMETYPE)) {
                            $mimeExt = 'jpg';
                            $mimeType = 'image/jpeg';
                        }
                    }
                }
            }
            break;

        case 'video/quicktime' :
        case 'video/mpeg' :
        case 'video/x-flv' :
        case 'video/x-ms-asf' :
        case 'video/x-ms-asf-plugin' :
        case 'video/avi' :
        case 'video/msvideo' :
        case 'video/x-msvideo' :
        case 'video/avs-video' :
        case 'video/x-ms-wmv' :
        case 'video/x-ms-wvx' :
        case 'video/x-ms-wm' :
        case 'application/x-troff-msvideo' :
        case 'application/x-shockwave-flash' :
        case 'video/mp4' :
        case 'video/x-m4v' :
            $mimeType = $mimeInfo['mime_type'];

            if ($filetype == 'video/mp4') {
                $mimeExt = 'mp4';
            }

            // process video format
            $media_orig = MG_getFilePath('orig', $media_filename, $mimeExt);

            $rc = @copy($filename, $media_orig);

            if ($rc != 1) {
                COM_errorLog("MG Upload: Error moving uploaded file in video processing....");
                COM_errorLog("Media Upload - Unable to copy file to: " . $media_orig);
                $errors++;
                $errMsg .= sprintf($LANG_MG02['move_error'],$filename);
            } else {
                if ($purgefiles) {
                    @unlink($importSource);
                }
                @chmod($media_orig, 0644);
                $mediaType = 1;
            }
            $video_attached_thumbnail = MG_videoThumbnail($album_id, $media_orig, $media_filename);
            break;

        case 'application/ogg' :
        case 'audio/mpeg' :
        case 'audio/x-ms-wma' :
        case 'audio/x-ms-wax' :
        case 'audio/x-ms-wmv' :
            $mimeType = $mimeInfo['mime_type'];
            // process audio format
            $media_orig = MG_getFilePath('orig', $media_filename, $mimeExt);

            $rc = @copy($filename, $media_orig);

            COM_errorLog("MG Upload: Extracting audio meta data");

            if (isset($mimeInfo['tags']['id3v1']['title'][0])) {
                if ($caption == '') {
                    $caption = $mimeInfo['tags']['id3v1']['title'][0];
                }
            }
            if (isset($mimeInfo['tags']['id3v1']['artist'][0])) {
                $artist = addslashes($mimeInfo['tags']['id3v1']['artist'][0]);
            }

            if (isset($mimeInfo['tags']['id3v2']['genre'][0])) {
                $genre = addslashes($mimeInfo['tags']['id3v2']['genre'][0]);
            }
            if (isset($mimeInfo['tags']['id3v1']['album'][0])) {
                $musicAlbum = addslashes($mimeInfo['tags']['id3v1']['album'][0]);
            }
            if ($rc != 1) {
                COM_errorLog("Media Upload - Error moving uploaded file in audio processing....");
                COM_errorLog("Media Upload - Unable to copy file to: " . $media_orig);
                $errors++;
                $errMsg .= sprintf($LANG_MG02['move_error'], $filename);
            } else {
                if ($purgefiles) {
                    @unlink($importSource);
                }
                $mediaType = 2;
            }
            break;

        case 'zip' :
        case 'application/zip' :
            if ($_MG_CONF['zip_enabled']) {
                $errMsg .= MG_processZip($filename, $album_id, $purgefiles, $media_filename);
                break;
            }
            // NO BREAK HERE, fall through if enable zip isn't allowed
        default:
            $media_orig = MG_getFilePath('orig', $media_filename, $mimeExt);

            $mimeType = $mimeInfo['mime_type'];

            $rc = @copy($filename, $media_orig);

            if ($rc != 1) {
                COM_errorLog("Media Upload - Error moving uploaded file in generic processing....");
                COM_errorLog("Media Upload - Unable to copy file to: " . $media_orig);
                $errors++;
                $errMsg .= sprintf($LANG_MG02['move_error'], $filename);
            } else {
                if ($purgefiles) {
                    @unlink($importSource);
                }
                $mediaType = 4;
            }
            $mediaType = 4;
            break;
    }

    // update quota
    $quota = $album->album_disk_usage;

    $quota += @filesize(MG_getFilePath('orig', $media_filename, $mimeExt));
    if ($_MG_CONF['discard_original'] == 1) {
        $quota += @filesize(MG_getFilePath('disp', $media_filename, 'jpg'));
    }
    DB_change($_TABLES['mg_albums'], 'album_disk_usage', $quota, 'album_id', intval($album_id));

    if ($errors) {
        @unlink($tmpPath);
        COM_errorLog("MG Upload: Problem uploading a media object");
        return array(false, $errMsg);
    }

    if (($mimeType != 'application/zip' || $_MG_CONF['zip_enabled'] == 0) && $errors == 0) {

        // Now we need to process an uploaded thumbnail

        if ($gotTN == 1) {
            $mp3TNFilename = $_MG_CONF['tmp_path'] . 'mp3tn' . time() . '.jpg';
            $fn = fopen($mp3TNFilename, "w");
            fwrite($fn, $mp3AttachdedThumbnail);
            fclose($fn);
            $saveThumbnailName = $_MG_CONF['path_mediaobjects'] . 'tn/' . $media_filename[0] . '/tn_' . $media_filename;
            MG_attachThumbnail($album_id, $mp3TNFilename, $saveThumbnailName);
            @unlink($mp3TNFilename);
            $atttn = 1;
        } else if ($atttn == 1) {
            $saveThumbnailName = $_MG_CONF['path_mediaobjects'] . 'tn/' . $media_filename[0] . '/tn_' . $media_filename;
            MG_attachThumbnail($album_id, $thumbnail, $saveThumbnailName);
        }
        if ($video_attached_thumbnail) {
            $atttn = 1;
        }
        if ($_MG_CONF['verbose']) {
            COM_errorLog("MG Upload: Building SQL and preparing to enter database");
        }

        if ($_MG_CONF['htmlallowed'] != 1) {
            $media_desc     = addslashes(htmlspecialchars(strip_tags(COM_checkWords(COM_killJS($description)))));
            $media_caption  = addslashes(htmlspecialchars(strip_tags(COM_checkWords(COM_killJS($caption)))));
            $media_keywords = addslashes(htmlspecialchars(strip_tags(COM_checkWords(COM_killJS($keywords)))));
        } else {
            $media_desc     = addslashes(COM_checkHTML(COM_killJS($description)));
            $media_caption  = addslashes(COM_checkHTML(COM_killJS($caption)));
            $media_keywords = addslashes(COM_checkHTML(COM_killJS($keywords)));
        }

        // Check and see if moderation is on.  If yes, place in mediasubmission
        if ($album->moderate == 1 && !$root_album->owner_id) {
          $tableMedia      = $_TABLES['mg_mediaqueue'];
          $tableMediaAlbum = $_TABLES['mg_media_album_queue'];
          $queue = 1;
        } else {
          $tableMedia      = $_TABLES['mg_media'];
          $tableMediaAlbum = $_TABLES['mg_media_albums'];
          $queue = 0;
        }

        $original_filename = addslashes($file);

        if ($album->filename_title) {
            if ($media_caption == '') {
                $pos = strrpos($original_filename, '.');
                if($pos === false) {
                    $media_caption = $original_filename;
                } else {
                    $media_caption = substr($original_filename, 0, $pos);
                }
            }
        }

        if ($_MG_CONF['verbose']) {
            COM_errorLog("MG Upload: Inserting media record into mg_media");
        }

        $resolution_x = 0;
        $resolution_y = 0;
        // try to find a resolution if video...
        if ($mediaType == 1) {
            switch ($mimeType) {
                case 'application/x-shockwave-flash' :
                case 'video/quicktime' :
                case 'video/mpeg' :
                case 'video/x-m4v' :
                    $resolution_x = -1;
                    $resolution_y = -1;
                    if (isset($mimeInfo['video']['resolution_x']) &&
                        isset($mimeInfo['video']['resolution_x'])) {
                        $resolution_x = $mimeInfo['video']['resolution_x'];
                        $resolution_y = $mimeInfo['video']['resolution_y'];
                    }
                    break;

                case 'video/x-flv' :
                    if ($mimeInfo['video']['resolution_x'] < 1 ||
                        $mimeInfo['video']['resolution_y'] < 1) {
                        $resolution_x = -1;
                        $resolution_y = -1;
                        if (isset($mimeInfo['meta']['onMetaData']['width']) &&
                            isset($mimeInfo['meta']['onMetaData']['height'])) {
                            $resolution_x = $mimeInfo['meta']['onMetaData']['width'];
                            $resolution_y = $mimeInfo['meta']['onMetaData']['height'];
                        }
                    } else {
                        $resolution_x = $mimeInfo['video']['resolution_x'];
                        $resolution_y = $mimeInfo['video']['resolution_y'];
                    }
                    break;

                case 'video/x-ms-asf' :
                case 'video/x-ms-asf-plugin' :
                case 'video/avi' :
                case 'video/msvideo' :
                case 'video/x-msvideo' :
                case 'video/avs-video' :
                case 'video/x-ms-wmv' :
                case 'video/x-ms-wvx' :
                case 'video/x-ms-wm' :
                case 'application/x-troff-msvideo' :
                    $resolution_x = -1;
                    $resolution_y = -1;
                    if (isset($mimeInfo['video']['streams']['2']['resolution_x']) &&
                        isset($mimeInfo['video']['streams']['2']['resolution_y'])) {
                        $resolution_x = $mimeInfo['video']['streams']['2']['resolution_x'];
                        $resolution_y = $mimeInfo['video']['streams']['2']['resolution_y'];
                    }
                    break;
            }
        }

        if ($replace > 0) {
            $sql = "UPDATE " . $tableMedia . " SET "
                 . "media_filename='"          . addslashes($media_filename)      . "',"
                 . "media_original_filename='" . $original_filename               . "',"
                 . "media_mime_ext='"          . addslashes($mimeExt)             . "',"
                 . "mime_type='"               . addslashes($mimeType)            . "',"
                 . "media_time='"              . addslashes($media_time)          . "',"
                 . "media_user_id='"           . addslashes($media_user_id)       . "',"
                 . "media_type='"              . addslashes($mediaType)           . "',"
                 . "media_upload_time='"       . addslashes($media_upload_time)   . "',"
                 . "media_watermarked='"       . addslashes($successfulWatermark) . "',"
                 . "media_resolution_x='"      . intval($resolution_x)            . "',"
                 . "media_resolution_y='"      . intval($resolution_y)            . "' "
                 . "WHERE media_id='"          . addslashes($replace)             . "'";
            DB_query($sql);
        } else {
            $sql = "INSERT INTO " . $tableMedia
                 . " (media_id,media_filename,media_original_filename,media_mime_ext,"
                 . "media_exif,mime_type,media_title,media_desc,media_keywords,media_time,"
                 . "media_views,media_comments,media_votes,media_rating,media_tn_attached,"
                 . "media_tn_image,include_ss,media_user_id,media_user_ip,media_approval,"
                 . "media_type,media_upload_time,media_category,media_watermarked,v100,"
                 . "maint,media_resolution_x,media_resolution_y,remote_media,remote_url,"
                 . "artist,album,genre) "
                 . "VALUES ('" . addslashes($new_media_id)        . "','"
                               . addslashes($media_filename)      . "','"
                               . $original_filename               . "','"
                               . addslashes($mimeExt)             . "','1','"
                               . addslashes($mimeType)            . "','"
                               . addslashes($media_caption)       . "','"
                               . addslashes($media_desc)          . "','"
                               . addslashes($media_keywords)      . "','"
                               . addslashes($media_time)          . "','0','0','0','0.00','"
                               . addslashes($atttn)               . "','','1','"
                               . addslashes($media_user_id)       . "','','0','"
                               . addslashes($mediaType)           . "','"
                               . addslashes($media_upload_time)   . "','"
                               . addslashes($category)            . "','"
                               . addslashes($successfulWatermark) . "','0','0',"
                               . intval($resolution_x)            . ","
                               . intval($resolution_y)            . ",0,'','"
                               . addslashes($artist)              . "','"
                               . addslashes($musicAlbum)          . "','"
                               . addslashes($genre)               . "');";
            DB_query($sql);

            if ($_MG_CONF['verbose']) {
                COM_errorLog("MG Upload: Updating Album information");
            }
            $x = 0;
            $sql = "SELECT MAX(media_order) + 10 AS media_seq FROM {$_TABLES['mg_media_albums']} WHERE album_id = " . intval($album_id);
            $result = DB_query($sql);
            $row = DB_fetchArray($result);
            $media_seq = $row['media_seq'];
            if ($media_seq < 10) {
                $media_seq = 10;
            }

            $sql = "INSERT INTO " . $tableMediaAlbum
                 . " (media_id, album_id, media_order) "
                 . "VALUES ('"
                 . addslashes($new_media_id) . "', "
                 . intval($album_id)         . ", "
                 . intval($media_seq)        . ")";
            DB_query($sql);

            if ($mediaType == 1 && $resolution_x > 0 && $resolution_y > 0 && $_MG_CONF['use_default_resolution'] == 0) {
                DB_save($_TABLES['mg_playback_options'], 'media_id,option_name,option_value', "'$new_media_id','width', '$resolution_x'");
                DB_save($_TABLES['mg_playback_options'], 'media_id,option_name,option_value', "'$new_media_id','height','$resolution_y'");
            }
            PLG_itemSaved($new_media_id, 'mediagallery');

            // update the media count for the album, only if no moderation...
            if ($queue == 0) {
                $album->media_count++;
                DB_change($_TABLES['mg_albums'], 'media_count', $album->media_count, 'album_id', $album->id);

                MG_updateAlbumLastUpdate($album->id);

                if ($album->cover == -1 && ($mediaType == 0 || $atttn == 1)) {
                    if ($atttn == 1) {
                        $covername = 'tn_' . $media_filename;
                    } else {
                        $covername = $media_filename;
                    }
                    DB_change($_TABLES['mg_albums'], 'album_cover_filename', $covername, 'album_id', $album->id);
                }
//                MG_resetAlbumCover($album->id);
            }
            $x++;
        }
    }

    if ($queue) {
        $errMsg .= $LANG_MG01['successful_upload_queue']; // ' successfully placed in Moderation queue';
    } else {
        $errMsg .= $LANG_MG01['successful_upload']; // ' successfully uploaded to album';
    }
    if ($queue == 0) {
        require_once $_CONF['path'] . 'plugins/mediagallery/include/rssfeed.php';
        MG_buildFullRSS();
        MG_buildAlbumRSS($album_id);
    }
    COM_errorLog("MG Upload: Successfully uploaded a media object");
    @unlink($tmpPath);
    return array(true, $errMsg);
}

function MG_attachThumbnail($aid, $thumbnail, $mediaFilename)
{
    global $_TABLES, $_MG_CONF;

    if ($_MG_CONF['verbose']) {
        COM_errorLog("MG Upload: Processing attached thumbnail: " . $thumbnail );
    }

    $sql = "SELECT tn_size, tnheight, tnwidth "
         . "FROM {$_TABLES['mg_albums']} WHERE album_id = " . intval($aid);
    $result = DB_query($sql);
    $A = DB_fetchArray($result);
    list($tnHeight, $tnWidth) = MG_getTNSize($A['tn_size'], $A['tnheight'], $A['tnwidth']);

    $tn_mime_type = MG_getMediaMetaData($thumbnail);
    if (!isset($tn_mime_type['mime_type'])) {
        $tn_mime_type['mime_type'] = '';
    }
    switch ($tn_mime_type['mime_type']) {
        case 'image/gif' :
            $tnExt = '.gif';
            break;
        case 'image/jpeg' :
        case 'image/jpg' :
            $tnExt = '.jpg';
            break;
        case 'image/png' :
            $tnExt = '.png';
            break;
        case 'image/bmp' :
            $tnExt = '.bmp';
            break;
        default:
            COM_errorLog("MG_attachThumbnail: Invalid graphics type for attached thumbnail.");
            return false;
    }
    $attach_tn = $mediaFilename . $tnExt;
    list($rc,$msg) = MG_resizeImage($thumbnail, $attach_tn, $tnHeight, $tnWidth, $tn_mime_type['mime_type'], 1, $_MG_CONF['tn_jpg_quality']);
    return true;
}

function MG_notifyModerators($aid)
{
    global $LANG_DIRECTION, $_USER, $_MG_CONF, $_CONF, $_TABLES, $LANG_MG01;

    $sql = "SELECT moderate, album_title, mod_group_id "
         . "FROM {$_TABLES['mg_albums']} WHERE album_id = " . intval($aid);
    $result = DB_query($sql);
    $A = DB_fetchArray($result);
    
    if ($A['moderate'] != 1 || SEC_hasRights('mediagallery.admin')) {
        return true;
    }

    require_once $_CONF['path'] . 'plugins/mediagallery/include/lib/phpmailer/class.phpmailer.php';

    $media_user_id = $_USER['uid'];

    if( empty( $LANG_DIRECTION )) {
        // default to left-to-right
        $direction = 'ltr';
    } else {
        $direction = $LANG_DIRECTION;
    }

    $charset = COM_getCharset();

    COM_clearSpeedlimit(600,'mgnotify');
    $last = COM_checkSpeedlimit ('mgnotify');
    if ( $last == 0 ) {
        $mail = new PHPMailer();
        $mail->CharSet = $charset;
        if ($_CONF['mail_settings']['backend'] == 'smtp' ) {
            $mail->Host     = $_CONF['mail_settings']['host'] . ':' . $_CONF['mail_settings']['port'];
            $mail->SMTPAuth = $_CONF['mail_settings']['auth'];
            $mail->Username = $_CONF['mail_settings']['username'];
            $mail->Password = $_CONF['mail_settings']['password'];
            $mail->Mailer = "smtp";
        } elseif ($_CONF['mail_settings']['backend'] == 'sendmail') {
            $mail->Mailer = "sendmail";
            $mail->Sendmail = $_CONF['mail_settings']['sendmail_path'];
        } else {
            $mail->Mailer = "mail";
        }
        $mail->WordWrap = 76;
        $mail->IsHTML(true);
        $mail->Subject = $LANG_MG01['new_upload_subject'] . $_CONF['site_name'];

        if (!isset($_USER['uid']) || $_USER['uid'] < 2  ) {
            $uname = 'Anonymous';
        } else {
            $uname = DB_getItem($_TABLES['users'], 'username', 'uid=' . intval($media_user_id));
        }
        // build the template...
        $T = COM_newTemplate( MG_getTemplatePath($aid) );
        $T->set_file('email', 'modemail.thtml');
        $T->set_var(array(
            'direction'         =>  $direction,
            'charset'           =>  $charset,
            'lang_new_upload'   =>  $LANG_MG01['new_upload_body'],
            'lang_details'      =>  $LANG_MG01['details'],
            'lang_album_title'  =>  'Album',
            'lang_uploaded_by'  =>  $LANG_MG01['uploaded_by'],
            'username'          =>  $uname,
            'album_title'       =>  strip_tags($A['title']),
            'url_moderate'      =>  '<a href="' . $_MG_CONF['site_url'] . '/admin.php?album_id=' . $aid . '&mode=moderate">Click here to view</a>',
            'site_name'         =>  $_CONF['site_name'] . ' - ' . $_CONF['site_slogan'],
            'site_url'          =>  $_CONF['site_url'],
        ));
        $body .= $T->finish($T->parse('output','email'));
        $mail->Body = $body;

        $altbody  = $LANG_MG01['new_upload_body'] . $A['title'];
        $altbody .= "\n\r\n\r";
        $altbody .= $LANG_MG01['details'];
        $altbody .= "\n\r";
        $altbody .= $LANG_MG01['uploaded_by'] . ' ' . $uname . "\n\r";
        $altbody .= "\n\r\n\r";
        $altbody .= $_CONF['site_name'] . "\n\r";
        $altbody .= $_CONF['site_url'] . "\n\r";

        $mail->AltBody = $altbody;

        $mail->From = $_CONF['site_mail'];
        $mail->FromName = $_CONF['site_name'];

        $groups = MG_getGroupList($A['mod_group_id']);
        $groupList = implode(',',$groups);

        $sql = "SELECT DISTINCT {$_TABLES['users']}.uid,username,fullname,email "
              ."FROM {$_TABLES['group_assignments']},{$_TABLES['users']} "
              ."WHERE {$_TABLES['users']}.uid > 1 "
              ."AND {$_TABLES['users']}.uid = {$_TABLES['group_assignments']}.ug_uid "
              ."AND ({$_TABLES['group_assignments']}.ug_main_grp_id IN ({$groupList}))";

        $result = DB_query($sql);
        $nRows = DB_numRows($result);
        $toCount = 0;
        for ($i=0;$i < $nRows; $i++ ) {
            $row = DB_fetchArray($result);
            if ( $row['email'] != '' ) {
                if ($_MG_CONF['verbose'] ) {
                    COM_errorLog("MG Upload: Sending notification email to: " . $row['email'] . " - " . $row['username']);
                }
                $toCount++;
                $mail->AddAddress($row['email'], $row['username']);
            }
        }
        if ( $toCount > 0 ) {
            if(!$mail->Send()) {
                COM_errorLog("MG Upload: Unable to send moderation email - error:" . $mail->ErrorInfo);
            }
        } else {
            COM_errorLog("MG Upload: Error - Did not find any moderators to email");
        }
        COM_updateSpeedlimit ('mgnotify');
    }
    return true;
}

/**
* Get a list (actually an array) of all groups this group belongs to.
*
* @param   basegroup   int     id of group
* @return              array   array of all groups 'basegroup' belongs to
*
*/
function MG_getGroupList($basegroup)
{
    global $_TABLES;

    $to_check = array();
    array_push($to_check, $basegroup);

    $checked = array();

    while (sizeof($to_check) > 0) {
        $thisgroup = intval(array_pop($to_check));
        if ($thisgroup > 0) {
            $result = DB_query("SELECT ug_grp_id FROM {$_TABLES['group_assignments']} WHERE ug_main_grp_id = $thisgroup");
            $numGroups = DB_numRows($result);
            for ($i = 0; $i < $numGroups; $i++) {
                $A = DB_fetchArray($result);
                if (!in_array($A['ug_grp_id'], $checked)) {
                    if (!in_array($A['ug_grp_id'], $to_check)) {
                        array_push($to_check, $A['ug_grp_id']);
                    }
                }
            }
            $checked[] = $thisgroup;
        }
    }

    return $checked;
}
?>