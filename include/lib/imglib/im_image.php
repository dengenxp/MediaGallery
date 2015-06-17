<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | im-image.php                                                             |
// |                                                                          |
// | ImageMagick Graphic Library interface                                    |
// +--------------------------------------------------------------------------+
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

function _img_getIMversion()
{
    global $_CONF, $_MG_CONF;

    // get im version
    $binary = 'identify' . ((PHP_OS == 'WINNT') ? '.exe' : '');
    $cmd = $_MG_CONF['path_to_imagemagick'] . $binary;
    list($results, $status) = UTL_exec($cmd);
    if ($status != 0) {
        COM_errorLog("UTL_execWrapper: Failed Command: " . $cmd);
        return false;
    }
    foreach ($results as $resultLine) {
        if (preg_match('/(ImageMagick|GraphicsMagick)\s+([\d\.r-]+)/', $resultLine, $matches)) {
            $version = array($matches[1], $matches[2]);
        }
    }
    return $version;
}

/*
 * ImageMagick specific rotate function
 */
function _img_RotateImage($srcImage, $direction, $mimeType)
{
    global $_CONF, $_MG_CONF;

    switch( $direction ) {
        case 'right' :
            $IM_rotate = "90";
            break;
        case 'left' :
            $IM_rotate = "-90";
            break;
        default :
            COM_errorLog("_img_rotateImage: Invalid direction passed to rotate, must be left or right");
            return array(false,'Invalid direction passed to rotate, must be left or right');
    }

    $tmp = pathinfo($srcImage);
    $tmpImage = $tmp['dirname'] .'/' . $tmp['filename'] . '_RT.' . $tmp['extension'];

    $binary = 'convert' . ((PHP_OS == 'WINNT') ? '.exe' : '');
    UTL_execWrapper('"' . $_MG_CONF['path_to_imagemagick'] . $binary . '"'
                    . " -quality 100 -rotate " . $IM_rotate . " $srcImage $tmpImage");
    if ($_MG_CONF['jhead_enabled'] == 1 && ($mimeType == 'image/jpeg' || $mimeType == 'image/jpg')) {
        $rc = UTL_execWrapper('"' . $_MG_CONF['jhead_path'] . "/jhead" . '"' . " -te " . $srcImage . " " . $tmpImage);
    }
    $rc = copy($tmpImage, $srcImage);
    @unlink($tmpImage);
    return array(true,'');
}

function _img_resizeImage($srcImage, $destImage,
                          $sImageHeight, $sImageWidth,
                          $dImageHeight, $dImageWidth,
                          $mimeType,
                          $JpegQuality=85)
{
    global $_CONF, $_MG_CONF;

    $version = _img_getIMversion();
    $noLayers = (version_compare($version[1], "6.3.4") == -1) ? 1 : 0;

    $opt = '-quality ' . $JpegQuality;
    $opt .= ' -format jpg';
    if ($_MG_CONF['verbose']) {
        $opt .= ' -verbose';
        COM_errorLog("_img_resizeImage: Resizing using ImageMagick src = " . $srcImage . " mimetype = " . $mimeType);
    }
    if ($mimeType == 'image/gif') {
        $opt .= ' -coalesce';
        $opt .= ($noLayers == 0) ? ' -layers Optimize' : '';
    } else {
        $opt .= ' -flatten';
    }
    if (($dImageHeight > $sImageHeight) && ($dImageWidth > $sImageWidth)) {
        $dImageWidth  = $sImageWidth;
        $dImageHeight = $sImageHeight;
    }
    $newdim = $dImageWidth . "x" . $dImageHeight;

//    if ( $mimeType == 'image/gif' ) {
//        $rc = UTL_execWrapper('"' . $_CONF['path_to_mogrify'] . "/convert" . '"' . " $opt $srcImage -resize $newdim $destImage");
//    } else {
//        $rc = UTL_execWrapper('"' . $_CONF['path_to_mogrify'] . "/convert" . '"' . " $opt $srcImage -size $newdim -geometry $newdim $destImage");
//    }

    $binary = 'convert' . ((PHP_OS == 'WINNT') ? '.exe' : '');
    $rc = UTL_execWrapper('"' . $_MG_CONF['path_to_imagemagick'] . $binary . '"' . " $opt -resize $newdim $srcImage $destImage");

//  $rc = UTL_execWrapper('"' . $_CONF['path_to_mogrify'] . '"' . " $opt -resize $newdim -write $destImage $srcImage");

//    $exec_path = $_CONF['path_to_mogrify'];
//    $exec_path = str_replace('/mogrify', '/convert', $exec_path);
//    $rc = UTL_execWrapper('"' . $exec_path . '"' . " $opt $srcImage -size $newdim -geometry $newdim $destImage");

    if ($rc != true) {
        COM_errorLog("_img_resizeImage: Error - Unable to resize image - ImageMagick convert failed.");
        return array(false,'Error - Unable to resize image - ImageMagick convert failed.');
    }
    clearstatcache();
    if (!file_exists($destImage) || !filesize($destImage)) {
        COM_errorLog("_img_resizeImage: Error - Unable to resize image - ImageMagick convert failed.");
        return array(false,'Error - Unable to resize image - ImageMagick convert failed.');
    }
    if (($mimeType != 'image/gif') && ($_MG_CONF['jhead_enabled'] == 1)) {
        UTL_execWrapper('"' . $_MG_CONF['jhead_path'] . "/jhead" . '"' . " -v -te " . $srcImage . " " . $destImage);
    }
    return array(true,'');
}

/*
 * ImageMagick Specific method to convert image
 */
function _img_convertImageFormat($srcImage,$destImage,$destFormat, $mimeType)
{
    global $_CONF, $_MG_CONF;

    COM_errorLog("_img_convertImageFormat: Converting image to " . $destFormat);
    $binary = 'convert' . ((PHP_OS == 'WINNT') ? '.exe' : '');
    $rc = UTL_execWrapper('"' . $_MG_CONF['path_to_imagemagick'] . $binary . '"'
                        . " -flatten -quality " . $_MG_CONF['jpg_orig_quality'] . " $srcImage -geometry +0+0 $destImage");
    if ( $rc != true ) {
        COM_errorLog("_img_convertImageFormat: Error converting " . $srcImage . " to " . $destImage);
        return array(false,'ImageMagick convert failed to convert image.');
    }
    clearstatcache();
    if ( !file_exists($destImage) || !filesize($destImage) ) {
        COM_errorLog("_img_resizeImage: Error - Unable to resize image - ImageMagick convert failed.");
        return array(false,'ImageMagick convert failed to convert image.');
    }

    if ( $srcImage != $destImage) {
        @unlink($srcImage);
    }
    return array(true,'');
}

function _img_watermarkImage($origImage, $watermarkImage, $opacity, $location, $mimeType )
{
    global $_CONF, $_MG_CONF;

    if ( $_MG_CONF['verbose'] ) {
        COM_errorLog("_img_watermarkImage: Using ImageMagick to watermark image.");
    }
    switch( $location ) {
        case 'topleft' : // 1 :
            $location = "NorthWest";
            break;
        case 'topcenter' : // 2:
            $location = "North";
            break;
        case 'topright': // 3:
            $location = "NorthEast";
            break;
        case 'leftmiddle' : // 4 :
            $location = "West";
            break;
        case 'center' : // 5 :
            $location = "Center";
            break;
        case 'rightmiddle' : // 6 :
            $location = "East";
            break;
        case 'bottomleft' : //7 :
            $location = "SouthWest";
            break;
        case 'bottomcenter' : // 8 :
            $location = "South";
            break;
        case 'bottomright' : // 9 :
            $location = "SouthEast";
            break;
        default:
            COM_errorLog("_img_watermarkImage: Unknown watermark location: " . $location);
            return array(false,'Unknown watermark location');
            break;
    }

    $binary_convert   = 'convert'   . ((PHP_OS == 'WINNT') ? '.exe' : '');
    $binary_composite = 'composite' . ((PHP_OS == 'WINNT') ? '.exe' : '');
    $rc = UTL_execWrapper('"' . $_MG_CONF['path_to_imagemagick'] . $binary_convert . '"'
                        . " $watermarkImage -fill grey50 -colorize 40  miff:- | "
                        . '"' . $_MG_CONF['path_to_imagemagick'] . $binary_composite . '"'
                        . " -dissolve " . $opacity . " -gravity " . $location . " - $origImage $origImage");
    COM_errorLog("_img_watermarkImage: Watermark successfully applied (ImageMagick)");
    return array($rc,'');
}

function _img_resizeImage_crop($srcImage, $destImage, 
                               $src_x, $src_y,
                               $new_x, $new_y,
                               $sImageHeight, $sImageWidth, 
                               $dImageHeight, $dImageWidth, 
                               $mimeType,
                               $JpegQuality=85)
{
    global $_CONF, $_MG_CONF;

    $version = _img_getIMversion();
    $noLayers = (version_compare($version[1], "6.3.4") == -1) ? 1 : 0;

    $opt = '-quality ' . $JpegQuality;
//    $opt .= ' -format jpg';
    if ($_MG_CONF['verbose']) {
        $opt .= ' -verbose';
        COM_errorLog("_img_resizeImage_crop: Resizing using ImageMagick src = " . $srcImage . " mimetype = " . $mimeType);
    }
    if ($mimeType == 'image/gif') {
        $opt .= ' -coalesce';
        $opt .= ($noLayers == 0) ? ' -layers Optimize' : '';
    } else {
        $opt .= ' -flatten';
    }
    if (($dImageHeight > $sImageHeight) && ($dImageWidth > $sImageWidth)) {
        $dImageWidth  = $sImageWidth;
        $dImageHeight = $sImageHeight;
    }
    $srcdim = $sImageWidth . "x" . $sImageHeight . "+" . $src_x . "+" . $src_y;
    $newdim = $dImageWidth . "x" . $dImageHeight;

    $binary = 'convert' . ((PHP_OS == 'WINNT') ? '.exe' : '');
    $rc = UTL_execWrapper('"' . $_MG_CONF['path_to_imagemagick'] . $binary . '"' 
                          . " $opt -crop $srcdim +repage -geometry $newdim $srcImage $destImage");
    if ($rc != true) {
        COM_errorLog("_img_resizeImage_crop: Error - Unable to resize image - ImageMagick convert failed.");
        return array(false, 'Error - Unable to resize image - ImageMagick convert failed.');
    }
    clearstatcache();
    if (!file_exists($destImage) || !filesize($destImage)) {
        COM_errorLog("_img_resizeImage_crop: Error - Unable to resize image - ImageMagick convert failed.");
        return array(false, 'Error - Unable to resize image - ImageMagick convert failed.');
    }
    if (($mimeType != 'image/gif') && ($_MG_CONF['jhead_enabled'] == 1)) {
        UTL_execWrapper('"' . $_MG_CONF['jhead_path'] . "/jhead" . '"' . " -v -te " . $srcImage . " " . $destImage);
    }
    return array(true, '');
}

?>