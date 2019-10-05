<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | autotags.php                                                             |
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

function MG_helper_getContainer($media, $align, $container)
{
    $style = '';
    if ($align == 'center') {
        $style = 'text-align:center;';
    } else if ($align != '') {
        $style = 'float:' . $align . ';';
    }
    $retval = '<' . $container . ' class="MG_autotag_media" style="' . $style . '">' . $media . '</' . $container . '>';

    return $retval;
}


function MG_autotags($op, $content = '', $autotag = '')
{
    global $_MG_CONF, $_CONF, $_TABLES, $LANG_MG00, $LANG_MG03, $side_count, $swfjsinclude;

    static $ss_count = 0;

    /*
     * Process the auto tag to remove any embedded &nbsp;
     */
    $tag = str_replace('&nbsp;', ' ', $autotag['tagstr']);
    $parms = explode(' ', $tag);
    // Extra test to see if autotag was entered with a space
    // after the module name
    if (substr($parms[0], -1) == ':') {
        $startpos = strlen ($parms[0]) + strlen ($parms[1]) + 2;
        $label = str_replace (']', '', substr($tag, $startpos));
        $tagid = $parms[1];
    } else {
        $label = str_replace (']', '',
                 substr($tag, strlen ($parms[0]) + 1));
        $parms = explode(':', $parms[0]);
        if (count ($parms) > 2) {
            // whoops, there was a ':' in the tag id ...
            array_shift($parms);
            $tagid = implode (':', $parms);
        } else {
            $tagid = $parms[1];
        }
    }
    $autotag['parm1'] = str_replace(']', '', $tagid);
    $autotag['parm2'] = $label;
    /*
     * end of tag replacement
     */
    // see if we have an alignment option included
    $caption = $autotag['parm2'];
    $aSet = 0;
    $skip = 0;

    // default values for parameters
    $border         = $_MG_CONF['at_border'];
    $align          = $_MG_CONF['at_align'];
    $width          = $_MG_CONF['at_width'];
    $height         = $_MG_CONF['at_height'];
    $src            = $_MG_CONF['at_src'];
    $autoplay       = $_MG_CONF['at_autoplay'];
    $enable_link    = $_MG_CONF['at_enable_link'];
    $delay          = $_MG_CONF['at_delay'];
    $transition     = 'Fade';
    $showtitle      = $_MG_CONF['at_showtitle'];
    $destination    = 'content';
    $target         = '';
    $linkID         = 0;
    $alt            = 0;
    $link_src       = 'disp';

    if ( $align != '' ) {
        $aSet = 1;
    }

    // parameter processing - logic borrowed from
    // Dirk Haun's Flickr plugin

    $px = explode(' ', trim ($autotag['parm2']));
    if (is_array ($px)) {
        foreach ($px as $part) {
            if (substr($part, 0, 6) == 'width:') {
                $a = explode(':', $part);
                $width = $a[1];
                $skip++;
            } elseif (substr($part, 0, 7) == 'height:') {
                $a = explode(':', $part);
                $height = $a[1];
                $skip++;
            } elseif (substr($part, 0, 7) == 'border:') {
                $a = explode(':', $part);
                $border = $a[1];
                $skip++;
            } elseif (substr($part,0, 6) == 'align:') {
                $a = explode(':', $part);
                $align = $a[1];
                $skip++;
                $aSet = 1;
            } elseif (substr($part,0,4) == 'src:') {
                $a = explode(':', $part);
                $src = $a[1];
                $skip++;
            } elseif (substr($part,0,9) == 'autoplay:') {
                $a = explode(':', $part);
                $autoplay = $a[1];
                $skip++;
            } elseif (substr($part,0,5) == 'link:') {
                $a = explode(':',$part);
                $enable_link = $a[1];
                $skip++;
            } elseif (substr($part, 0, 6) == 'delay:') {
                $a = explode(':', $part);
                $delay = $a[1];
                $skip++;
            } elseif (substr($part, 0, 11) == 'transition:') {
                $a = explode(':', $part);
                $transition = $a[1];
                $skip++;
            } elseif (substr($part,0, 6) == 'title:') {
                $a = explode(':',$part);
                $showtitle = $a[1];
                $skip++;
            } elseif (substr($part, 0, 5) == 'dest:') {
                $a = explode(':', $part);
                $destination = $a[1];
                if ($destination != 'content' && $destination != 'block') {
                    $destination = 'content';
                }
                $skip++;
            } elseif (substr($part,0,7) == 'linkid:') {
                $a = explode(':',$part);
                $linkID = $a[1];
                $skip++;
            } elseif (substr($part,0,4) == 'alt:') {
                $a = explode(':',$part);
                $alt = $a[1];
                $skip++;
            } elseif (substr($part,0,7) == 'target:') {
                $a = explode(':',$part);
                $target = $a[1];
                $skip++;
            } elseif (substr($part,0,5) == 'type:') {
                $a = explode(':',$part);
                $mp3_type = $a[1];
                $skip++;
            } elseif (substr($part,0,8) == 'linksrc:') {
                $a = explode(':',$part);
                $link_src = $a[1];
                if (!in_array($link_src, array('tn', 'disp', 'orig'))) {
                    $link_src = 'disp';
                }
                $skip++;
            } else {
                break;
            }
        }

        if ($skip != 0) {
            $caption = '';
            if (count ($px) > $skip) {
                for ($i = 0; $i < $skip; $i++) {
                    array_shift($px);
                }
                $caption = trim (implode (' ', $px));
            }
        }
    } else {
        $caption = trim ($autotag['parm2']);
    }

    if (!is_numeric($autotag['parm1'][0])) {
        switch ($autotag['parm1'][0]) {
            case 'n' :
                $align = '';
                $aSet = 1;
                break;
            case 'c' :
                $align='center';
                $aSet = 1;
                break;
            case 'l' :
                $align = 'left';
                $aSet = 1;
                break;
            case 'r' :
                $align = 'right';
                $aSet = 1;
                break;
            case 'a' :
                $align = (!($side_count % 2) ? 'left' : 'right');
                $side_count++;
                $aSet = 1;
                break;
            default :
                $align = 'left';
                $side_count++;
                break;
        }
        $parm1 = COM_applyFilter(substr($autotag['parm1'], 1, strlen($autotag['parm1']) - 1));
    } else {
        $parm1 = COM_applyFilter($autotag['parm1']);
        if ($aSet == 0 || $align == 'auto') {
            $align=(!($side_count % 2) ? 'left' : 'right');
            $side_count++;
        }
    }
    if ($align == 'none') {
        $align = '';
    }
    // sanity check incase the album has been deleted or something...
    if (!in_array($autotag['tag'], array('media','image','video','audio','download','oimage','img','mlink','alink','playall'))) {
        if (DB_count($_TABLES['mg_albums'], 'album_id', intval($parm1)) == 0)
            return str_replace($autotag['tagstr'], '', $content);
    }
    $ss_count = mt_rand(0,32768);
    switch ($autotag['tag']) {
        case 'download' :
            $side_count--;
            $sql = "SELECT ma.album_id "
                 . "FROM {$_TABLES['mg_media']} AS m "
                 . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON m.media_id=ma.media_id "
                 . "WHERE m.media_id='" . DB_escapeString($parm1) . "'";
            $result = DB_query($sql);
            if (DB_numRows($result) <= 0) return str_replace($autotag['tagstr'], '', $content);
            $row = DB_fetchArray($result);
            $album_data = MG_getAlbumData($row['album_id'], array('album_id'), true);
            if (!isset($album_data['album_id']) || $album_data['access'] == 0) {
                return str_replace($autotag['tagstr'], '', $content);
            }
            $link = '<a href="' . $_MG_CONF['site_url'] . '/download.php?mid=' . $parm1 . '">'
                  . (($caption != '') ? $caption : 'download') . '</a>';
            break;

        case 'mlink' :
            $side_count--;
            $sql = "SELECT m.remote_url,ma.album_id "
                 . "FROM {$_TABLES['mg_media']} AS m "
                 . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON m.media_id=ma.media_id "
                 . "WHERE m.media_id='" . DB_escapeString($parm1) . "'";
            $result = DB_query($sql);
            if (DB_numRows($result) <= 0) return str_replace($autotag['tagstr'], '', $content);
            $row = DB_fetchArray($result);
            $album_data = MG_getAlbumData($row['album_id'], array('album_id'), true);
            if (!isset($album_data['album_id']) || $album_data['access'] == 0) {
                return str_replace($autotag['tagstr'], '', $content);
            }
            if ($alt == 1 && $row['remote_url'] != '') {
                $href = $row['remote_url'];
            } else {
                $href = $_MG_CONF['site_url'] . '/media.php?f=0&amp;sort=0&amp;s=' . $parm1;
            }
            $link = '<a href="' . $href . '"' . ($target=='' ? '' : ' target="' . $target . '"') . '>'
                  . (($caption != '') ? $caption : $LANG_MG03['click_here']) . '</a>';
            break;

        case 'playall' :
            $album_data = MG_getAlbumData($parm1, array('album_id'), true);
            if (!isset($album_data['album_id']) || $album_data['access'] == 0 ||
                    (COM_isAnonUser() && $_MG_CONF['loginrequired'] == 1)) {
                return str_replace($autotag['tagstr'], '', $content);
            }
            $V = COM_newTemplate(MG_getTemplatePath(0));
            $V->set_file('xspf', 'xspf_radio.thtml');
            $V->set_var(array(
                'aid'      => $parm1,
                'site_url' => $_MG_CONF['site_url'],
                'autoplay' => $autoplay ? 'play' : 'stop',
                'id'       => 'mp3radio' . rand(),
                'id2'      => 'mp3radio' . rand(),
                'xhtml'    => XHTML,
            ));
            $media = $V->finish($V->parse('output', 'xspf'));
            $link = MG_helper_getContainer($media, $align, 'span');
            break;

        case 'video' :
            $sql = "SELECT ma.album_id,m.media_id,m.mime_type,m.remote_url,m.media_filename,"
                        . "m.media_mime_ext,m.media_original_filename,m.media_tn_attached,"
                        . "m.media_resolution_x,m.media_resolution_y,m.remote_media "
                 . "FROM {$_TABLES['mg_media']} AS m "
                 . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON m.media_id=ma.media_id "
                 . "WHERE m.media_id='" . DB_escapeString($parm1) . "'";
            $result = DB_query($sql);
            if (DB_numRows($result) <= 0) return str_replace($autotag['tagstr'], '', $content);
            $row = DB_fetchArray($result);
            $album_data = MG_getAlbumData($row['album_id'], array('album_id'), true);
            if (!isset($album_data['album_id']) || $album_data['access'] == 0) {
                return str_replace($autotag['tagstr'], '', $content);
            }
            $orig_media_url = $_MG_CONF['mediaobjects_url'] . '/orig/' . $row['media_filename'][0] . '/' . $row['media_filename'] . '.' . $row['media_mime_ext'];
            // determine height / width and aspect
            if ($width === 'auto' && $row['media_resolution_x'] > 0 && $row['media_resolution_y'] > 0) {
                $videoheight = $row['media_resolution_y'];
                $videowidth  = $row['media_resolution_x'];
            } else {
                $ratio =  0.75;
                $orientation = 0;
                if ($row['media_resolution_x'] > 0 && $row['media_resolution_y'] > 0) {
                    if ($row['media_resolution_x'] >= $row['media_resolution_y']) {
                        // landscape
                        $ratio = $row['media_resolution_y'] / $row['media_resolution_x'];
                    } else {
                        // portrait
                        $ratio = $row['media_resolution_x'] / $row['media_resolution_y'];
                        $orientation = 1;
                    }
                }
                if ($orientation == 0) {
                    if ($width > 0 && $height == 0) {
                        $videoheight = round($width * $ratio);
                        $videowidth  = $width;
                    } else if ($width == 0 && $height == 0) {
                        $videoheight = 200 * $ratio;
                        $videowidth  = 200;
                    } else if ($width == 0 && $height > 0) {
                        $videowidth = round($height / $ratio);
                        $videoheight = $height;
                    } else if ($width > 0 && $height > 0) {
                        $videowidth = $width;
                        $videoheight = $height;
                    }
                } else {
                    if ($width > 0 && $height == 0) {
                        $videoheight = round($width / $ratio);
                        $videowidth  = $width;
                    } else if ($width == 0 && $height == 0) {
                        $videoheight = 200;
                        $videowidth  = round(200 / $ratio);
                    } else if ($width == 0 && $height > 0) {
                        $videowidth = round($height * $ratio);
                        $videoheight = $height;
                    } else if ($width > 0 && $height > 0) {
                        $videowidth = $width;
                        $videoheight = $height;
                    }
                }
            }
            switch ($row['mime_type']) {
                case 'embed' :
                    $media = $row['remote_url'];
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
                    $V = COM_newTemplate(MG_getTemplatePath(0));
                    $V->set_file('video', 'view_asf.thtml');
                    $V->set_var(array(
                        'autostart'          => $autoplay ? 'true' : 'false',
                        'enablecontextmenu'  => 'true',
                        'stretchtofit'       => 'false',
                        'showstatusbar'      => 'false',
                        'showcontrols'       => 'true',
                        'showdisplay'        => 'false',
                        'height'             => $videoheight,
                        'width'              => $videowidth,
                        'bgcolor'            => '#FFFFFF',
                        'playcount'          => '9999',
                        'loop'               => 'true',
                        'movie'              => $orig_media_url,
                        'autostart0'         => $autoplay ? '1' : '0',
                        'enablecontextmenu0' => '1',
                        'stretchtofit0'      => '0',
                        'showstatusbar0'     => '0',
                        'uimode0'            => 'none',
                        'showcontrols0'      => '1',
                        'showdisplay0'       => '0',
                        'xhtml'              => XHTML,
                    ));
                    $media = $V->finish($V->parse('output', 'video'));
                    break;

                case 'video/mpeg' :
                case 'video/x-motion-jpeg' :
                case 'video/quicktime' :
                case 'video/mpeg' :
                case 'video/x-mpeg' :
                case 'video/x-mpeq2a' :
                case 'video/x-qtc' :
                case 'video/x-m4v' :
                    $V = COM_newTemplate(MG_getTemplatePath(0));
                    $V->set_file('video', 'view_quicktime.thtml');
                    $V->set_var(array(
                        'autoref'    => 'true',
                        'autoplay'   => $autoplay ? 'true' : 'false',
                        'controller' => 'true',
                        'kioskmode'  => 'true',
                        'scale'      => 'aspect',
                        'height'     => $videoheight,
                        'width'      => $videowidth,
                        'bgcolor'    => '#F0F0F0',
                        'loop'       => 'true',
                        'movie'      => $orig_media_url,
                        'xhtml'      => XHTML,
                    ));
                    $media = $V->finish($V->parse('output', 'video'));
                    break;

                case 'application/x-shockwave-flash' :
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

                    $poResult = DB_query("SELECT * FROM {$_TABLES['mg_playback_options']} WHERE media_id='" . $row['media_id'] . "'");
                    while ($poRow = DB_fetchArray($poResult)) {
                        $playback_options[$poRow['option_name']] = $poRow['option_value'];
                    }
                    if ($swfjsinclude > 0) {
                        $link = '';
                    } else {
                        $S = COM_newTemplate(MG_getTemplatePath(0));
                        $S->set_file('swf', 'swfobject.thtml');
                        $S->set_var('site_url', $_MG_CONF['site_url']);
                        $link = $S->finish($S->parse('output', 'swf'));
                        $swfjsinclude++;
                    }

                    $V = COM_newTemplate(MG_getTemplatePath(0));
                    $V->set_file('video', 'view_swf.thtml');
                    $V->set_var(array(
                        'site_url'     => $_MG_CONF['site_url'],
                        'lang_noflash' => $LANG_MG03['no_flash'],
                        'play'         => ($autoplay ? 'true' : 'false'),
                        'menu'         => ($playback_options['menu'] ? 'true' : 'false'),
                        'loop'         => ($playback_options['loop'] ? 'true' : 'false'),
                        'scale'        => $playback_options['scale'],
                        'wmode'        => $playback_options['wmode'],
                        'flashvars'    => $playback_options['flashvars'],
                        'quality'      => $playback_options['quality'],
                        'height'       => $videoheight,
                        'width'        => $videowidth,
                        'asa'          => $playback_options['allowscriptaccess'],
                        'bgcolor'      => $playback_options['bgcolor'],
                        'swf_version'  => $playback_options['swf_version'],
                        'filename'     => $row['media_original_filename'],
                        'id'           => $row['media_filename'] . rand(),
                        'id2'          => $row['media_filename'] . rand(),
                        'movie'        => $orig_media_url,
                        'xhtml'        => XHTML,
                    ));
                    $media = $V->finish($V->parse('output', 'video'));
                    break;

                case 'video/x-flv' :
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
                    $playback_options['bgcolor']    = $_MG_CONF['swf_bgcolor'];
                    $playback_options['swf_version'] = $_MG_CONF['swf_version'];
                    $playback_options['flashvars']   = $_MG_CONF['swf_flashvars'];

                    $poResult = DB_query("SELECT * FROM {$_TABLES['mg_playback_options']} WHERE media_id='" . $row['media_id'] . "'");
                    while ($poRow = DB_fetchArray($poResult)) {
                        $playback_options[$poRow['option_name']] = $poRow['option_value'];
                    }
                    if ($swfjsinclude > 0) {
                        $link = '';
                    } else {
                        $S = COM_newTemplate(MG_getTemplatePath(0));
                        $S->set_file('swf', 'swfobject.thtml');
                        $S->set_var('site_url', $_MG_CONF['site_url']);
                        $link = $S->finish($S->parse('output', 'swf'));
                        $swfjsinclude++;
                    }

                    // now the player specific items.
                    if ($autoplay == 1) {  // auto start
                        $playButton = '';
                    } else {
                        if ($row['media_tn_attached'] == 1) {
                            $tfn = 'tn/' . $row['media_filename'][0] . '/tn_' . $row['media_filename'];
                            $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . $tfn);
                            $playImage = $_MG_CONF['mediaobjects_url'] . '/' . $tfn . $ext;
                        } else {
                            $playImage = MG_getImageFile('blank_blk.jpg');
                        }
                        $playButton = "{ url: '" . $playImage . "', overlayId: 'play' },";
                    }
                    if ($row['remote_media'] == 1) {
                        $urlParts = array();
                        $urlParts = parse_url($row['remote_url']);

                        $pathParts = array();
                        $pathParts = explode('/',$urlParts['path']);

                        $ppCount = count($pathParts);
                        $pPath = '';
                        for ($row=1; $row<$ppCount-1; $row++) {
                            $pPath .= '/' . $pathParts[$row];
                        }
                        $videoFile = $pathParts[$ppCount-1];

                        $pos = strrpos($videoFile, '.');
                        if ($pos === false) {
                            $basefilename = $videoFile;
                        } else {
                            $basefilename = substr($videoFile,0,$pos);
                        }
                        $videoFile            = $basefilename;
                        $streamingServerURL   = "streamingServerURL: '" . $urlParts['scheme'] . '://' . $urlParts['host'] . $pPath . "',";
                        $streamingServer      = "streamingServer: 'fms',";
                    } else {
                        $streamingServerURL   = '';
                        $streamingServer      = '';
                        $videoFile            = urlencode($orig_media_url);
                    }
                    $width  = $videowidth;
                    $height = $videoheight + 22;
                    $resolution_x = $videowidth;
                    $resolution_y = $videoheight;
                    $id  = 'id_'  . rand();
                    $id2 = 'id2_' . rand();
                    $F = COM_newTemplate(MG_getTemplatePath(0));
                    $F->set_file('player', 'flvfp.thtml');
                    $F->set_var(array(
                        'site_url'          => $_MG_CONF['site_url'],
                        'lang_noflash'      => $LANG_MG03['no_flash'],
                        'play'              => ($autoplay ? 'true' : 'false'),
                        'menu'              => ($playback_options['menu'] ? 'true' : 'false'),
                        'loop'              => ($playback_options['loop'] ? 'true' : 'false'),
                        'scale'             => $playback_options['scale'],
                        'wmode'             => $playback_options['wmode'],
                        'width'             => $width,
                        'height'            => $height,
                        'streamingServerURL'=> $streamingServerURL,
                        'streamingServer'   => $streamingServer,
                        'videoFile'         => $videoFile,
                        'playButton'        => $playButton,
                        'id'                => $id,
                        'id2'               => $id2,
                        'resolution_x'      => $resolution_x,
                        'resolution_y'      => $resolution_y,
                        'xhtml'             => XHTML,
                    ));
                    $flv_player = $F->finish($F->parse('output', 'player'));

                    $V = COM_newTemplate(MG_getTemplatePath(0));
                    $V->set_file('video', 'view_flv_light.thtml');
                    $V->set_var(array(
                        'site_url'      => $_MG_CONF['site_url'],
                        'lang_noflash'  => $LANG_MG03['no_flash'],
                        'id'            => $id,
                        'id2'           => $id2,
                        'width'         => $resolution_x,
                        'height'        => $resolution_y,
                        'flv_player'    => $flv_player,
                        'xhtml'         => XHTML,
                    ));
                    $media = $V->finish($V->parse('output', 'video'));
                    break;
            }
            $link = MG_helper_getContainer($media, $align, 'div');
            break;

        case 'audio' :
            $sql = "SELECT ma.album_id,m.media_title,m.mime_type,m.media_tn_attached,m.media_filename,m.media_mime_ext "
                 . "FROM {$_TABLES['mg_media']} AS m "
                 . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON m.media_id=ma.media_id "
                 . "WHERE m.media_id='" . DB_escapeString($parm1) . "'";
            $result = DB_query($sql);
            if (DB_numRows($result) <= 0) return str_replace($autotag['tagstr'], '', $content);
            $row = DB_fetchArray($result);
            $album_data = MG_getAlbumData($row['album_id'], array('album_id'), true);
            if (!isset($album_data['album_id']) || $album_data['access'] == 0) {
                return str_replace($autotag['tagstr'], '', $content);
            }
            $orig_media_url = $_MG_CONF['mediaobjects_url'] . '/orig/' . $row['media_filename'][0] . '/' . $row['media_filename'] . '.' . $row['media_mime_ext'];
            switch ($row['mime_type']) {
                case 'audio/mpeg' :
                    $playback_options['height'] = 50;
                    $playback_options['width']  = 200;
                    $u_pic = '';
                    if ($row['media_tn_attached'] == 1) {
                        $tfn = 'tn/' . $row['media_filename'][0] . '/tn_' . $row['media_filename'];
                        $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . $tfn);
                        $u_tn = $_MG_CONF['mediaobjects_url'] . '/' . $tfn . $ext;
                        $media_size_disp  = @getimagesize($_MG_CONF['path_mediaobjects'] . $tfn . $ext);
                        $border_width = $media_size_disp[0] + 12;
                        $u_pic = '<img src="' . $u_tn . '" alt="" style="border:none;vertical-align:bottom;"' . XHTML . '>';
                    }

                    $V = COM_newTemplate(MG_getTemplatePath(0));
                    $V->set_file('audio', ($mp3_type == 'ribbon') ? 'mp3_podcast.thtml' : 'view_mp3_flv.thtml');
                    $V->set_var(array(
                        'autostart'         => ($autoplay ? 'play' : 'stop'),
                        'enablecontextmenu' => 'true',
                        'stretchtofit'      => 'false',
                        'showstatusbar'     => 'true',
                        'uimode'            => 'mini',
                        'height'            => $playback_options['height'],
                        'width'             => $playback_options['width'],
                        'bgcolor'           => '#FFFFFF',
                        'loop'              => 'true',
                        'u_pic'             => $u_pic,
                        'title'             => urlencode($row['media_title']),
                        'id'                => 'mp3' . rand(),
                        'id2'               => 'mp3' . rand(),
                        'site_url'          => $_MG_CONF['site_url'],
                        'movie'             => $orig_media_url,
                        'mp3_file'          => $orig_media_url,
                        'xhtml'             => XHTML,
                    ));
                    $media = $V->finish($V->parse('output', 'audio'));
                    break;

                case 'audio/x-ms-wma' :
                case 'audio/x-ms-wax' :
                case 'audio/x-ms-wmv' :
                    $playback_options['height'] = 50;
                    $playback_options['width']  = 200;
                    $u_pic = '';
                    if ($row['media_tn_attached'] == 1) {
                        $tfn = 'tn/' . $row['media_filename'][0] . '/tn_' . $row['media_filename'];
                        $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . $tfn);
                        $u_tn = $_MG_CONF['mediaobjects_url'] . '/' . $tfn . $ext;
                        $media_size_disp  = @getimagesize($_MG_CONF['path_mediaobjects'] . $tfn . $ext);
                        $border_width = $media_size_disp[0] + 12;
                        $u_pic = '<div class="out" style="width:' . $border_width . 'px"><div class="in ltin tpin"><img src="' . $u_tn . '" alt="" style="vertical-align:bottom;"' . XHTML . '></div></div>';
                    }

                    $V = COM_newTemplate(MG_getTemplatePath(0));
                    $V->set_file('audio', 'view_mp3_wmp.thtml');
                    $V->set_var(array(
                        'autostart'         => ($autoplay ? '1' : '0'), // $autoplay ? 'true' : 'false',
                        'enablecontextmenu' => 'true',
                        'stretchtofit'      => 'false',
                        'showstatusbar'     => 'true',
                        'uimode'            => 'mini',
                        'height'            => $playback_options['height'],
                        'width'             => $playback_options['width'],
                        'bgcolor'           => '#FFFFFF',
                        'loop'              => 'true',
                        'u_pic'             => $u_pic,
                        'movie'             => $orig_media_url,
                        'xhtml'             => XHTML,
                    ));
                    $media = $V->finish($V->parse('output', 'audio'));
                    break;
            }
            $link = MG_helper_getContainer($media, $align, 'div');
            break;

        case 'fslideshow' :
            if (empty($parm1)) return $content;
            $album_data = MG_getAlbumData($parm1, array('album_title', 'album_id', 'hidden'), true);
            if (!isset($album_data['album_id']) || $album_data['access'] == 0) {
                return str_replace($autotag['tagstr'], '', $content);
            }

            if ($width > 0 && $height == 0) {
                $height = $width * 0.75;
            } else if ($width == 0 && $height == 0) {
                $height = $width = 200;
            } else if ($width == 0  && $height > 0) {
                $width = $height * 1.3333;
            }
            // if none of the above, assume height and width both specified.

            if ($caption == '' && $_MG_CONF['autotag_caption'] && isset($parm1) ) {
                $caption = $album_data['album_title'];
            }
            $captionHTML = '<br' . XHTML . '><span style="width:' . $width . 'px;font-style:italic;font-size: smaller;text-indent:0;">' . $caption . '</span>' . LB;
            $ss_count++;

            $T = COM_newTemplate(MG_getTemplatePath(0));
            $T->set_file('fslideshow', 'fsat.thtml');
            $T->set_var('site_url', $_MG_CONF['site_url']);
            $T->set_var(array(
                'id'            => 'mms' . $ss_count,
                'id2'           => 'fsid' . $ss_count,
                'movie'         => $_MG_CONF['site_url'] . '/xml.php?aid=' . $parm1 . '%26src=' . trim($src),
                'dropshadow'    => 'true',
                'delay'         => $delay,
                'nolink'        => ($album_data['hidden'] || $enable_link == 0) ? 'true' : 'false',
                'showtitle'     => ($showtitle == 'bottom' || $showtitle == 'top') ? '&showTitle=' . $showtitle : '',
                'width'         => $width,
                'height'        => $height,
                'xhtml'         => XHTML,
            ));
            $swfobject = $T->finish($T->parse('output', 'fslideshow'));
            $media = $swfobject . $captionHTML;
/*
            $style = '';
            if ($align == 'center') {
                $style = 'text-align:center;';
            } else if ($align != '') {
                $style = 'float:' . $align . ';';
            }
            $link = '<span style="' . $style . 'padding:5px;">' . $media . '</span>';
*/
            if ($align != '' && $align != "center") {
                $link = '<div style="float:' . $align . ';padding:5px;text-align:center;">' . $media . '</div>';
            } else if ($align == 'center') {
                $link = '<div style="padding:5px;text-align:center;">' . $media . '</div>';
            } else {
                $link = '<div style="padding:5px;text-align:center;">' . $media . '</div>';
            }
            break;

        case 'slideshow' :
            if (empty($parm1)) return $content;
            $album_data = MG_getAlbumData($parm1, array('album_title', 'album_id', 'hidden'), true);
            if (!isset($album_data['album_id']) || $album_data['access'] == 0) {
                return str_replace($autotag['tagstr'], '', $content);
            }
            if ($caption == '' && $_MG_CONF['autotag_caption'] ) {
                $caption = $album_data['album_title'];
            }
            $aid = $parm1;
            $pics = '';
            $counter = 0;
            $maxwidth = 0;
            $maxheight = 0;
            $ss_count++;

            $sql = MG_buildMediaSql(array(
                'album_id'  => $aid,
                'fields'    => array('media_filename', 'media_mime_ext', 'remote_url'),
                'where'     => "m.media_type = 0 AND m.include_ss = 1"
            ));
            $result = DB_query($sql);
            while ($row = DB_fetchArray($result)) {
                switch ($src) {
                    case 'orig' :
                        $media_size = @getimagesize($_MG_CONF['path_mediaobjects'] . 'orig/' . $row['media_filename'][0] . '/' . $row['media_filename'] . '.' . $row['media_mime_ext']);
                        $ext = $row['media_mime_ext'];
                        break;
                    case 'disp' :
                        $mfn = 'disp/' . $row['media_filename'][0] . '/' . $row['media_filename'];
                        $tnext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . $mfn);
                        $media_size = @getimagesize($_MG_CONF['path_mediaobjects'] . $mfn . $tnext);
                        $ext = substr($tnext,1,3); // no use ?
                        break;
                    default :
                        $mfn = 'tn/' . $row['media_filename'][0] . '/' . $row['media_filename'];
                        $tnext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . $mfn);
                        $media_size = @getimagesize($_MG_CONF['path_mediaobjects'] . $mfn . $tnext);
                        $ext = substr($tnext,1,3); // no use ?
                        $src = 'tn';
                        break;
                }
                if ($media_size == false) {
                    continue;
                }

                $counter++;
                if ($width > 0 && $height == 0) {
                    if ($media_size[0] > $media_size[1]) {        // landscape
                        $ratio = $media_size[0] / $width;
                        $newwidth = $width;
                        $newheight = round($media_size[1] / $ratio);
                    } else {    // portrait
                        $ratio = $media_size[1] / $width;
                        $newheight = $width;
                        $newwidth = round($media_size[0] / $ratio);
                    }
                } else if ($width == 0 && $height == 0) {
                    if ($media_size[0] > $media_size[1]) {        // landscape
                        $ratio = $media_size[0] / 200;
                        $newwidth = 200;
                        $newheight = round($media_size[1] / $ratio);
                    } else {    // portrait
                        $ratio = $media_size[1] / 200;
                        $newheight = 200;
                        $newwidth = round($media_size[0] / $ratio);
                    }
                } else if ($width == 0 && $height > 0) {
                    if ($height > $media_size[1]) {
                        $newheight = $media_size[1];
                        $newwidth = $media_size[0];
                    } else {
                        $ratio = $height / $media_size[1];
                        $newheight = $height;
                        $newwidth = round($media_size[0] * $ratio);
                    }
                } else {
                    $newwidth = $width;
                    $newheight = $height;
                }

                if ($newheight > $maxheight) {
                    $maxheight = $newheight;
                }
                if ($newwidth > $maxwidth) {
                    $maxwidth  = $newwidth;
                }
/*
                if ($album->hidden == 1 || $enable_link == 0) {
                    $pics .= '<img class="slideshowThumbnail' . $ss_count . '" src="' . $_MG_CONF['mediaobjects_url'] . '/' . $src . '/' . $row['media_filename'][0] . '/' . $row['media_filename'] . '.' . $ext . '" alt="" style="width:' . $newwidth . 'px;height:' . $newheight . 'px;border:none;position:absolute;left:0px;top:0px;vertical-align:bottom;"' . XHTML . '>' . LB;
                } else {
                    $pics .= '<img class="slideshowThumbnail' . $ss_count . '" src="' . $_MG_CONF['mediaobjects_url'] . '/' . $src . '/' . $row['media_filename'][0] . '/' . $row['media_filename'] . '.' . $ext . '" alt="" style="width:' . $newwidth . 'px;height:' . $newheight . 'px;border:none;position:absolute;left:0px;top:0px;vertical-align:bottom;"' . XHTML . '>' . LB;
                }
*/
                $pics .= '<img class="slideshowThumbnail' . $ss_count . '" src="' . $_MG_CONF['mediaobjects_url'] . '/' . $src . '/' . $row['media_filename'][0] . '/' . $row['media_filename'] . '.' . $ext
                      . '" alt="" style="width:' . $newwidth . 'px;height:' . $newheight . 'px;'
                      . 'border:none;position:absolute;left:0px;top:0px;vertical-align:bottom;"' . XHTML . '>' . LB;
            }
            if ($delay <= 0) {
                $delay = 10;
            }
            if ($album_data['hidden'] == 1 || $enable_link == 0) {
                $ss_url = '';
            } else {
//              $ss_url = '<a href="' . $_MG_CONF['site_url'] . '/album.php?aid=' . $aid . '"' . ($target=='' ? '' : ' target="' . $target . '"') . '>';
                $ss_url = $_MG_CONF['site_url'] . '/album.php?aid=' . $aid;
            }

            $link = '';
            if ($counter != 0) {
                $T = COM_newTemplate($_MG_CONF['template_path']);
                $T->set_file('tag', 'autotag_ss.thtml');
                $T->set_var(array(
                    'align'      => $align,
                    'pics'       => $pics,
                    'caption'    => $caption,
                    'maxheight'  => $maxheight,
                    'maxwidth'   => $maxwidth,
                    'width'      => $maxwidth,
                    'framewidth' => $maxwidth + 10,
                    'ss_count'   => $ss_count,
                    'delay'      => $delay * 1000,
                    'border'     => $border ? 'border: silver solid;border-width: 1px;' : '',
                    'xhtml'      => XHTML,
                    'sslink'     => $ss_url,
                ));
                if ($align == 'left' || $align == 'right') {
                    $T->set_var('float','float: ' . $align . ';');
                } else {
                    $T->set_var('float','float:left;');
                    $align = 'left';
                }

                $T->set_var('margin-right', ($align == 'left') ? 'margin-right:15px;' : '');

                $link = $T->finish($T->parse('output', 'tag'));
            }
/*
            if ($align == 'center') {
                $link = '<center>' . $link . '</center>';
            }
*/
            break;




// Testing //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        case 'gallery' :
            if (empty($parm1)) return $content;
            $album_data = MG_getAlbumData($parm1, array('album_title', 'album_id', 'hidden'), true);
            if (!isset($album_data['album_id']) || $album_data['access'] == 0) {
                return str_replace($autotag['tagstr'], '', $content);
            }
            if ($caption == '' && $_MG_CONF['autotag_caption'] ) {
                $caption = $album_data['album_title'];
            }
            $aid = $parm1;
            $pics = '';
            $counter = 0;
            $maxwidth = 0;
            $maxheight = 0;
            $ss_count++;

            $sql = MG_buildMediaSql(array(
                'album_id'  => $aid,
                'fields'    => array('media_filename', 'media_mime_ext', 'remote_url'),
                'where'     => "m.media_type = 0 AND m.include_ss = 1"
            ));
            $result = DB_query($sql);
            while ($row = DB_fetchArray($result)) {
                switch ($src) {
                    case 'orig' :
                        $media_size = @getimagesize($_MG_CONF['path_mediaobjects'] . 'orig/' . $row['media_filename'][0] . '/' . $row['media_filename'] . '.' . $row['media_mime_ext']);
                        $ext = $row['media_mime_ext'];
                        break;
                    case 'disp' :
                        $mfn = 'disp/' . $row['media_filename'][0] . '/' . $row['media_filename'];
                        $tnext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . $mfn);
                        $media_size = @getimagesize($_MG_CONF['path_mediaobjects'] . $mfn . $tnext);
                        $ext = substr($tnext,1,3); // no use ?
                        break;
                    default :
                        $mfn = 'tn/' . $row['media_filename'][0] . '/' . $row['media_filename'];
                        $tnext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . $mfn);
                        $media_size = @getimagesize($_MG_CONF['path_mediaobjects'] . $mfn . $tnext);
                        $ext = substr($tnext,1,3); // no use ?
                        $src = 'tn';
                        break;
                }
                if ($media_size == false) {
                    continue;
                }

                $counter++;
                if ($width > 0 && $height == 0) {
                    if ($media_size[0] > $media_size[1]) {        // landscape
                        $ratio = $media_size[0] / $width;
                        $newwidth = $width;
                        $newheight = round($media_size[1] / $ratio);
                    } else {    // portrait
                        $ratio = $media_size[1] / $width;
                        $newheight = $width;
                        $newwidth = round($media_size[0] / $ratio);
                    }
                } else if ($width == 0 && $height == 0) {
                    if ($media_size[0] > $media_size[1]) {        // landscape
                        $ratio = $media_size[0] / 200;
                        $newwidth = 200;
                        $newheight = round($media_size[1] / $ratio);
                    } else {    // portrait
                        $ratio = $media_size[1] / 200;
                        $newheight = 200;
                        $newwidth = round($media_size[0] / $ratio);
                    }
                } else if ($width == 0 && $height > 0) {
                    if ($height > $media_size[1]) {
                        $newheight = $media_size[1];
                        $newwidth = $media_size[0];
                    } else {
                        $ratio = $height / $media_size[1];
                        $newheight = $height;
                        $newwidth = round($media_size[0] * $ratio);
                    }
                } else {
                    $newwidth = $width;
                    $newheight = $height;
                }

                if ($newheight > $maxheight) {
                    $maxheight = $newheight;
                }
                if ($newwidth > $maxwidth) {
                    $maxwidth  = $newwidth;
                }
                $pics .= '<img class="slideshowThumbnail' . $ss_count . '" src="' . $_MG_CONF['mediaobjects_url'] . '/' . $src . '/' . $row['media_filename'][0] . '/' . $row['media_filename'] . '.' . $ext
                      . '" alt="" style="width:' . $newwidth . 'px;height:' . $newheight . 'px;'
                      . 'border:none;position:absolute;left:0px;top:0px;vertical-align:bottom;"' . XHTML . '>' . LB;
            }
            if ($delay <= 0) {
                $delay = 10;
            }
            if ($album_data['hidden'] == 1 || $enable_link == 0) {
                $ss_url = '';
            } else {
                $ss_url = $_MG_CONF['site_url'] . '/album.php?aid=' . $aid;
            }

            $link = '';
            if ($counter != 0) {
                $T = COM_newTemplate($_MG_CONF['template_path']);
                $T->set_file('tag', 'autotag_ss.thtml');
                $T->set_var(array(
                    'align'      => $align,
                    'pics'       => $pics,
                    'caption'    => $caption,
                    'maxheight'  => $maxheight,
                    'maxwidth'   => $maxwidth,
                    'width'      => $maxwidth,
                    'framewidth' => $maxwidth + 10,
                    'ss_count'   => $ss_count,
                    'delay'      => $delay * 1000,
                    'border'     => $border ? 'border: silver solid;border-width: 1px;' : '',
                    'xhtml'      => XHTML,
                    'sslink'     => $ss_url,
                ));
                if ($align == 'left' || $align == 'right') {
                    $T->set_var('float','float: ' . $align . ';');
                } else {
                    $T->set_var('float','float:left;');
                    $align = 'left';
                }

                $T->set_var('margin-right', ($align == 'left') ? 'margin-right:15px;' : '');

                $link = $T->finish($T->parse('output', 'tag'));
            }
            break;
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////





        case 'album' :
            if (empty($parm1)) {
                $side_count--;
                return $content;
            }
            $album_data = MG_getAlbumData($parm1, array('album_title', 'album_id', 'hidden', 'tn_attached'), true);
            if (!isset($album_data['album_id']) || $album_data['access'] == 0) {
                $side_count--;
                return str_replace($autotag['tagstr'], '', $content);
            }
            $ss_count++;

            if ($caption != '') {
                $alttag = ' alt="' . $caption . '" title="' . $caption . '"';
            } else {
                $alttag = ' alt=""';
                if ($_MG_CONF['autotag_caption']) {
                    $caption = $album_data['album_title'];
                }
            }
//            $aid = $parm1;

            if ($album_data['tn_attached'] == 1) {
                $tfn = 'covers/cover_' . $parm1;
                $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . $tfn);
                $tnImage = $_MG_CONF['mediaobjects_url'] . '/' . $tfn . $ext;
                $tnFileName = $_MG_CONF['path_mediaobjects'] . $tfn . $ext;
            } else {
                $filename = MG_getAlbumCover($parm1);
                if ($filename != '') {
                    $tfn = 'tn/' . $filename[0] . '/' . $filename;
                    $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . $tfn);
                    $tnImage = $_MG_CONF['mediaobjects_url'] . '/' . $tfn . $ext;
                    $tnFileName = $_MG_CONF['path_mediaobjects'] . $tfn . $ext;
                } else {
                    $tnImage = $_MG_CONF['mediaobjects_url'] . '/empty.png';
                    $tnFileName = $_MG_CONF['path_mediaobjects'] . 'empty.png';
                }
            }
            $media_size = @getimagesize($tnFileName);
            if ($media_size == false) {
                $tnImage = $_MG_CONF['mediaobjects_url'] . '/missing.png';
                $tnFileName = $_MG_CONF['path_mediaobjects'] . 'missing.png';
                $media_size = @getimagesize($tnFileName);
            }
            if ($width > 0 && $height == 0) {
                $ratio = $media_size[0] / $width;
                $newwidth = $width;
                $newheight = round($media_size[1] / $ratio);
            } else if ($width == 0 && $height == 0) {
                if ($media_size[0] > $media_size[1]) {        // landscape
                    $ratio = $media_size[0] / 200;
                    $newwidth = 200;
                    $newheight = round($media_size[1] / $ratio);
                } else {    // portrait
                    $ratio = $media_size[1] / 200;
                    $newheight = 200;
                    $newwidth = round($media_size[0] / $ratio);
                }
            } else if ($width == 0 && $height > 0) {
                $ratio = $height / $media_size[1];
                $newheight = $height;
                $newwidth = round($media_size[0] * $ratio);
            } else {
                $newwidth = $width;
                $newheight = $height;
            }
            $tagtext = '<img src="' . $tnImage . '" ' . $alttag . 'style="width:' . $newwidth . 'px;height:' . $newheight . 'px;border:none;vertical-align:bottom;"' . XHTML . '>';

            if ($linkID == 0) {
                $url = $_MG_CONF['site_url'] . '/album.php?aid=' . $parm1;
            } else {
                if ($linkID < 1000000) {
                    $url = $_MG_CONF['site_url'] . '/album.php?aid=' . $linkID;
                } else {
                    $url = $_MG_CONF['site_url'] . '/media.php?s=' . $linkID;
                }
            }
            if ($enable_link == 0) {
                $link = $tagtext;
            } else {
                $link = '<a href="' . $url . '"' . ($target=='' ? '' : ' target="' . $target . '"') . '>' . $tagtext . '</a>';
            }

            $T = COM_newTemplate($_MG_CONF['template_path']);
            $T->set_file('tag', ($border == 0) ? 'autotag_nb.thtml' : 'autotag.thtml');
            $T->set_var(array(
                'ss_count'     => $ss_count,
                'align'        => $align,
                'autotag'      => $link,
                'caption'      => $caption,
                'width'        => $newwidth,
                'framewidth'   => $newwidth,
                'xhtml'        => XHTML,
                'float'        => (($align == 'left' || $align == 'right') ? 'float:' . $align . ';' : ''),
                'margin-right' => (($align == 'left') ? 'margin-right:15px;' : ''),
            ));

            if ($align == 'center') {
                $T->set_var('margin-right', 'margin:0 auto;');
            }

            $link = $T->finish($T->parse('output', 'tag'));

            if ($align == 'center') {
                $link = '<div style="text-align:center;">' . LB . $link . '</div>'. LB;
            }
            break;

        case 'alink' :
            if ($parm1 == '' || $parm1 == 0) {
                $side_count--;
                return $content;
            }
            $album_data = MG_getAlbumData($parm1, array('album_title', 'album_id', 'hidden'), true);
            if (!isset($album_data['album_id']) || $album_data['access'] == 0) {
                $side_count--;
                return str_replace($autotag['tagstr'], '', $content);
            }
            if ($caption == '') {
                $caption = $album_data['album_title'];
            }
            $link = '<a href="'.$_MG_CONF['site_url'] . '/album.php?aid=' . $album_data['album_id'] .'">'.$caption.'</a>';
            return str_replace ($autotag['tagstr'], $link, $content);
            break;

        case 'media' :
        /* image, oimage and img are depreciated */
        case 'image' :
        case 'oimage' :
        case 'img' :
            if (empty($parm1)) return $content;
            $direct_link = '';
            $ss_count++;
            $alttag = ' alt=""';
            if ($caption != '') {
                $alttag = ' alt="' . $caption . '" title="' . $caption . '"';
            }
            $sql = "SELECT ma.album_id,m.media_title,m.media_type,m.media_filename,"
                        . "m.media_mime_ext,m.mime_type,m.media_tn_attached,m.remote_url "
                 . "FROM {$_TABLES['mg_media']} AS m "
                 . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON m.media_id=ma.media_id "
                 . "WHERE m.media_id='" . DB_escapeString($parm1) . "'";
            $result = DB_query($sql);
            if (DB_numRows($result) <= 0) return $content; // no image found
            $row = DB_fetchArray($result);
            $aid = $row['album_id'];
            $album_data = MG_getAlbumData($aid, array('album_id', 'hidden'), true);
            if (!isset($album_data['album_id']) || $album_data['access'] == 0) {
                return str_replace($autotag['tagstr'], '', $content);
            }
            if ($caption == '' && $_MG_CONF['autotag_caption']) {
                $caption = $row['media_title'];
            }
            switch($row['media_type']) {
                case 0 :    // standard image
                    $fn =          $row['media_filename'][0] . '/' . $row['media_filename'] . '.' . $row['media_mime_ext'];
                    $tfn = 'tn/' . $row['media_filename'][0] . '/' . $row['media_filename'];
                    if ($autotag['tag'] == 'oimage') {
                        $default_thumbnail = (($_MG_CONF['discard_original'] == 1) ? 'disp/' : 'orig/') . $fn;
                    } else {
                        switch ($src) {
                            case 'orig' :
                                $default_thumbnail = (($_MG_CONF['discard_original'] == 1) ? 'disp/' : 'orig/') . $fn;
                                break;
                            case 'disp' :
                                $default_thumbnail = 'disp/' . $fn;
                                break;
                            default :
                                $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . $tfn);
                                $default_thumbnail = $tfn . $ext;
                                break;
                        }
                        $lfn = $link_src . '/' . $row['media_filename'][0] . '/' . $row['media_filename'];
                        $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . $lfn);
                        $direct_link = $_MG_CONF['mediaobjects_url'] . '/' . $lfn . $ext;
                    }
                    break;
                case 1 :    // video file
                    switch ($row['mime_type']) {
                        case 'application/x-shockwave-flash' :
                            $default_thumbnail = 'flash.png';
                            break;
                        case 'video/quicktime' :
                        case 'video/mpeg' :
                        case 'video/x-m4v' :
                            $default_thumbnail = 'quicktime.png';
                            break;
                        case 'video/x-ms-asf' :
                        case 'video/x-ms-wvx' :
                        case 'video/x-ms-wm' :
                        case 'video/x-ms-wmx' :
                        case 'video/x-msvideo' :
                        case 'application/x-ms-wmz' :
                        case 'application/x-ms-wmd' :
                            $default_thumbnail = 'wmp.png';
                            break;
                        default :
                            $default_thumbnail = 'video.png';
                            break;
                    }
                    $src = 'tn';
                    break;
                case 2 :    // music file
                    $src = 'tn';
                    $default_thumbnail = 'audio.png';
                    break;
            }
            if ($row['media_tn_attached'] == 1 && ($src != 'orig' && $src != 'disp')) {
                $tfn = 'tn/' . $row['media_filename'][0] . '/tn_' . $row['media_filename'];
                $ext = MG_getMediaExt($_MG_CONF['path_mediaobjects'] . $tfn);
                $media_thumbnail      = $_MG_CONF['mediaobjects_url'] . '/' . $tfn . $ext;
                $media_thumbnail_file = $_MG_CONF['path_mediaobjects'] . $tfn . $ext;
            } else {
                $media_thumbnail      = $_MG_CONF['mediaobjects_url'] . '/' . $default_thumbnail;
                $media_thumbnail_file = $_MG_CONF['path_mediaobjects'] . $default_thumbnail;
            }

            $mediaSize = @getimagesize($media_thumbnail_file);
            if ($mediaSize == false) return str_replace($autotag['tagstr'], '', $content);
            if ($autotag['tag'] == 'oimage' || $src == 'orig') {
                $newwidth  = $mediaSize[0];
                $newheight = $mediaSize[1];
            } else {
                if ($width > 0) {
                    $tn_height = $width;
                } else {
                    switch ($src) {
                        case 'orig' :
                        case 'disp' :
                            $tn_height = $mediaSize[0];
                            break;
                        default :
                            $tn_height = 200;
                            break;
                    }
                }

                if ($mediaSize[0] > $mediaSize[1]) {
                    $ratio = $mediaSize[0] / $tn_height;
                    $newwidth = $tn_height;
                    $newheight = round($mediaSize[1] / $ratio);
                } else {
                    $ratio = $mediaSize[1] / $tn_height;
                    $newheight = $tn_height;
                    $newwidth = round($mediaSize[0] / $ratio);
                }
            }
            $tagtext = '<img src="' . $media_thumbnail . '" ' . $alttag . ' style="width:' . $newwidth . 'px;height:' . $newheight . 'px;border:none;vertical-align:bottom;"' . XHTML . '>';

            $link = '';
            if ($alt == 1 && $row['remote_url'] != '') {

                $url = $row['remote_url'];
                if ($autotag['tag'] != 'image' && $enable_link != 0) {
                    $link = '<a href="' . $url . '"' . ($target=='' ? '' : ' target="' . $target . '"') . '>' . $tagtext . '</a>';
                } else {
                    $link = $tagtext;
                }

            } else if ($linkID == 0) {

                $url = $_MG_CONF['site_url'] . '/media.php?s=' . $parm1;

            } else {


                if ($linkID < 1000000) {
                    $link_album = MG_getAlbumData($linkID, array('album_id', 'hidden'), false);
                    if (!isset($link_album['album_id'])) {
                        $url = $_MG_CONF['site_url'] . '/album.php?aid=' . $linkID;
                        if ($autotag['tag'] != 'image' && $link_album['hidden'] != 1 && $enable_link != 0) {
                            $link = '<a href="' . $url . '"' . ($target=='' ? '' : ' target="' . $target . '"') . '>' . $tagtext . '</a>';
                         } else {
                            $link = $tagtext;
                        }
                    } else {
                        $url = $_MG_CONF['site_url'] . '/media.php?s=' . $parm1;
                    }

                } else {

                    $linkAID = intval(DB_getItem($_TABLES['mg_media_albums'], 'album_id', 'media_id="' . DB_escapeString($linkID) . '"'));
                    if ($linkAID != 0) {
                        $url = $_MG_CONF['site_url'] . '/media.php?s=' . $linkID;
                        $hidden = DB_getItem($_TABLES['mg_albums'], 'hidden', "album_id=" . intval($linkAID));
                        if ($autotag['tag'] != 'image' && $hidden != 1 && $enable_link != 0) {
                            $link = '<a href="' . $url . '"' . ($target=='' ? '' : ' target="' . $target . '"') . '>' . $tagtext . '</a>';
                        } else {
                            $link = $tagtext;
                        }
                    } else {
                        $url = $_MG_CONF['site_url'] . '/media.php?s=' . $parm1;
                    }

                }
            }

            if ($link == '') {
                if ($autotag['tag'] != 'image' && ($album_data['hidden'] != 1 || $enable_link == 2) && $enable_link != 0) {
                    if ($enable_link == 2 && $direct_link != '') {
                        if ($_MG_CONF['disable_lightbox'] == true) {
                            $link = $tagtext;
                        } else {
                            $link = '<a href="' . $direct_link . '" rel="lightbox" title="' . strip_tags(str_replace('$','&#36;',$caption)) . '">' . $tagtext . '</a>';
                        }
                    } else {
                        $link = '<a href="' . $url . '"' . ($target=='' ? '' : ' target="' . $target . '"') . '>' . $tagtext . '</a>';
                    }
                } else {
                    $link = $tagtext;
                }
            }

            if ($autotag['tag'] == 'img') {
                $link = MG_helper_getContainer($link, $align, 'div');
            } else {
                $T = COM_newTemplate($_MG_CONF['template_path']);
                $T->set_file('tag', ($border == 0) ? 'autotag_nb.thtml' : 'autotag.thtml');
                $T->set_var(array(
                    'ss_count'     => $ss_count,
                    'align'        => $align,
                    'autotag'      => $link,
                    'caption'      => $caption,
                    'width'        => $newwidth,
                    'framewidth'   => $newwidth, // + 10,
                    'xhtml'        => XHTML,
                    'float'        => (($align == 'left' || $align == 'right') ? 'float:' . $align . ';' : ''),
                    'margin-right' => (($align == 'left') ? 'margin:15px;' : ''),
                ));

                if ($align == 'center') {
                    $T->set_var('margin-right', 'margin:0 auto;');
                }

                $link = $T->finish($T->parse('output', 'tag'));

                if ($align == 'center') {
                    $link = '<div style="text-align:center;">' . LB . $link . '</div>'. LB;
                }
            }
            break;
    }
    return str_replace($autotag['tagstr'], $link, $content);
}
