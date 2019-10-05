<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | lib-media.php                                                            |
// |                                                                          |
// | General purpose media display / manipulation interface                   |
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
require_once $_CONF['path'] . 'plugins/mediagallery/include/lib/imglib/lib-image.php';

function MG_getNextitem($num_items, $start_item, $is_next = TRUE)
{
    global $_MG_CONF;

    if ($num_items <= 1) return 0;
    if ($is_next) { // next item index
        if ($start_item < $num_items - 1) {
            return $start_item + 1;
        }
        return $_MG_CONF['enable_loop_pagination'] ? 0 : '';
    }
    // previous item index
    if ($start_item > 0) {
        return $start_item - 1;
    }
    return $_MG_CONF['enable_loop_pagination'] ? ($num_items - 1) : '';
}

/*
 * Generate the prev and next links for media browsing.
 */
function MG_getNextandPrev($base_url, $num_items, $start_item, &$media_array)
{
    if ($num_items <= 1) return array('', '');

    $prev_string = '';
    $next_string = '';
    $base_url .= strstr( $base_url, '?' ) ? "&amp;s=" : "?s=";

    $prev = MG_getNextitem($num_items, $start_item, FALSE);
    if ($prev !== '') $prev_string = $base_url . $media_array[$prev]['media_id'];

    $next = MG_getNextitem($num_items, $start_item, TRUE);
    if ($next !== '') $next_string = $base_url . $media_array[$next]['media_id'];

    return array($prev_string, $next_string);
}

function MG_displayASF($I, $opt=array())
{
    global $_TABLES, $_CONF, $_MG_CONF;

    // set the default playback options...
    $playback_options['autostart']         = $_MG_CONF['asf_autostart'];
    $playback_options['enablecontextmenu'] = $_MG_CONF['asf_enablecontextmenu'];
    $playback_options['stretchtofit']      = $_MG_CONF['asf_stretchtofit'];
    $playback_options['showstatusbar']     = $_MG_CONF['asf_showstatusbar'];
    $playback_options['uimode']            = $_MG_CONF['asf_uimode'];
    $playback_options['height']            = $_MG_CONF['asf_height'];
    $playback_options['width']             = $_MG_CONF['asf_width'];
    $playback_options['bgcolor']           = $_MG_CONF['asf_bgcolor'];
    $playback_options['playcount']         = $_MG_CONF['asf_playcount'];

    $sql = "SELECT * FROM {$_TABLES['mg_playback_options']} "
         . "WHERE media_id='" . DB_escapeString($I['media_id']) . "'";
    $poResult = DB_query($sql);
    while ($poRow = DB_fetchArray($poResult)) {
        $playback_options[$poRow['option_name']] = $poRow['option_value'];
    }

    $_MG_USERPREFS = MG_getUserPrefs();
    if (isset($_MG_USERPREFS['playback_mode']) && $_MG_USERPREFS['playback_mode'] != -1) {
        $playback_type = $_MG_USERPREFS['playback_mode'];
    } else {
        $playback_type = $opt['playback_type'];
    }

    $resolution_x = $I['media_resolution_x'];
    $resolution_y = $I['media_resolution_y'];
    if ($resolution_x == 0) {
        $filepath = Media::getFilePath('orig', $I['media_filename'], $I['media_mime_ext']);
        list($resolution_x, $resolution_y) = Media::getResolutionID3($filepath);
    }

    $raw_link_url = '';
    switch ($playback_type) {
        case 0: // Popup Window
            $win_width = $playback_options['width'] + 40;
            $win_height = $playback_options['height'] + 40;
            $u_pic = Media::getHref_showvideo($I['media_id'], $win_height, $win_width);
            $raw_link_url = $u_pic;
            list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
            break;
        case 1: // download
            $u_pic = $_MG_CONF['site_url'] . '/download.php?mid=' . $I['media_id'];
            $raw_link_url = $u_pic;
            list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
            break;
        case 2: // inline
            $V = COM_newTemplate(MG_getTemplatePath_byName($opt['skin']));
            $V->set_file('video', 'view_asf.thtml');
            $V->set_var(array(
                'autostart'          => ($playback_options['autostart'] ? 'true' : 'false'),
                'enablecontextmenu'  => ($playback_options['enablecontextmenu'] ? 'true' : 'false'),
                'stretchtofit'       => ($playback_options['stretchtofit'] ? 'true' : 'false'),
                'showstatusbar'      => ($playback_options['showstatusbar'] ? 'true' : 'false'),
                'uimode'             => $playback_options['uimode'],
                'playcount'          => $playback_options['playcount'],
                'height'             => $playback_options['height'],
                'width'              => $playback_options['width'],
                'bgcolor'            => $playback_options['bgcolor'],
                'movie'              => Media::getFileUrl('orig', $I['media_filename'], $I['media_mime_ext']),
                'autostart0'         => ($playback_options['autostart'] ? '1' : '0'),
                'enablecontextmenu0' => ($playback_options['enablecontextmenu'] ? '1' : '0'),
                'stretchtofit0'      => ($playback_options['stretchtofit'] ? '1' : '0'),
                'showstatusbar0'     => ($playback_options['showstatusbar'] ? '1' : '0'),
            ));
            switch ($playback_options['uimode']) {
                case 'mini' :
                case 'full' :
                    $V->set_var(array(
                        'showcontrols'  => 'true',
                        'showcontrols0' => '1',
                    ));
                    break;
                case 'none' :
                    $V->set_var(array(
                        'showcontrols'  => 'false',
                        'showcontrols0' => '0',
                    ));
                    break;
            }
            $u_image = $V->finish($V->parse('output','video'));
            return array($u_image, '', $resolution_x, $resolution_y, '');
            break;
        case 3: // use mms links
            $mms_path = preg_replace("/http/i", 'mms', $_MG_CONF['mediaobjects_url']);
            $u_pic = $mms_path . '/orig/'.  $I['media_filename'][0] . '/' . $I['media_filename'] . '.' . $I['media_mime_ext'];
            $raw_link_url = $u_pic;
            list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
            break;
    }

    $retval = MG_getFramedImage($opt['display_skin'], $I['media_title'],
                  $u_pic, $u_image, $media_size_disp[0], $media_size_disp[1]);

    return array($retval, $u_image, $media_size_disp[0], $media_size_disp[1], $raw_link_url);
}

function MG_displayMOV($I, $opt=array())
{
    global $_TABLES, $_CONF, $_MG_CONF, $LANG_MG03;

    // set the default playback options...
    $playback_options['autoref']    = $_MG_CONF['mov_autoref'];
    $playback_options['autoplay']   = $_MG_CONF['mov_autoplay'];
    $playback_options['controller'] = $_MG_CONF['mov_controller'];
    $playback_options['kioskmode']  = $_MG_CONF['mov_kioskmode'];
    $playback_options['scale']      = $_MG_CONF['mov_scale'];
    $playback_options['loop']       = $_MG_CONF['mov_loop'];
    $playback_options['height']     = $_MG_CONF['mov_height'];
    $playback_options['width']      = $_MG_CONF['mov_width'];
    $playback_options['bgcolor']    = $_MG_CONF['mov_bgcolor'];

    $sql = "SELECT * FROM {$_TABLES['mg_playback_options']} "
         . "WHERE media_id='" . DB_escapeString($I['media_id']) . "'";
    $poResult = DB_query($sql);
    while ($poRow = DB_fetchArray($poResult)) {
        $playback_options[$poRow['option_name']] = $poRow['option_value'];
    }

    $_MG_USERPREFS = MG_getUserPrefs();
    if (isset($_MG_USERPREFS['playback_mode']) && $_MG_USERPREFS['playback_mode'] != -1) {
        $playback_type = $_MG_USERPREFS['playback_mode'];
    } else {
        $playback_type = $opt['playback_type'];
    }

    $resolution_x = $I['resolution_x'];
    $resolution_y = $I['resolution_y'];
    if ($resolution_x == 0) {
        $resolution_x = $I['media_resolution_x'];
        $resolution_y = $I['media_resolution_y'];
        if ($resolution_x == 0) {
            $filepath = Media::getFilePath('orig', $I['media_filename'], $I['media_mime_ext']);
            list($resolution_x, $resolution_y) = Media::getResolutionID3($filepath);
        }
    }

    switch ($playback_type) {
        case 0: // Popup Window
            $win_width = $playback_options['width'] + 40;
            $win_height = $playback_options['height'] + 40;
            $u_pic = Media::getHref_showvideo($I['media_id'], $win_height, $win_width);
            list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
            break;
        case 1: // download
        case 3: // use mms links
            $u_pic = $_MG_CONF['site_url'] . '/download.php?mid=' . $I['media_id'];
            list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
            break;
        case 2: // inline
            $V = COM_newTemplate(MG_getTemplatePath_byName($opt['skin']));
            $V->set_file('video', 'view_quicktime.thtml');
            $V->set_var(array(
                'site_url'         => $_MG_CONF['site_url'],
                'autoref'          => ($playback_options['autoref'] ? 'true' : 'false'),
                'autoplay'         => ($playback_options['autoplay'] ? 'true' : 'false'),
                'controller'       => ($playback_options['controller'] ? 'true' : 'false'),
                'kioskmode'        => ($playback_options['kioskmode'] ? 'true' : 'false'),
                'loop'             => ($playback_options['loop'] ? 'true' : 'false'),
                'scale'            => $playback_options['scale'],
                'height'           => $playback_options['height'] + ($playback_options['controller'] ? 20 : 0),
                'width'            => $playback_options['width'],
                'bgcolor'          => $playback_options['bgcolor'],
                'movie'            => Media::getFileUrl('orig', $I['media_filename'], $I['media_mime_ext']),
                'filename'         => $I['media_original_filename'],
                'lang_noquicktime' => $LANG_MG03['no_quicktime'],
            ));
            $V->parse('output','video');
            $u_image = $V->finish($V->get_var('output'));
            return array($u_image, '', $resolution_x, $resolution_y, '');
            break;
    }

    $retval = MG_getFramedImage($opt['display_skin'], $I['media_title'],
                  $u_pic, $u_image, $media_size_disp[0], $media_size_disp[1]);

    return array($retval, $u_image, $media_size_disp[0], $media_size_disp[1], $u_pic);
}

function MG_displaySWF($I, $opt=array())
{
    global $_TABLES, $_CONF, $_MG_CONF, $LANG_MG03;

    // set the default playback options...
    $playback_options['play']        = $_MG_CONF['swf_play'];
    $playback_options['menu']        = $_MG_CONF['swf_menu'];
    $playback_options['quality']     = $_MG_CONF['swf_quality'];
    $playback_options['height']      = $_MG_CONF['swf_height'];
    $playback_options['width']       = $_MG_CONF['swf_width'];
    $playback_options['loop']        = $_MG_CONF['swf_loop'];
    $playback_options['scale']       = $_MG_CONF['swf_scale'];
    $playback_options['wmode']       = $_MG_CONF['swf_wmode'];
    $playback_options['allowscriptaccess'] = $_MG_CONF['swf_allowscriptaccess'];
    $playback_options['bgcolor']     = $_MG_CONF['swf_bgcolor'];
    $playback_options['swf_version'] = $_MG_CONF['swf_version'];
    $playback_options['flashvars']   = $_MG_CONF['swf_flashvars'];

    $poResult = DB_query("SELECT * FROM {$_TABLES['mg_playback_options']} WHERE media_id='" . DB_escapeString($I['media_id']) . "'");
    while ($poRow = DB_fetchArray($poResult)) {
        $playback_options[$poRow['option_name']] = $poRow['option_value'];
    }

    $_MG_USERPREFS = MG_getUserPrefs();
    if (isset($_MG_USERPREFS['playback_mode']) && $_MG_USERPREFS['playback_mode'] != -1) {
        $playback_type = $_MG_USERPREFS['playback_mode'];
    } else {
        $playback_type = $opt['playback_type'];
    }

    $resolution_x = $I['resolution_x'];
    $resolution_y = $I['resolution_y'];
    if ($resolution_x == 0) {
        $resolution_x = $I['media_resolution_x'];
        $resolution_y = $I['media_resolution_y'];
        if ($resolution_x == 0) {
            $filepath = Media::getFilePath('orig', $I['media_filename'], $I['media_mime_ext']);
            list($resolution_x, $resolution_y) = Media::getResolutionID3($filepath);
        }
    }

    switch ($playback_type) {
        case 0: // Popup Window
            $win_width  = $playback_options['width'] + 40;
            $win_height = $playback_options['height'] + 40;
            $u_pic = Media::getHref_showvideo($I['media_id'], $win_height, $win_width);
            list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
            break;
        case 1: // download
        case 3: // mms - not supported for flash
            $u_pic = $_MG_CONF['site_url'] . '/download.php?mid=' . $I['media_id'];
            list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
            break;
        case 2: // inline
            $u_image = '';
            $V = COM_newTemplate(MG_getTemplatePath_byName($opt['skin']));
            $V->set_file('video', 'view_swf.thtml');
            $V->set_var(array(
                'site_url'     => $_MG_CONF['site_url'],
                'lang_noflash' => $LANG_MG03['no_flash'],
                'play'         => ($playback_options['play'] ? 'true' : 'false'),
                'menu'         => ($playback_options['menu'] ? 'true' : 'false'),
                'loop'         => ($playback_options['loop'] ? 'true' : 'false'),
                'scale'        => $playback_options['scale'],
                'wmode'        => $playback_options['wmode'],
                'quality'      => $playback_options['quality'],
                'height'       => $playback_options['height'],
                'width'        => $playback_options['width'],
                'asa'          => $playback_options['allowscriptaccess'],
                'bgcolor'      => $playback_options['bgcolor'],
                'swf_version'  => $playback_options['swf_version'],
                'filename'     => $I['media_original_filename'],
                'id'           => 'swf' . rand(),
                'id2'          => 'swf2' . rand(),
                'movie'        => Media::getFileUrl('orig', $I['media_filename'], $I['media_mime_ext']),
            ));

            $flasharray = array();
            $flasharray = explode('&',$playback_options['flashvars']);

            $i = 0;
            $V->set_block('video','flashvars','flashvar');

            foreach ($flasharray as $var) {
                $temp = split("=", $var);
                $variable = $temp[0];
                $value = implode("=", array_slice($temp, 1));
                if (!isset($variable) && $variable != '') {
                    $V->set_var('fv', 'flashvars.' . $variable . '="' . $value . '";' .  LB);
                    $V->parse('flashvar', 'flashvars', true);
                    $i++;
                }
                $i++;
            }
            $u_image .= $V->finish($V->parse('output','video'));
            return array($u_image,'',$resolution_x,$resolution_y,'');
            break;
    }

    $retval = MG_getFramedImage($opt['display_skin'], $I['media_title'],
                  $u_pic, $u_image, $media_size_disp[0], $media_size_disp[1]);

    return array($retval, $u_image, $media_size_disp[0], $media_size_disp[1], $u_pic);
}

function MG_displayFLV($I, $opt=array())
{
    global $_TABLES, $_CONF, $_MG_CONF, $LANG_MG03;

    // set the default playback options...
    $playback_options['play']    = $_MG_CONF['swf_play'];
    $playback_options['menu']    = $_MG_CONF['swf_menu'];
    $playback_options['quality'] = $_MG_CONF['swf_quality'];
    $playback_options['height']  = $_MG_CONF['swf_height'];
    $playback_options['width']   = $_MG_CONF['swf_width'];
    $playback_options['loop']    = $_MG_CONF['swf_loop'];
    $playback_options['scale']   = $_MG_CONF['swf_scale'];
    $playback_options['wmode']   = $_MG_CONF['swf_wmode'];
    $playback_options['allowscriptaccess'] = $_MG_CONF['swf_allowscriptaccess'];
    $playback_options['bgcolor']     = $_MG_CONF['swf_bgcolor'];
    $playback_options['swf_version'] = $_MG_CONF['swf_version'];
    $playback_options['flashvars']   = $_MG_CONF['swf_flashvars'];

    $poResult = DB_query("SELECT * FROM {$_TABLES['mg_playback_options']} WHERE media_id='" . DB_escapeString($I['media_id']) . "'");
    while ($poRow = DB_fetchArray($poResult)) {
        $playback_options[$poRow['option_name']] = $poRow['option_value'];
    }

    $_MG_USERPREFS = MG_getUserPrefs();
    if (isset($_MG_USERPREFS['playback_mode']) && $_MG_USERPREFS['playback_mode'] != -1) {
        $playback_type = $_MG_USERPREFS['playback_mode'];
    } else {
        $playback_type = $opt['playback_type'];
    }

    $resolution_x = $I['resolution_x'];
    $resolution_y = $I['resolution_y'];
    if ($resolution_x == 0) {
        $resolution_x = 320; //$I['media_resolution_x'];
        $resolution_y = 240; //$I['media_resolution_y'];
        if ($I['media_resolution_x'] == 0 && $I['remote_media'] == 0) {
            $filepath = Media::getFilePath('orig', $I['media_filename'], $I['media_mime_ext']);
            list($resolution_x, $resolution_y) = Media::getResolutionID3($filepath);
        }
    }

    switch ($playback_type) {
        case 0: // Popup Window
            $resolution_x = $playback_options['width'];
            $resolution_y = $playback_options['height'];
            if ($resolution_x < 1 || $resolution_y < 1) {
                $resolution_x = 480;
                $resolution_y = 320;
            } else {
                $resolution_x = $resolution_x + 40;
                $resolution_y = $resolution_y + 40;
            }
            if ($I['mime_type'] == 'video/x-flv' && $_MG_CONF['use_flowplayer'] != 1) {
                $resolution_x = $resolution_x + 60;
                if ($resolution_x < 590) {
                    $resolution_x = 590;
                }
                $resolution_y = $resolution_y + 80;
                if ($resolution_y < 500) {
                    $resolution_y = 500;
                }
            }
            if ($I['media_type'] == 5) {
                $resolution_x = 460;
                $resolution_y = 380;
            }
            $u_pic = Media::getHref_showvideo($I['media_id'], $resolution_y, $resolution_x);
            list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
            break;
        case 1: // download
            $u_pic = $_MG_CONF['site_url'] . '/download.php?mid=' . $I['media_id'];
            $raw_link_url = $u_pic;
            list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
            break;
        case 3: // mms - not supported for flash
        case 2: // inline
            $u_image = '';
            // Initialize the view_flv.thtml template
            $V = COM_newTemplate(MG_getTemplatePath_byName($opt['skin']));
            $V->set_file('video', 'view_flv.thtml');

            // now the player specific items.
            $F = COM_newTemplate(MG_getTemplatePath_byName($opt['skin']));
            if ($_MG_CONF['use_flowplayer'] == 1) {    // FlowPlayer Setup
                $F->set_file('player', 'flvfp.thtml');
            } else {
                $F->set_file('player', 'flvmg.thtml');
            }

            if ($playback_options['play'] == 1) {  // auto start
                $playButton = '';
                $playButtonMG = '';
                $autoplay   = 'true';
            } else {
                if ($I['media_tn_attached'] == 1) {
                    $playImage = Media::getFileUrl ('tn', $I['media_filename'], 'jpg', 1);
                    $playButtonMG = 'flashvars.thumbUrl="' . $playImage . '";';
                } else {
                    $playImage = $_MG_CONF['site_url'] . MG_getImageFile('blank_blk.jpg');
                    $playButtonMG = '';
                }
                $playButton = "{ url: '" . $playImage . "', overlayId: 'play' },";
                $autoplay = 'false';
            }
            if ($I['remote_media'] == 1) {
                $urlParts = array();
                $urlParts = parse_url($I['remote_url']);

                $pathParts = array();
                $pathParts = explode('/',$urlParts['path']);

                $ppCount = count($pathParts);
                $pPath = '';
                for ($I=1; $I<$ppCount-1;$I++) {
                    $pPath .= '/' . $pathParts[$I];
                }
                $videoFile = $pathParts[$ppCount-1];

                $pos = strrpos($videoFile, '.');
                if($pos === false) {
                    $basefilename = $videoFile;
                } else {
                    $basefilename = substr($videoFile,0,$pos);
                }
                $videoFile            = $basefilename;
                $streamingServerURL   = "streamingServerURL: '" . $urlParts['scheme'] . '://' . $urlParts['host'] . $pPath . "',";
                $streamingServerURLmg = 'flashvars.streamingServerUrl="' . $urlParts['scheme'] . '://' . $urlParts['host'] . $pPath . '";';
                $streamingServer      = "streamingServer: 'fms',";
            } else {
                $videoFile            = urlencode(Media::getFileUrl('orig', $I['media_filename'], $I['media_mime_ext']));
                $streamingServerURL   = '';
                $streamingServerURLmg = '';
                $streamingServer      = '';
            }
            $width  = $playback_options['width'];
            $height = $playback_options['height'];
            if ($opt['allow_download'] == 1) {
                $allowDl = 'true';
            } else {
                $allowDl = 'false';
            }
            if ($I['media_title'] != '' && $I['media_title'] != ' ') {
                $title = urlencode($I['media_title']);
            } else {
                $title = urlencode($I['media_original_filename']);
            }

            if ($_MG_CONF['use_flowplayer'] == 1) {
                $resolution_x = $width;
                $resolution_y = $height;
            } else {
                $resolution_x = $resolution_x + 60;
                $resolution_y = $resolution_y + 190;
                if ($resolution_x < 565) {
                    $resolution_x = 565;
                }
            }
            $id  = 'id'  . rand();
            $id2 = 'idtwo' . rand();
            $F->set_var(array(
                'site_url'             => $_MG_CONF['site_url'],
                'lang_noflash'         => $LANG_MG03['no_flash'],
                'play'                 => $autoplay,
                'autoplay'             => $autoplay,
                'menu'                 => ($playback_options['menu'] ? 'true' : 'false'),
                'loop'                 => ($playback_options['loop'] ? 'true' : 'false'),
                'scale'                => $playback_options['scale'],
                'wmode'                => $playback_options['wmode'],
                'width'                => $width,
                'height'               => $height,
                'allowDl'              => $allowDl,
                'title'                => $title,
                'streamingServerURL'   => $streamingServerURL,
                'videoFile'            => $videoFile,
                'playButton'           => $playButton,
                'streamingServerURLmg' => $streamingServerURLmg,
                'playButtonMG'         => $playButtonMG,
                'id'                   => $id,
                'id2'                  => $id2,
                'lang_download'        => $LANG_MG03['download'],
                'lang_large'           => $LANG_MG03['large'],
                'lang_normal'          => $LANG_MG03['normal'],
                'resolution_x'         => $resolution_x,
                'resolution_y'         => $resolution_y,
            ));
            $flv_player = $F->finish($F->parse('output', 'player'));

            $V->set_var(array(
                'site_url'      => $_MG_CONF['site_url'],
                'lang_noflash'  => $LANG_MG03['no_flash'],
                'id'            => $id,
                'id2'           => $id2,
                'resolution_x'  => $resolution_x,
                'resolution_y'  => $resolution_y,
                'flv_player'    => $flv_player,
            ));
            $u_image .= $V->finish($V->parse('output','video'));
            return array($u_image, '', $resolution_x, $resolution_y, '');
            break;
    }

    $retval = MG_getFramedImage($opt['display_skin'], $I['media_title'],
                  $u_pic, $u_image, $media_size_disp[0], $media_size_disp[1]);

    return array($retval, $u_image, $media_size_disp[0], $media_size_disp[1], $u_pic);
}

function MG_displayMP3($I, $opt=array())
{
    global $_TABLES, $_CONF, $_MG_CONF, $LANG_MG03;

    // set the default playback options...
    $playback_options['autostart']         = $_MG_CONF['mp3_autostart'];
    $playback_options['enablecontextmenu'] = $_MG_CONF['mp3_enablecontextmenu'];
    $playback_options['showstatusbar']     = $_MG_CONF['mp3_showstatusbar'];
    $playback_options['uimode']            = $_MG_CONF['mp3_uimode'];
    $playback_options['loop']              = $_MG_CONF['mp3_loop'];

    $poResult = DB_query("SELECT * FROM {$_TABLES['mg_playback_options']} WHERE media_id='" . DB_escapeString($I['media_id']) . "'");
    while ($poRow = DB_fetchArray($poResult)) {
        $playback_options[$poRow['option_name']] = $poRow['option_value'];
        $playback_options[$poRow['option_name']. '_tf'] = ($poRow['option_value'] ? 'true' : 'false');
    }

    $_MG_USERPREFS = MG_getUserPrefs();
    if (isset($_MG_USERPREFS['playback_mode']) && $_MG_USERPREFS['playback_mode'] != -1) {
        $playback_type = $_MG_USERPREFS['playback_mode'];
    } else {
        $playback_type = $opt['playback_type'];
    }
    $u_tn = '';

    $_MG_USERPREFS['mp3_player'] = 2;
    $_MG_CONF['mp3_player'] = 2;

    switch ($playback_type) {
        case 0: // Popup Window
            $win_height = 320;
            $win_width = 600;
            $win_width = 550;
            $u_pic = Media::getHref_showvideo($I['media_id'], $win_height, $win_width);
            list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
            break;
        case 1: // download
            $u_pic = $_MG_CONF['site_url'] . '/download.php?mid=' . $I['media_id'];
            list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
            break;
        case 2: // inline
            $playback_options['width']  = 300;
            $playback_options['height'] = 50;
            $u_pic='';
            if ($I['media_tn_attached'] == 1) {
                $u_tn = Media::getFileUrl('tn', $I['media_filename'], 'jpg', 1);
                $media_size_disp = @getimagesize(Media::getFilePath('tn', $I['media_filename'], '', 1));
                $u_pic = '<img src="' . $u_tn . '"' . XHTML . '>';
            }
            $win_width  = $playback_options['width'];
            $win_height = $playback_options['height'];

            $filepath = Media::getFilePath('orig', $I['media_filename'], $I['media_mime_ext']);
            $FileInfo = Media::getID3($filepath);

            if (isset($FileInfo['tags']['id3v1']['title'][0])) {
                $mp3_title = str_replace(' ', '+', $FileInfo['tags']['id3v1']['title'][0]);
            } else {
                if (isset($FileInfo['tags']['id3v2']['title'][0])) {
                    $mp3_title = str_replace(' ', '+', $FileInfo['tags']['id3v2']['title'][0]);
                } else {
                    $mp3_title = str_replace(' ', '+', $I['media_original_filename']);
                }
            }
            if (isset($FileInfo['tags']['id3v1']['artist'])) {
                $mp3_artist = $FileInfo['tags']['id3v1']['artist'];
            } else {
                $mp3_artist = '';
            }

            $S = COM_newTemplate(MG_getTemplatePath_byName($opt['skin']));
            $S->set_file('swf', 'swfobject.thtml');
            $S->set_var('site_url', $_MG_CONF['site_url']);
            $u_image = $S->finish($S->parse('output', 'swf'));

            $V = COM_newTemplate(MG_getTemplatePath_byName($opt['skin']));
            $tfile = 'view_mp3_swf.thtml';
            if ($I['mime_type'] == 'audio/x-ms-wma') {
                $tfile = 'view_mp3_wmp.thtml';
            }
            $V->set_file('video', $tfile);
            $V->set_var(array(
                'u_pic'             => $u_pic,
                'u_tn'              => $u_tn,
                'autostart'         => ($playback_options['autostart'] ? 'true' : 'false'),
                'enablecontextmenu' => ($playback_options['enablecontextmenu'] ? 'true' : 'false'),
                'stretchtofit'      => isset($playback_options['stretchtofit']) ? ($playback_options['stretchtofit'] ? 'true' : 'false') : 'false',
                'showstatusbar'     => ($playback_options['showstatusbar'] ? 'true' : 'false'),
                'loop'              => ($playback_options['loop'] ? 'true' : 'false'),
                'playcount'         => ($playback_options['loop'] ? '9999' : '1'),
                'uimode'            => $playback_options['uimode'],
                'height'            => $playback_options['height'],
                'width'             => $playback_options['width'],
                'movie'             => Media::getFileUrl('orig', $I['media_filename'], $I['media_mime_ext']),
                'site_url'          => $_MG_CONF['site_url'],
                'mp3_title'         => $mp3_title,
                'mp3_artist'        => $mp3_artist,
                'allow_download'    => ($opt['allow_download'] ? 'true' : 'false'),
                'lang_artist'       => $LANG_MG03['artist'],
                'lang_album'        => $LANG_MG03['album'],
                'lang_song'         => $LANG_MG03['song'],
                'lang_track'        => $LANG_MG03['track'],
                'lang_genre'        => $LANG_MG03['genre'],
                'lang_year'         => $LANG_MG03['year'],
                'lang_download'     => $LANG_MG03['download'],
                'lang_info'         => $LANG_MG03['info'],
                'lang_noflash'      => $LANG_MG03['no_flash'],
                'swf_version'       => '9',
            ));
            $u_image .= $V->finish($V->parse('output', 'video'));
            return array($u_image, '', $win_width, $win_height, '');
            break;
        case 3: // use mms links
            $mms_path = preg_replace("/http/i", 'mms', $_MG_CONF['mediaobjects_url']);
            $u_pic = $mms_path . '/orig/'.  $I['media_filename'][0] . '/' . $I['media_filename'] . '.' . $I['media_mime_ext'];
            list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
            break;
    }

    $retval = MG_getFramedImage($opt['display_skin'], $I['media_title'],
                  $u_pic, $u_image, $media_size_disp[0], $media_size_disp[1]);

    return array($retval, $u_image, $media_size_disp[0], $media_size_disp[1], $u_pic);
}

function MG_displayGeneric($I, $opt=array())
{
    global $_TABLES, $_CONF, $_MG_CONF;

    $u_pic = $_MG_CONF['site_url'] . '/download.php?mid=' . $I['media_id'];
    list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
    $retval = MG_getFramedImage($opt['display_skin'], $I['media_title'],
                  $u_pic, $u_image, $media_size_disp[0], $media_size_disp[1]);

    return array($retval, $u_image, $media_size_disp[0], $media_size_disp[1], $u_pic);
}

function MG_displayTGA($I, $opt=array())
{
    global $_CONF, $_MG_CONF, $_USER;

    $media_link_start = '';
    $media_link_end   = '';

    $media_size_disp = @getimagesize(Media::getFilePath('disp', $I['media_filename'], 'jpg'));

    if ($opt['full_display'] == 2 || $_MG_CONF['discard_original'] == 1 || ($opt['full_display'] == 1 && $_USER['uid'] > 1)) {
        $u_pic = '#';
    } else {
        $u_pic = $_MG_CONF['site_url'] . '/download.php?mid=' . $I['media_id'];
        $media_link_start = '<a href="' . $u_pic . '">';
        $media_link_end   = '</a>';
    }
    $u_image = Media::getFileUrl('disp', $I['media_filename'], 'jpg');

    $retval = MG_getFramedImage($opt['display_skin'], $I['media_title'],
                  $u_pic, $u_image, $media_size_disp[0], $media_size_disp[1],
                  $media_link_start, $media_link_end);

    return array($retval, $u_image, $media_size_disp[0], $media_size_disp[1], $u_pic);
}

function MG_displayPSD($I, $opt=array())
{
    global $_CONF, $_MG_CONF, $_USER;

    $media_link_start = '';
    $media_link_end   = '';

    $media_size_disp = @getimagesize(Media::getFilePath('disp', $I['media_filename'], 'jpg'));

    if ($opt['full_display'] == 2 || $_MG_CONF['discard_original'] == 1 || ($opt['full_display'] == 1 && $_USER['uid'] > 1)) {
        $u_pic = '';
    } else {
        $u_pic = $_MG_CONF['site_url'] . '/download.php?mid=' . $I['media_id'];
        $media_link_start = '<a href="' . $u_pic . '">';
        $media_link_end   = '</a>';
    }
    $u_image = Media::getFileUrl('disp', $I['media_filename'], 'jpg');

    $retval = MG_getFramedImage($opt['display_skin'], $I['media_title'],
                  $u_pic, $u_image, $media_size_disp[0], $media_size_disp[1],
                  $media_link_start, $media_link_end);

    return array($retval, $u_image, $media_size_disp[0], $media_size_disp[1], $u_pic);
}

function MG_displayEmbed($I, $opt=array())
{
    global $_CONF, $_MG_CONF, $_USER;

    $playback_type = $opt['playback_type'];

    switch ($playback_type) {
        case 0: // Popup Window
            if ($I['media_type'] == 5) {
                $resolution_x = 460;
                $resolution_y = 380;
            }
            $u_pic = Media::getHref_showvideo($I['media_id'], $resolution_y, $resolution_x);
            list($u_image, $p_image, $media_size_orig) = Media::getThumbInfo($I);
            break;
        case 1: // download - not supported for embedded video
        case 3: // mms - not supported for embedded video
        case 2: // inline
            $F = COM_newTemplate(MG_getTemplatePath_byName($opt['skin']));
            $F->set_file('media_frame', 'view_embed.thtml');
            $F->set_var(array(
                'embed_string' => $I['remote_url'],
                'media_title'  => (isset($I['media_title']) && $I['media_title'] != ' ') ? PLG_replaceTags($I['media_title']) : '',
                'media_tag'    => (isset($I['media_title']) && $I['media_title'] != ' ') ? strip_tags($I['media_title']) : '',
            ));
            $F->parse('media', 'media_frame');
            $retval = $F->finish($F->get_var('media'));
            return array($retval, '', $resolution_x, $resolution_y, '');
    }

    $retval = MG_getFramedImage($opt['display_skin'], $I['media_title'],
                  $u_pic, $u_image, $media_size_orig[0], $media_size_orig[1]);

    return array($retval, $u_image, $media_size_orig[0], $media_size_orig[1], $u_pic);
}

function MG_displayJPG($I, $opt=array())
{
    global $_CONF, $_MG_CONF, $_USER;

    $full      = !empty($opt['full']) ? $opt['full'] : 0;
    $media_id  = $opt['media_id'];
    $sortOrder = $opt['sortOrder'];
    $spage     = $opt['spage'];

    $media_size_disp = false;
    if ($full == 1) {
        $u_image = Media::getFileUrl('orig', $I['media_filename'], $I['media_mime_ext']);
    } else {
        if ($I['remote_media'] == 1) {
            if ($I['media_resolution_x'] != 0 && $I['media_resolution_y'] != 0) {
                $media_size_disp[0] = $I['media_resolution_x'];
                $media_size_disp[1] = $I['media_resolution_y'];
            } else {
                $media_size_disp = @getimagesize($I['remote_url']);
            }
            $u_image = $I['remote_url'];
        } else {
            $u_image = Media::getFileUrl('disp', $I['media_filename'], $I['media_mime_ext']);
            $media_size_disp = @getimagesize(Media::getFilePath('disp', $I['media_filename'], $I['media_mime_ext']));
            if ($media_size_disp == false) {
                $u_image = Media::getFileUrl('disp', $I['media_filename'], 'jpg');
                $media_size_disp = @getimagesize(Media::getFilePath('disp', $I['media_filename'], 'jpg'));
                if ($media_size_disp == false) {
                    $fname = 'missing.png';
                    $u_image = $_MG_CONF['mediaobjects_url'] . '/' . $fname;
                    $p_image = $_MG_CONF['path_mediaobjects']      . $fname;
                    $media_size_disp = @getimagesize($pimage);
                }
            }
        }
    }

    if ($media_size_disp == false) {
        $media_size_disp[0] = 0;
        $media_size_disp[1] = 0;
    }

    $media_link_start = '';
    $media_link_end   = '';
    $media_size_orig = @getimagesize(Media::getFilePath('orig', $I['media_filename'], $I['media_mime_ext']));
    
    if ($media_size_orig == false ||
            $opt['full_display'] == 2 ||
            $_MG_CONF['discard_original'] == 1 ||
            ($opt['full_display'] == 1 && (!isset($_USER['uid']) || $_USER['uid'] < 2))) {
        $u_pic = '#';
        $raw_link_url = '';
        if ($media_size_orig == false) {
            $media_size_orig[0] = 200;
            $media_size_orig[1] = 150;
        }
    } else {
        if ($full == 0 && $_MG_CONF['full_in_popup']) {
            $popup_x = $media_size_orig[0] + 75;
            $popup_y = $media_size_orig[1] + 100;
            $u_pic = 'javascript:showVideo(\'' . $_MG_CONF['site_url'] . '/popup.php?s=' . $media_id
                   . '&amp;sort=' . $sortOrder . '\',' . $popup_y . ',' . $popup_x . ')';
        } else {
            $f = $full ? '0' : '1';
            if ($_MG_CONF['click_image_and_go_next']) {
                $f = $full ? '1' : '0';
            }
            $u_pic = $_MG_CONF['site_url'] . '/media.php?f=' . $f . '&amp;s=' . $media_id . '&amp;p=' . $spage;
        }
        $media_link_start = '<a href="' . $u_pic . '">';
        $media_link_end   = '</a>';
        $raw_link_url = $u_pic;
    }

    $imageWidth  = $full ? $media_size_orig[0] : $media_size_disp[0];
    $imageHeight = $full ? $media_size_orig[1] : $media_size_disp[1];

    $playback_type = $opt['playback_type'];

    switch ($playback_type) {
        case 0: // Popup Window
            $win_width = $imageWidth + 20;
            $win_height = $imageHeight + 20;
            $u_pic = Media::getHref_showvideo($I['media_id'], $win_height, $win_width);
            $raw_link_url = $u_pic;
            break;
    }

    $retval = MG_getFramedImage($opt['display_skin'], $I['media_title'],
                  $u_pic, $u_image, $imageWidth, $imageHeight, $media_link_start, $media_link_end);

    return array($retval, $u_image, $imageWidth, $imageHeight, $raw_link_url);
}

function MG_buildContent($media, &$opt)
{
    global $_MG_CONF;

    switch ($media['mime_type']) {
        case 'image/gif' :
        case 'image/jpeg' :
        case 'image/jpg' :
        case 'image/png' :
        case 'image/bmp' :
            $function = 'MG_displayJPG';
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
        case 'application/x-ms-wmz' :
        case 'application/x-ms-wmd' :
            $function = 'MG_displayASF';
            break;
        case 'audio/x-ms-wma' :
            $function = 'MG_displayMP3';
            break;
        case 'video/mpeg' :
        case 'video/x-mpeg' :
        case 'video/x-mpeq2a' :
            if ($_MG_CONF['use_wmp_mpeg'] == 1) {
                $function = 'MG_displayASF';
                break;
            }
        case 'video/x-motion-jpeg' :
        case 'video/quicktime' :
        case 'video/x-qtc' :
        case 'video/x-m4v' :
            $function = 'MG_displayMOV';
            if ($media['media_mime_ext'] == 'mp4' &&
                isset($_MG_CONF['play_mp4_flv']) && $_MG_CONF['play_mp4_flv'] == true) {
                $function = 'MG_displayFLV';
            }
            break;
        case 'embed' :
            $function = 'MG_displayEmbed';
            break;
        case 'application/x-shockwave-flash' :
            $function = 'MG_displaySWF';
            break;
        case 'video/x-flv' :
            $function = 'MG_displayFLV';
            break;
        case 'audio/mpeg' :
        case 'audio/x-mpeg' :
        case 'audio/mpeg3' :
        case 'audio/x-mpeg-3' :
            $function = 'MG_displayMP3';
            break;
        case 'application/ogg' :
        case 'application/x-ogg' :
            $function = 'MG_displayGeneric';
            break;
        case 'image/x-targa' :
        case 'image/tga' :
        case 'image/tiff' :
            $function = 'MG_displayTGA';
            break;
        case 'image/photoshop' :
        case 'image/x-photoshop' :
        case 'image/psd' :
        case 'application/photoshop' :
        case 'application/psd' :
            $function = 'MG_displayPSD';
            break;
        default :
            switch ($media['media_mime_ext']) {
                case 'jpg' :
                case 'gif' :
                case 'png' :
                case 'bmp' :
                    $function = 'MG_displayJPG';
                    break;
                case 'asf' :
                    $function = 'MG_displayASF';
                    break;
                default :
                    $function = 'MG_displayGeneric';
                    break;
            }
    }

    return $function($media, $opt);
}

function MG_displayMedia($id, $full=0, $sortOrder=0, $comments=0, $spage=0)
{
    global $_TABLES, $_CONF, $_MG_CONF, $LANG_MG00, $LANG_MG01, $LANG_MG03, $LANG_MG04,
           $LANG_ACCESS, $_USER;

    $retval = '';

    $aid = DB_getItem($_TABLES['mg_media_albums'], 'album_id', 'media_id="' . DB_escapeString($id) . '"');
    require_once $_CONF['path'].'plugins/mediagallery/include/classAlbum.php';
    $mg_album = new mgAlbum($aid);
    $root_album = new mgAlbum(0);

    $pid = 0;
    if (isset($mg_album->pid)) {
        $pid = $mg_album->pid;
    }
    $aOffset = -1;
    $aOffset = $mg_album->getOffset();
    if ($aOffset == -1 || $mg_album->access == 0) {
        $retval = COM_startBlock($LANG_ACCESS['accessdenied'], '', COM_getBlockTemplate('_msg_block', 'header'))
                 . '<br'.XHTML.'>' . $LANG_MG00['access_denied_msg']
                 . COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
        return array($LANG_MG00['access_denied_msg'], $retval);
    }

    $sql = MG_buildMediaSql(array(
        'album_id'  => $aid,
        'sortorder' => $mg_album->enable_sort ? $sortOrder : 0
    ));
    $result = DB_query($sql);
    $nRows = DB_numRows($result);

    $total_media = $nRows;
    $media_array = array();
    while ($row = DB_fetchArray($result)) {
        $media_array[] = $row;
        $id_array[] = $row['media_id'];
    }

    $key = array_search($id, $id_array);
    if ($key === false) {
        $retval = COM_startBlock($LANG_ACCESS['accessdenied'], '', COM_getBlockTemplate('_msg_block', 'header'))
                . '<br'.XHTML.'>' . $LANG_MG00['access_denied_msg']
                . COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
        return array($LANG_MG00['access_denied_msg'], $retval);
    }

    $media = $media_array[$key];

    if ($mg_album->full == 2 || $_MG_CONF['discard_original'] == 1 || ($mg_album->full == 1 && $_USER['uid'] > 1)) {
        $full = 0;
    }
    $disp = ($full) ? 'orig' : 'disp';

    if ($mg_album->enable_comments == 0) {
        $comments = 0;
    }

    $display_skin = $mg_album->display_skin;
    MG_getCSS($display_skin);

    // construct the album jumpbox...
    $album_jumpbox = '';
    if (!$_MG_CONF['hide_jumpbox_on_mediaview']) {
        $album_jumpbox = MG_buildAlbumJumpbox($root_album, $aid, 1, -1);
    }

    // Update the views count... But only for non-admins

    if (!$root_album->owner_id /*SEC_hasRights('mediagallery.admin')*/ ) {
        $media_views = $media['media_views'] + 1;
        DB_change($_TABLES['mg_media'], 'media_views', $media_views,
                  'media_id', DB_escapeString($media['media_id']));
    }

    $columns_per_page = ($mg_album->display_columns == 0) ? $_MG_CONF['ad_display_columns'] : $mg_album->display_columns;
    $rows_per_page    = ($mg_album->display_rows == 0)    ? $_MG_CONF['ad_display_rows']    : $mg_album->display_rows;

    $_MG_USERPREFS = MG_getUserPrefs();
    if (isset($_MG_USERPREFS['display_rows']) && $_MG_USERPREFS['display_rows'] > 0) {
        $rows_per_page = $_MG_USERPREFS['display_rows'];
    }
    if (isset($_MG_USERPREFS['display_columns']) && $_MG_USERPREFS['display_columns'] > 0) {
        $columns_per_page = $_MG_USERPREFS['display_columns'];
    }
    $media_per_page = $columns_per_page * $rows_per_page;

    if ($mg_album->albums_first) {
        $childCount = $mg_album->getChildCount();
        $page = intval(($key + $childCount) / $media_per_page) + 1;
    } else {
        $page = intval($key / $media_per_page) + 1;
    }

    /*
     * check to see if the original image exists, if not fall back to full image
     */

    $media_size_orig = @getimagesize(Media::getFilePath('orig', $media['media_filename'], $media['media_mime_ext']));

    if ($media_size_orig == false) {
        $full = 0;
        $disp = 'disp';
    }

    $aPage = intval($aOffset / ($root_album->display_columns * $root_album->display_rows)) + 1;

    $birdseed = MG_getBirdseed($mg_album->id, 1, $sortOrder, $aPage);

    $album_link = '<a href="' . $_MG_CONF['site_url'] . '/album.php?aid=' . $aid . '&amp;page=' . $page . '&amp;sort=' . $sortOrder . '">';

    if ($_MG_CONF['usage_tracking']) {
        MG_updateUsage('media_view', $mg_album->title, $media['media_title'], $media['media_id']);
    }

    // hack for tga files...
    if ($media['mime_type'] == 'image/x-targa' || $media['mime_type'] == 'image/tga') {
        $full = 0;
        $disp = 'disp';
    }

    $prevLink = '';
    $nextLink = '';
    $pagination = '';
    $base_url = $_MG_CONF['site_url'] . "/media.php?f=" . ($full ? '1' : '0') . "&amp;sort=" . $sortOrder;

    list($prevLink, $nextLink) = MG_getNextandPrev($base_url, $nRows, $key, $media_array);
    // generate pagination routine
    if (!empty($prevLink)) {
        $pagination .= '<a href="' . $prevLink  . '">' . $LANG_MG03['previous'] . '</a>';
    }
    if (!empty($nextLink)) {
        $pagination .= (!empty($prevLink)) ? '&nbsp;&nbsp;&nbsp;' : '';
        $pagination .= '<a href="' . $nextLink  . '">' . $LANG_MG03['next'] . '</a>';
    }
    $pagination .= LB;

    // hack for testing...>>>
    $media_id = $media['media_id'];
    if ($_MG_CONF['click_image_and_go_next'] && !$_MG_CONF['full_in_popup']) {
        $nextkey = MG_getNextitem($nRows, $key);
        if ($nextkey !== '') {
            $media_id = $media_array[$nextkey]['media_id'];
        }
    }
    $vf = $full;

    if ($media['media_type'] == '0') { // image
        $switch_size = $_MG_CONF['site_url'] . "/media.php?f=" . ($full ? '0' : '1')
                     . '&amp;sort=' . $sortOrder
                     . '&amp;s=' . $media['media_id'];
        $lang_switch_size = $full ? $LANG_MG03['normal_size'] : $LANG_MG03['full_size'];
        $switch_viewsize_link = '<a href="' . $switch_size . '">' . $lang_switch_size . '</a>';
    }
    // hack for testing...<<<

    $opt = array(
        'full'           => $full,
        'media_id'       => $media_id,
        'sortOrder'      => $sortOrder,
        'spage'          => $spage,
        'playback_type'  => $mg_album->playback_type,
        'skin'           => $mg_album->skin,
        'display_skin'   => $mg_album->display_skin,
        'allow_download' => $mg_album->allow_download,
        'full_display'   => $mg_album->full,
    );

    list($u_image, $raw_image, $raw_image_width, $raw_image_height, $raw_link_url)
        = MG_buildContent($media, $opt);

    $mid = $media['media_id'];

    if ($_MG_CONF['use_upload_time'] == 1) {
        $media_date = MG_getUserDateTimeFormat($media['upload_time']);
    } else {
        $media_date = MG_getUserDateTimeFormat($media['media_time']);
    }

    $rating_box = '';
    if ($mg_album->enable_rating > 0) {
        require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-rating.php';
        $rating_box = MG_getRatingBar(
            $mg_album->enable_rating,
            $media['media_user_id'],
            $media['media_id'],
            $media['media_votes'],
            $media['media_rating'],
            ''
        );
    }

    $download_link = '';
    $download = '';
    if ($mg_album->allow_download) {
        $download_link = $_MG_CONF['site_url'] . '/download.php?mid=' . $media['media_id'];
        $download = '<a href="' . $download_link . '">' . $LANG_MG01['download'] . '</a>';
    }

    $edit_item_link = '';
    $edit_item = '';
    if ($mg_album->access == 3 || ($_MG_CONF['allow_user_edit'] == true && isset($_USER['uid']) && $media['media_user_id'] == $_USER['uid'])) {
        $edit_item_link = $_MG_CONF['site_url'] . '/admin.php?mode=mediaedit&amp;s=1&amp;album_id=' . $aid . '&amp;mid=' . $mid;
        $edit_item = '<a href="' . $edit_item_link . '">' . $LANG_MG01['edit'] . '</a>';
    }

    $media_desc = PLG_replaceTags(nl2br($media['media_desc']));
    if (strlen($media_desc) > 0) {
        $media_desc = '<p style="margin:5px">'.$media_desc.'</p>';
    }

    $getid3link = '';
    $getid3linkend = '';
    $media_properties = ($getid3link != '') ? $LANG_MG03['media_properties'] : '';

    $kwText = '';
    $lang_keywords = '';
    if ($mg_album->enable_keywords == 1 && !empty($media['media_keywords'])) {
        $lang_keywords = $LANG_MG01['keywords'];
        $keyWords = array();
        $keyWords = explode(' ', $media['media_keywords']);
        $numKeyWords = count($keyWords);
        for ($i=0; $i<$numKeyWords; $i++) {
            $keyWords[$i] = str_replace('"', ' ', $keyWords[$i]);
            $searchKeyword = $keyWords[$i];
            $keyWords[$i] = str_replace('_', ' ', $keyWords[$i]);
            $kwText .= '<a href="' . $_MG_CONF['site_url'] . '/search.php?mode=search&amp;swhere=1&amp;keywords=' . $searchKeyword . '&amp;keyType=any">' . $keyWords[$i] . '</a>';
        }
    }

    $media_user_id = $media['media_user_id'];
    if (empty($media_user_id)) $media_user_id = 0;
    $displayname = ($_CONF['show_fullname']) ? 'fullname' : 'username';
    $owner_name = DB_getItem($_TABLES['users'], $displayname, "uid = $media_user_id");
    if (empty($owner_name)) {
        $owner_name = DB_getItem($_TABLES['users'],'username', "uid = $media_user_id");
        if (empty($owner_name)) $owner_name = 'unknown';
    }
    $owner_link = $owner_name;
    if ($owner_name != 'unknown') {
        $owner_link = '<a href="' . $_CONF['site_url'] . '/users.php?mode=profile&amp;uid='
                    . $media_user_id . '">' . $owner_name . '</a>';
    }

    $property = '';
    if (($mg_album->exif_display == 2 || $mg_album->exif_display == 3) && ($media['media_type'] == 0)) {
        require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-exif.php';
        $haveEXIF = MG_haveEXIF($media['media_id']);
        if ($haveEXIF) {
            $property = $_MG_CONF['site_url'] . '/property.php?mid=' . $media['media_id'];
        }
    }

    $media_id = '';
    if ($root_album->owner_id || $_MG_CONF['enable_media_id'] == 1) {
        $media_id = $media['media_id'];
    }

    $exif_info = '';
    if (($mg_album->exif_display == 1 || $mg_album->exif_display == 3) && ($media['media_type'] == 0)) {
        require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-exif.php';
        $haveEXIF = MG_haveEXIF($media['media_id']);
        if ($haveEXIF) {
            $exif_info = MG_readEXIF($media['media_id'], 2);
        }
    }

    $T = COM_newTemplate(MG_getTemplatePath_byName($mg_album->skin));
    switch ($media['media_type']) {
        case '0': // image
            $T->set_file('page', 'view_image.thtml');
            break;
        case '1': // video
        case '5': // embedded video
            $T->set_file('page', 'view_video.thtml');
            break;
        case '2': // audio
            $T->set_file('page', 'view_audio.thtml');
            break;
        default:
            $T->set_file('page', 'view_image.thtml');
            break;
    }
    $T->set_var(array(
        'header'              => $LANG_MG00['plugin'],
        'site_url'            => $_MG_CONF['site_url'],
        'plugin'              => 'mediagallery',
        'birdseed'            => $birdseed,
        'lang_slideshow_link' => $LANG_MG03['slide_show'],
        'image_detail'        => $u_image,
        'media_title'         => (isset($media['media_title']) && $media['media_title'] != ' ') ? PLG_replaceTags($media['media_title']) : '',
        'album_title'         => $mg_album->title,
        'media_desc'          => (isset($media['media_desc']) && $media['media_desc'] != ' ') ? $media_desc : '',
        'media_time'          => $media_date[0],
        'media_views'         => ($mg_album->enable_views ? $media['media_views'] : ''),
        'media_comments'      => ($mg_album->enable_comments ? $media['media_comments'] : ''),
        'pagination'          => $pagination,
        'media_number'        => sprintf("%s %d %s %d", $LANG_MG03['image'], $key + 1 , $LANG_MG03['of'], $total_media ),
        'jumpbox'             => $album_jumpbox,
        'rating_box'          => $rating_box,
        'download'            => $download,
        'download_link'       => $download_link,
        'lang_download'       => $LANG_MG01['download'],
        'edit_item'           => $edit_item,
        'edit_item_link'      => $edit_item_link,
        'lang_edit'           => $LANG_MG01['edit'],
        'lang_prev'           => $LANG_MG03['previous'],
        'lang_next'           => $LANG_MG03['next'],
        'next_link'           => $nextLink,
        'prev_link'           => $prevLink,
        'image_height'        => $raw_image_height,
        'image_width'         => $raw_image_width,
        'left_side'           => intval($raw_image_width / 2) - 1,
        'right_side'          => intval($raw_image_width / 2),
        'raw_image'           => $raw_image,
        'raw_link_url'        => $raw_link_url,
        'item_number'         => $key + 1,
        'total_items'         => $total_media,
        'lang_of'             => $LANG_MG03['of'],
        'album_link'          => $album_link,
        'switch_size'         => $switch_size,
        'lang_switch_size'    => $lang_switch_size,
        'switch_size_link'    => $switch_viewsize_link,
        'getid3'              => $getid3link,
        'getid3end'           => $getid3linkend,
        'media_properties'    => $media_properties,
        'media_keywords'      => $kwText,
        'lang_keywords'       => $lang_keywords,
        'owner_username'      => $owner_link,
        'property'            => $property,
        'lang_property'       => $LANG_MG04['exif_header'],
        'media_id'            => $media_id,
        'exif_info'           => $exif_info,
        'lang_comments'       => ($mg_album->enable_comments ? $LANG_MG03['comments'] : ''),
        'lang_views'          => ($mg_album->enable_views ? $LANG_MG03['views'] : ''),
        'lang_title'          => $LANG_MG01['title'],
        'lang_uploaded_by'    => $LANG_MG01['uploaded_by'],
        'album_id'            => $aid,
        'lang_search'         => $LANG_MG01['search'],
    ));
    MG_buildSlideshow($mg_album, $T, $sortOrder);

    PLG_templateSetVars('mediagallery', $T);

    $retval .= $T->finish($T->parse('output', 'page'));

    if ($comments) {
        // Geeklog Comment support
        $sid = $media['media_id'];
        require_once $_CONF['path_system'] . 'lib-comment.php';
        $delete_option = false;
        if ($mg_album->access == 3 || $root_album->owner_id) {
            $delete_option = true;
        }
        $page = isset($_GET['page']) ? COM_applyFilter($_GET['page'], true) : 0;
        $comorder = '';
        if (isset($_POST['order'])) {
            $comorder = COM_applyFilter($_POST['order']);
        } elseif (isset($_GET['order'])) {
            $comorder = COM_applyFilter($_GET['order']);
        }
        $commode = '';
        if (isset($_POST['mode'])) {
            $commode = COM_applyFilter($_POST['mode']);
        } elseif (isset($_GET['mode'])) {
            $commode = COM_applyFilter($_GET['mode']);
        }
        $commentcode = 0; // ¡‚Ì‚Æ‚±‚ë–³ðŒ‚ÉƒRƒƒ“ƒg“Še‚ð‹–‰ÂB
        $retval .= CMT_userComments($sid, $media['media_title'], 'mediagallery',
                       $comorder, $commode, 0, $page, false, $delete_option, $commentcode);
    }

    return array(strip_tags($media['media_title']), $retval, $aid);
}


function MG_rotateMedia($album_id, $media_id, $direction, $actionURL='')
{
    global $_TABLES, $_MG_CONF;

    $sql = "SELECT media_filename,media_mime_ext FROM {$_TABLES['mg_media']} "
         . "WHERE media_id='" . DB_escapeString($media_id) . "'";
    $result = DB_query($sql);
    list($filename, $mime_ext) = DB_fetchArray($result);
    if (DB_error() != 0) {
        COM_errorLog("MG_rotateMedia: Unable to retrieve media object data");
        if ($actionURL == '') {
            return false;
        }
        COM_redirect($actionURL);
    }

    $orig = Media::getFilePath('orig', $filename, $mime_ext);
    if (file_exists($orig)) {
        list($rc, $msg) = MG_rotateImage($orig, $direction);
    }
    $disp = Media::getFilePath('disp', $filename);
    if (file_exists($disp)) {
        list($rc, $msg) = MG_rotateImage($disp, $direction);
    }
    $ext = pathinfo($disp, PATHINFO_EXTENSION);
    $tn = Media::getFilePath('tn', $filename, $ext);
    if (file_exists($tn)) {
        list($rc, $msg) = MG_rotateImage($tn, $direction);
    }
    $types = array('0','1','2','3','10','11','12','13');
    foreach ($types as $t) {
        $fpath = Media::getThumbPath($tn, $t);
        if (file_exists($fpath)) {
            list($rc, $msg) = MG_rotateImage($fpath, $direction);
        }
    }

    if ($actionURL == -1 || $actionURL == '') return true;

    COM_redirect($actionURL . '&t=' . time());
}


function MG_deleteMedia($media_id)
{
    global $_TABLES;

    $sql = "SELECT media_filename, media_mime_ext FROM {$_TABLES['mg_media']} "
         . "WHERE media_id='" . DB_escapeString($media_id) . "'";
    $result = DB_query($sql);
    while (list($filename, $mime_ext) = DB_fetchArray($result)) {
        $orig = Media::getFilePath('orig', $filename, $mime_ext);
        @unlink($orig);
        $disp = Media::getFilePath('disp', $filename);
        @unlink($disp);
        $ext = pathinfo($disp, PATHINFO_EXTENSION);
        $tn = Media::getFilePath('tn', $filename, $ext);
        @unlink($tn);
        $types = array('0','1','2','3','10','11','12','13');
        foreach ($types as $t) {
            $fpath = Media::getThumbPath($tn, $t);
            @unlink($fpath);
        }
        DB_delete($_TABLES['mg_media_albums'], 'media_id', DB_escapeString($media_id));
        DB_delete($_TABLES['mg_media'], 'media_id', DB_escapeString($media_id));
        DB_delete($_TABLES['comments'], 'sid', DB_escapeString($media_id));
        DB_delete($_TABLES['mg_playback_options'], 'media_id', DB_escapeString($media_id));
        PLG_itemDeleted($media_id, 'mediagallery');
    }
}

function MG_updateQuotaUsage($album_id)
{
    global $_CONF, $_TABLES;

    require_once $_CONF['path'] . 'plugins/mediagallery/include/classMedia.php';

    // update the disk usage...
    $quota = 0;
    $sql = MG_buildMediaSql(array(
        'album_id'  => $album_id,
        'fields'    => array('media_filename', 'media_mime_ext'),
        'sortorder' => -1,
    ));
    $result = DB_query($sql);
    $nRows = DB_numRows($result);
    while (list($filename, $mime_ext) = DB_fetchArray($result)) {
        $orig = Media::getFilePath('orig', $filename, $mime_ext);
        if (file_exists($orig)) {
            $quota += @filesize($orig);
        }
        $disp = Media::getFilePath('disp', $filename);
        if (file_exists($disp)) {
            $quota += @filesize($disp);
        }
        $ext = pathinfo($disp, PATHINFO_EXTENSION);
        $tn = Media::getFilePath('tn', $filename, $ext);
        if (file_exists($tn)) {
            $quota += @filesize($tn);
        }
        $types = array('0','1','2','3','10','11','12','13');
        foreach ($types as $t) {
            $fpath = Media::getThumbPath($tn, $t);
            if (file_exists($fpath)) {
                $quota += @filesize($fpath);
            }
        }
    }
    if ($nRows > 0) {
        DB_change($_TABLES['mg_albums'], 'album_disk_usage', $quota, 'album_id', intval($album_id));
    }
}

?>