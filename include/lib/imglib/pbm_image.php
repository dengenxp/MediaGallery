<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | pbm_image.php                                                            |
// |                                                                          |
// | NetPBM Graphic Library interface                                         |
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

function _img_resizeImage($srcImage, $destImage,
                          $sImageHeight, $sImageWidth,
                          $dImageHeight, $dImageWidth,
                          $mimeType,
                          $JpegQuality=85)
{
    global $_CONF, $_MG_CONF;

    if ($mimeType == 'image/x-targa' || $mimeType == 'image/tga') {
        COM_errorLog("_img_resizeImage: TGA files not supported by NetPBM");
        return array(false,'TGA format not supported by NetPBM');
    }

    $ext = (PHP_OS == 'WINNT') ? '.exe' : '';

    if ($mimeType == 'image/gif' && !file_exists($_CONF['path_to_netpbm'] . 'pamtogif' . $ext)) {
        COM_errorLog("_img_resizeImage: NetPBM installation does have have pamtogif binary.");
        return array(false,'NetPBM installation does have have pamtogif binary.');
    }

    if ($_MG_CONF['verbose']) {
        COM_errorLog("_img_resizeImage: Resizing using NetPBM src = " . $srcImage . " mimetype = " . $mimeType);
    }

    // determine which program to use, pamscale or pnmscale...
    $pamscale = 'pnmscale';
    if (file_exists($_CONF['path_to_netpbm'] . 'pamscale' . $ext)) {
        $pamscale = 'pamscale';
    }

    if (($dImageHeight > $sImageHeight) && ($dImageWidth > $sImageWidth)) {
        $dImageWidth  = $sImageWidth;
        $dImageHeight = $sImageHeight;
    }

    switch ($mimeType) {
        case 'image/jpeg' :
        case 'image/jpg' :
            $cmd1 = 'jpegtopnm';
            $cmd2 = 'pnmtojpeg';
            break;
        case 'image/bmp' :
            $cmd1 = 'bmptopnm';
            $cmd2 = 'ppmtobmp';
            break;
        case 'image/gif' :
            $cmd1 = 'giftopnm';
            $cmd2 = 'pamtogif';
            break;
        case 'image/png' :
            $cmd1 = 'pngtopnm'; // 'pngtopam'
            $cmd2 = 'pnmtopng';
            break;
        case 'image/tiff' :
            $cmd1 = 'tifftopnm';
            $cmd2 = 'pnmtotiff';
            break;
    }
    $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . $cmd1 . '" ' . $srcImage . ' | '
                        . '"' . $_CONF['path_to_netpbm'] . $pamscale . '"' . " -xsize=$dImageWidth -ysize=$dImageHeight" . ' | '
                        . '"' . $_CONF['path_to_netpbm'] . $cmd2 . '"' . " -quality=$JpegQuality > " . $destImage);
    if ($rc != true) {
        COM_errorLog("_img_resizeImage: Unable to resize image - NetPBM failed.");
        //@unlink($destImage);
        return array(false,'Unable to resize image - NetPBM failed.');
    }

    if (($mimeType != 'image/gif') && ($_MG_CONF['jhead_enabled'] == 1)) {
        UTL_execWrapper('"' . $_MG_CONF['jhead_path'] . "/jhead" . '"' . " -v -te " . $srcImage . " " . $destImage);
    }

    return array(true, '');
}

/*
 * ImageMagick specific rotate function
 */
function _img_RotateImage($srcImage, $direction,$mimeType) {
    global $_CONF, $_MG_CONF;

    switch( $direction ) {
        case 'right' :
            $NP_rotate = "270";
            break;
        case 'left' :
            $NP_rotate = "90";
            break;
        default :
            COM_errorLog("_img_RotateImage: Invalid direction passed to rotate, must be left or right");
            return array(false,'Invalid direction passed to rotate, must be left or right');
    }

    $pamflip = 'pamflip';

    $tmpImage = $srcImage . '.rt';

    switch ( $mimeType ) {
        case 'image/jpeg' :
        case 'image/jpg' :
            $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . "jpegtopnm" . '" ' . $srcImage . " | " . '"' . $_CONF['path_to_netpbm'] . $pamflip . '" -r' . $NP_rotate . " > " . $srcImage . ".PNM");
            $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . "pnmtojpeg" . '"' . " -quality=100 " . $srcImage . ".PNM > " . $tmpImage);
            @unlink($srcImage . ".PNM");
            if ( $_MG_CONF['jhead_enabled'] == 1 ) {
                $rc = UTL_execWrapper('"' . $_MG_CONF['jhead_path'] . "/jhead" . '"' . " -te " . $srcImage . " " . $tmpImage);
            }
            $rc = copy($tmpImage, $srcImage);
            @unlink($tmpImage);
            break;
        case 'image/bmp' :
            $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . "bmptopnm" . '" ' . $srcImage . " | " . '"' . $_CONF['path_to_netpbm'] . $pamflip . '" -r' . $NP_rotate . " > " . $srcImage . ".PNM");
            $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . "pnmtobmp" . '"' . " " . $srcImage . ".PNM > " . $tmpImage);
            @unlink($srcImage . ".PNM");
            $rc = copy($tmpImage, $srcImage);
            @unlink($tmpImage);
            break;
        case 'image/gif' :
            $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . "giftopnm" . '" ' . $srcImage . " | " . '"' . $_CONF['path_to_netpbm'] . $pamflip . '" -r' . $NP_rotate . " > " . $srcImage . ".PNM");
            $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . "pnmtogif" . '"' . " " . $srcImage . ".PNM > " . $tmpImage);
            @unlink($srcImage . ".PNM");
            $rc = copy($tmpImage, $srcImage);
            @unlink($tmpImage);
            break;
        case 'image/png' :
            $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . "pngtopnm" . '" ' . $srcImage . " | " . '"' . $_CONF['path_to_netpbm'] . $pamflip . '" -r' . $NP_rotate . " > " . $srcImage . ".PNM");
            if ( $rc != true ) {
                COM_errorLog("_img_RotateImage: Error executing pngtopnm (NetPBM)");
                return array(false,'Error executing pngtopnm (NetPBM)');
            }
            $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . "pnmtopng" . '"' . " " . $srcImage . ".PNM > " . $tmpImage);
            @unlink($srcImage . ".PNM");
            $rc = copy($tmpImage, $srcImage);
            @unlink($tmpImage);
            break;
        default:
            COM_errorLog("_img_RotateImage: NetPBM only support JPG, BMP, GIF or PNG");
            return array(false,'NetPBM only supports JPG, BMP, GIF or PNG');
    }

    return array(true,'');
}

function _img_convertImageFormat($srcImage,$destImage,$destFormat,$mimeType) {
    global $_CONF, $_MG_CONF;

    if ( $_MG_CONF['verbose'] ) {
        COM_errorLog("_img_convertImageFormat: Converting using NetPBM src = " . $srcImage . " mimetype = " . $mimeType);
    }
    // determine which program to use, pamscale or pnmscale...
    if ( file_exists( $_CONF['path_to_netpbm'] . "pamscale" ) ) {
        $pamscale = 'pamscale';
    } else {
        $pamscale = 'pnmscale';
    }
    switch ( $mimeType ) {
        case 'image/jpeg' :
        case 'image/jpg' :
            $cvtFrom = 'jpegtopnm';
            break;
        case 'image/bmp' :
            $cvtFrom = 'bmptopnm';
            break;
        case 'image/gif' :
            $cvtFrom = 'giftopnm';
            break;
        case 'image/png' :
            $cvtFrom = 'pngtopnm';
            break;
        case 'image/tiff' :
            $cvtFrom = 'tifftopnm';
            break;
        case 'image/tga' :
        case 'image/targa-x' :
            $cvtFrom = 'tgatoppm';
            break;
        default :
            COM_errorLog("_img_convertImageFormat: NetPBM only supports JPG, BMP, GIF, PNG, TIFF, and TGA source images.");
            return array(false,'NetPBM only supports JPG, BMP, GIF, PNG, TIFF and TGA formats.');
    }
    switch ( $destFormat ) {
        case 'image/jpeg' :
        case 'image/jpg' :
            $cvtTo = 'pnmtojpeg';
            break;
        case 'image/bmp' :
            $cvtTo = 'pnmtobmp';
            break;
        case 'image/gif' :
            $cvtTo = 'pnmtogif';
            break;
        case 'image/png' :
            $cvtTo = 'pnmtopng';
            break;
        case 'image/tiff' :
            $cvtTo = 'pnmtotiff';
            break;
        default :
            COM_errorLog("_img_convertImageFormat: NetPBM only supports JPG, BMP, GIF, PNG, and TIFF destination formats.");
            return array(false,'NetPBM only supports JPG, BMP, GIF, PNG, TIFF and TGA formats.');
    }
    $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . $cvtFrom . '" ' . $srcImage . " | " . '"' . $_CONF['path_to_netpbm'] . $cvtTo . '" > ' . $destImage);
    if ( $rc == true ) {
        if ( $srcImage != $destImage) {
            @unlink($srcImage);
        }
    } else {
        COM_errorLog("_img_convertImageFormat: NetPBM returned an error converting image.");
        return array(false,'NetPBM returned an error when converting image.');
    }
    return array(true,'');
}

function _img_watermarkImage($origImage, $watermarkImage, $opacity, $location, $mimeType ) {
    global $_CONF, $_MG_CONF;

    if ( $_MG_CONF['verbose'] ) {
        COM_errorLog("_img_watermarkImage: Using NetPBM to watermark image.");
    }
    $errors = 0;
    $newSrc  = $origImage . '.tmp';
    $IntFile = $origImage . '.int';
    $origImagePNM = $origImage . '.pnm';
    $overlay = $watermarkImage . '.oly';
    $alpha   = $watermarkImage . '.alpa';
    $srcSize = @getimagesize($origImage);
    $overlaySize = @getimagesize($watermarkImage);

    $file_extension = strtolower(substr(strrchr($watermarkImage,"."),1));

    switch( $file_extension ) {
        case "png":
            $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . "pngtopnm" . '" ' . $watermarkImage . ' > ' . $overlay);
            if ( $rc != true ) {
                COM_errorLog("_img_watermarkImage: Unable to apply watermark, error executing pngtopnm (NetPBM) rc = " . $rc);
                return array(false,'Error executing pngtopnm (NetPBM)');
            }
            $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . "pngtopnm" . '" -alpha ' . $watermarkImage . ' > ' . $alpha);
            if ( $rc != 1 ) {
                COM_errorLog("_img_watermarkImage: Unable to apply watermark, error executing pngtopnm (alpha mask) (NetPBM)");
                return array(false,'Error executing pngtopnm (alpha mask) (NetPBM)');
            }
            break;
        case "jpg":
            $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . "jpegtopnm" . '" ' . $watermarkImage . '" > ' . $overlay);
            if ( $rc != 1 ) {
                COM_errorLog("_img_watermarkImage: Unable to apply watermark, error executing jpegtopnm (NetPBM)");
                return array(false,'Error executing jpegtopnm');
            }
            break;
        default :
            COM_errorLog("_img_watermarkImage: Unable to apply watermark, unrecognized filetype for watermark image (NetPBM)");
            return array(false,'Unrecognized file type for watermark image');
    }
    switch ( $mimeType ) {
        case 'image/jpeg' :
        case 'image/jpg' :
            $toPNM = 'jpegtopnm';
            $fromPNM = 'pnmtojpeg';
            break;
        case 'image/png' :
            $toPNM = 'pngtopnm';
            $fromPNM = 'pnmtopng';
            break;
        case 'image/gif' :
            $toPNM = 'giftopnm';
            $fromPNM = 'ppmtogif';
            break;
        case 'image/bmp' :
            $toPNM = 'bmptopnm';
            $fromPNM = 'ppmtobmp';
            break;
        case 'image/x-targa' :
        case 'image/tga' :
            COM_errorLog("_img_watermarkImage: TGA files not supported by NetPBM");
            return array(false,'TGA files not supported by NetPBM');
        default :
            COM_errorLog("_img_watermarkImage: NetPBM only support JPG, PNG,GIF, and BMP image types.");
            return array(false,'NetPBM only supports JPG, PNG, GIF, and BMP image formats');
    }
    $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . $toPNM . '" ' . $origImage . ' > ' . $origImagePNM);
    if ( $rc != 1 ) {
        COM_errorLog("_img_watermarkImage: Unable to apply watermark, error creating pnm image (NetPBM)");
        return array(false,'Error creating pnm image (NetPBM)');
    }

    switch ($location) {
        case 'topleft': // Top - Left
            $align = 'left';
            $valign = 'top';
            $wmAlignX = 0;
            $wmAlignY = 0;
            break;
        case 'topcenter' : // Top - centered...
            $align = 'center';
            $valign = 'top';
            $wmAlignX = ($srcSize[0] - $overlaySize[0]) / 2;
            $wmAlignY = 0;
            break;
        case 'topright': // Top - Right
            $align = 'right';
            $valign = 'center';
            $wmAlignX = ($srcSize[0] - $overlaySize[0]);
            $wmAlignY = 0;
            break;
        case 'leftmiddle': // Left
            $align = 'left';
            $valign = 'middle';
            $wmAlignX = 0;
            $wmAlignY = ($srcSize[1] - $overlaySize[1]) / 2;
            break;
        case 'center': // Center
            $align = 'center';
            $valign='middle';
            $wmAlignX = ($srcSize[0] - $overlaySize[0]) / 2;
            $wmAlignY = ($srcSize[1] - $overlaySize[1]) / 2;
            break;
        case 'rightmiddle': // Right
            $align = 'right';
            $valign = 'middle';
            $wmAlignX = ($srcSize[0] - $overlaySize[0]);
            $wmAlignY = ($srcSize[1] - $overlaySize[1]) / 2;
            break;
        case 'bottomleft': // Bottom - Left
            $align='left';
            $valign='bottom';
            $wmAlignX = 0;
            $wmAlignY = ($srcSize[1] - $overlaySize[1]);
            break;
        case 'bottomcenter': // Bottom
            $align='center';
            $valign='bottom';
            $wmAlignX = ($srcSize[0] - $overlaySize[0]) / 2;
            $wmAlignY = 0; // ($srcSize[1] - $overlaySize[1]);
            break;
        case 'bottomright': // Bottom Right
            $align='right';
            $valign='bottom';
            $wmAlignX = ($srcSize[0] - $overlaySize[0]);
            $wmAlignY = ($srcSize[1] - $overlaySize[1]);
            break;
        default:
            COM_errorLog("_img_watermarkImage: Unknown watermark location: " . $location);
            return array(false,'Invalid watermark position');
            break;
    }
    $args = "-align=" . $align . " -valign=" . $valign . " ";
    $args .= "-opacity=" . $opacity . ' ';
    if ($alpha) {
        $args .= "-alpha=$alpha ";
    }
    $args .= $overlay;
    $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . "pnmcomp" . '" '  . $args . ' ' . $origImagePNM . " > " . $IntFile);
    if ( $rc != 1 ) {
        COM_errorLog("_img_watermarkImage: Unable to apply watermark, error executing pamcomp (NetPBM)");
        return array(false,'Error executing pamcomp (NetPBM)');
    }
    $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . $fromPNM . '"  ' . $IntFile . ' > ' . $newSrc);
    if ( $rc != 1 ) {
        COM_errorLog("_img_watermarkImage: Unable to apply watermark, error executing " . $fromPNM . " (NetPBM)");
        return array(false,'Error executing ' . $fromPNM . ' (NetPBM)');
    }

    if ( $_MG_CONF['jhead_enabled'] == 1 && ($mimeType == 'image/jpeg' || $mimeType == 'image/jpg') ) {
        $rc = UTL_execWrapper('"' . $_MG_CONF['jhead_path'] . "/jhead" . '"' . " -te " . $origImage . " " . $newSrc);
    }
    @unlink($origImage);
    $rc = copy($newSrc, $origImage);
    @unlink($newSrc);
    @unlink($alpha);
    @unlink($overlay);
    @unlink($origImagePNM);
    @unlink($IntFile);
    COM_errorLog("_img_watermarkImage: Watermark successfully applied (NetPBM)");
    return array(true,'');
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

    if ($mimeType == 'image/x-targa' || $mimeType == 'image/tga') {
        COM_errorLog("_img_resizeImage_crop: TGA files not supported by NetPBM");
        return array(false,'TGA format not supported by NetPBM');
    }

    $ext = (PHP_OS == 'WINNT') ? '.exe' : '';

    if ($mimeType == 'image/gif' && !file_exists($_CONF['path_to_netpbm'] . 'pamtogif' . $ext)) {
        COM_errorLog("_img_resizeImage_crop: NetPBM installation does have have pamtogif binary.");
        return array(false,'NetPBM installation does have have pamtogif binary.');
    }

    if ($_MG_CONF['verbose']) {
        COM_errorLog("_img_resizeImage_crop: Resizing using NetPBM src = " . $srcImage . " mimetype = " . $mimeType);
    }

    // determine which program to use, pamscale or pnmscale...
    $pamscale = 'pnmscale';
    if (file_exists( $_CONF['path_to_netpbm'] . 'pamscale' . $ext)) {
        $pamscale = 'pamscale';
    }

    if (($dImageHeight > $sImageHeight) && ($dImageWidth > $sImageWidth)) {
        $dImageWidth  = $sImageWidth;
        $dImageHeight = $sImageHeight;
    }

    switch ($mimeType) {
        case 'image/jpeg' :
        case 'image/jpg' :
            $cmd1 = 'jpegtopnm';
            $cmd2 = 'pnmtojpeg';
            break;
        case 'image/bmp' :
            $cmd1 = 'bmptopnm';
            $cmd2 = 'ppmtobmp';
            break;
        case 'image/gif' :
            $cmd1 = 'giftopnm';
            $cmd2 = 'pamtogif';
            break;
        case 'image/png' :
            $cmd1 = 'pngtopnm'; // 'pngtopam'
            $cmd2 = 'pnmtopng';
            break;
        case 'image/tiff' :
            $cmd1 = 'tifftopnm';
            $cmd2 = 'pnmtotiff';
            break;
    }
    $rc = UTL_execWrapper('"' . $_CONF['path_to_netpbm'] . $cmd1 . '" ' . $srcImage . ' | '
                        . '"' . $_CONF['path_to_netpbm'] . "pamcut" . '"' . " $src_x $src_y $sImageWidth $sImageHeight" . ' | '
                        . '"' . $_CONF['path_to_netpbm'] . $pamscale . '"' . " -xsize=$dImageWidth -ysize=$dImageHeight" . ' | '
                        . '"' . $_CONF['path_to_netpbm'] . $cmd2 . '"' . " -quality=$JpegQuality > " . $destImage);
    if ($rc != true) {
        COM_errorLog("_img_resizeImage_crop: Unable to resize image - NetPBM failed.");
        //@unlink($destImage);
        return array(false,'Unable to resize image - NetPBM failed.');
    }

    if (($mimeType != 'image/gif') && ($_MG_CONF['jhead_enabled'] == 1)) {
        UTL_execWrapper('"' . $_MG_CONF['jhead_path'] . "/jhead" . '"' . " -v -te " . $srcImage . " " . $destImage);
    }

    return array(true, '');
}
?>