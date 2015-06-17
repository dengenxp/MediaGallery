<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | classMedia.php                                                           |
// |                                                                          |
// | Media objects class and handling routines                                |
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

class Media {
    var $id;
    var $title;
    var $description;
    var $filename;
    var $keywords;
    var $mime_ext;
    var $mime_type;
    var $time;
    var $views;
    var $comments;
    var $votes;
    var $rating;
    var $tn_attached;
    var $tn_image;
    var $owner_id;
    var $type;
    var $upload_time;
    var $album_id;
    var $cat_id;
    var $watermarked;
    var $artist;
    var $album;
    var $genre;
    var $resolution_x;
    var $resolution_y;
    var $remote;
    var $remote_url;
    var $access;
    var $media_thumbnail;
    var $media_thumbnail_file;
    var $media_size;

    function Media(&$M, &$aid) {
        $this->id   = $M['media_id'];
        $this->type = $M['media_type'];
        if ($this->type != -1) {
            $this->title            = (isset($M['media_title']) && $M['media_title'] != ' ') ? $M['media_title'] : '';
            $this->description      = (isset($M['media_desc']) && $M['media_desc'] != ' ') ? $M['media_desc'] : '';
            $this->filename         = $M['media_filename'];
            $this->keywords         = (isset($M['media_keywords']) && $M['media_keywords'] != ' ') ? $M['media_keywords'] : '';
            $this->mime_ext         = $M['media_mime_ext'];
            $this->mime_type        = $M['mime_type'];
            $this->time             = $M['media_time'];
            $this->views            = $M['media_views'];
            $this->comments         = $M['media_comments'];
            $this->votes            = $M['media_votes'];
            $this->rating           = $M['media_rating'];
            $this->tn_attached      = $M['media_tn_attached'];
            $this->tn_image         = $M['media_tn_image'];
            $this->owner_id         = $M['media_user_id'];
            $this->upload_time      = $M['media_upload_time'];
            $this->cat_id           = $M['media_category'];
            $this->watermarked      = $M['media_watermarked'];
            $this->artist           = (isset($M['artist']) && $M['artist'] != ' ') ? $M['artist'] : '';
            $this->album            = (isset($M['album']) && $M['album'] != ' ') ? $M['album'] : '';
            $this->genre            = (isset($M['genre']) && $M['genre'] != ' ') ? $M['genre'] : '';
            $this->resolution_x     = $M['media_resolution_x'];
            $this->resolution_y     = $M['media_resolution_y'];
            $this->remote           = $M['remote_media'];
            $this->remote_url       = (isset($M['remote_url']) && $M['remote_url'] != ' ') ? $M['remote_url'] : '';
            $this->album_id         = $aid;
            $this->setAccessRights();
            $this->setMediaThumbnail();
        }
    }

    static public function hasAccess($owner_id, $group_id, $perm_owner, $perm_group, $perm_members, $perm_anon)
    {
        global $_USER, $_GROUPS;

        if (SEC_hasRights('mediagallery.admin') || SEC_inGroup('Root')) return 3;

        $uid = empty($_USER['uid']) ? 1 : $_USER['uid'];

        if ($uid == $owner_id) return $perm_owner;

        if (in_array($group_id, $_GROUPS)) return $perm_group;

        if ($uid == 1) return $perm_anon;

        return $perm_members;
    }

    private function setAccessRights()
    {
        global $_TABLES;

        $sql = "SELECT owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon "
             . "FROM {$_TABLES['mg_albums']} "
             . "WHERE album_id = " . $this->album_id;
        $result = DB_query($sql);
        $A = DB_fetchArray($result);
        $this->access = self::hasAccess($A['owner_id'], $A['group_id'], $A['perm_owner'],
                                        $A['perm_group'], $A['perm_members'], $A['perm_anon']);
    }

    private function setMediaThumbnail()
    {
        global $_TABLES, $_MG_CONF;

        $info = array(
            'media_type'        => $this->type,
            'mime_type'         => $this->mime_type,
            'media_filename'    => $this->filename,
            'media_mime_ext'    => $this->mime_ext,
            'remote_media'      => $this->remote_url,
            'media_tn_attached' => $this->tn_attached,
        );
        $tn_size = DB_getItem($_TABLES['mg_albums'], 'tn_size', 'album_id=' . $this->album_id);
        list($this->media_thumbnail,
             $this->media_thumbnail_file,
             $this->media_size) = self::getThumbInfo($info, $tn_size);
    }

    static public function getMediaExt($path_and_filename)
    {
        global $_MG_CONF;
        $retval = 'jpg';
        foreach ($_MG_CONF['validExtensions'] as $ext)
            if (file_exists($path_and_filename . $ext)) {
                $retval = $ext;
                break;
            }
        return $retval;
    }

    static public function getThumbInfo(&$info, $tn_size='')
    {
        global $_MG_CONF;

        if ($info['media_tn_attached'] == 1) {
            $pimage = self::getFilePath('tn', $info['media_filename'], 'jpg', 1);
            $image  = self::getFileUrl ('tn', $info['media_filename'], 'jpg', 1);
        } else {
            $fname = self::getDefaultThumbnail($info, $tn_size);
            $pimage = $_MG_CONF['path_mediaobjects']      . $fname;
            $image  = $_MG_CONF['mediaobjects_url'] . '/' . $fname;
        }
        $size = @getimagesize($pimage);
        if ($size == false) {
            $fname = 'missing.png';
            $pimage = $_MG_CONF['path_mediaobjects']      . $fname;
            $image  = $_MG_CONF['mediaobjects_url'] . '/' . $fname;
            $size = @getimagesize($pimage);
        }

        return array($image, $pimage, $size);
    }

    // get the default thumbnail
    static public function getDefaultThumbnail($info='', $tn_size='')
    {
        global $_MG_CONF;

        switch ($info['media_type']) {
            case 0:    // standard image
                $retval = 'generic.png';
                $filename = $info['media_filename'];
                if (!empty($filename)) {
                    $fname = 'tn/' . $filename[0] . '/' . $filename;

                    // testing!!
                    if ($tn_size !== '') {
                        $fname = self::getThumbPath($fname, $tn_size);
                        $fname = rtrim($fname, '.');
                    }

                    $ext = self::getMediaExt($_MG_CONF['path_mediaobjects'] . $fname);
                    if (!empty($ext)) {
                        $retval = $fname . $ext;
                    }
                }
                break;

            case 1:    // video file
                switch ($info['mime_type']) {
                    case 'video/x-flv':
                        $retval = 'flv.png';
                        break;
                    case 'application/x-shockwave-flash' :
                        $retval = 'flash.png';
                        break;
                    case 'video/mpeg':
                    case 'video/x-mpeg':
                    case 'video/x-mpeq2a':
                        if ($_MG_CONF['use_wmp_mpeg'] == 1) {
                            $retval = 'wmp.png';
                            break;
                        }
                    case 'video/x-motion-jpeg':
                    case 'video/quicktime':
                    case 'video/x-qtc':
                    case 'audio/mpeg':
                    case 'video/x-m4v':
                        $retval = 'quicktime.png';
                        break;
                    case 'asf':
                    case 'video/x-ms-asf':
                    case 'video/x-ms-asf-plugin':
                    case 'video/avi':
                    case 'video/msvideo':
                    case 'video/x-msvideo':
                    case 'video/avs-video':
                    case 'video/x-ms-wmv':
                    case 'video/x-ms-wvx':
                    case 'video/x-ms-wm':
                    case 'application/x-troff-msvideo':
                    case 'application/x-ms-wmz':
                    case 'application/x-ms-wmd':
                        $retval = 'wmp.png';
                        break;
                    default :
                        $retval = 'video.png';
                        break;
                }
                break;

            case 2:    // music file
                $retval = 'audio.png';
                break;

            case 4:    // other files
                switch ($info['mime_type']) {
                    case 'application/zip':
                    case 'application/x-compressed':
                    case 'application/x-gzip':
                    case 'multipart/x-gzip':
                    case 'application/arj':
                        $retval = 'zip.png';
                        break;
                    case 'application/pdf':
                        $retval = 'pdf.png';
                        break;
                    default :
                        $retval = 'generic.png';
                        $ext = $info['media_mime_ext'];
                        if (!empty($ext)) {
                            if (isset($_MG_CONF['dt'][$ext])) {
                                $retval = $_MG_CONF['dt'][$ext];
                            } else {
                                switch ($ext) {
                                    case 'pdf':
                                        $retval = 'pdf.png';
                                        break;
                                    case 'zip':
                                    case 'arj':
                                    case 'rar':
                                    case 'gz':
                                        $retval = 'zip.png';
                                        break;
                                }
                            }
                        }
                        break;
                }
                break;

            case 5:
            case 'embed':
                $retval = 'remote.png';
                if (!empty($info['remote_media'])) {
                    if (preg_match("/youtube/i", $info['remote_media'])) {
                        $retval = 'youtube.png';
                    } else if (preg_match("/google/i", $info['remote_media'])) {
                        $retval = 'googlevideo.png';
                    }
                }
                break;

            default:
                $retval = 'missing.png';
                break;
        }

        return $retval;
    }

    // Testing!
    static public function getThumbPath($path, $tn_size)
    {
        switch ($tn_size) {
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

    static public function getFilePath($type, $filename, $ext = '', $atttn = 0)
    {
        global $_MG_CONF;

        $tn = ($atttn == 1) ? 'tn_' : '';

        $path_and_filename = $_MG_CONF['path_mediaobjects'] . $type
                           . '/' . $filename[0] . '/' . $tn . $filename;
        if ($atttn == 1) {
            $ext = 'jpg';
        } else if (empty($ext)) {
            $ext = ltrim(self::getMediaExt($path_and_filename), '.');
        }

        return $path_and_filename . '.' . $ext;
    }

    static public function getFileUrl($type, $filename, $ext = '', $atttn = 0)
    {
        global $_MG_CONF;

        $tn = ($atttn == 1) ? 'tn_' : '';

        $tmpstr = $type . '/' . $filename[0] . '/' . $tn . $filename;
        $path_and_filename = $_MG_CONF['path_mediaobjects'] . $tmpstr;

        if ($atttn == 1) {
            $ext = 'jpg';
        } else if (empty($ext)) {
            $ext = ltrim(self::getMediaExt($path_and_filename), '.');
        }

        return $_MG_CONF['mediaobjects_url'] . '/' . $tmpstr . '.' . $ext;
    }

    public function displayThumb($opt = array())
    {
        global $_USER, $_CONF, $_MG_CONF, $_TABLES, $LANG_MG03, $LANG_MG01;

        $sortOrder  = isset($opt['sortorder'])  ? $opt['sortorder']  : 0;
        $searchmode = isset($opt['searchmode']) ? $opt['searchmode'] : 0;
        $album      = isset($opt['album_id'])   ? $opt['album_id']   : NULL;
        $mode       = isset($opt['imageonly'])  ? $opt['imageonly']  : 0; // $mode = 1 return image only

        if ($album === NULL) {
            $album = new mgAlbum($this->album_id);
        }

        $type = $this->type;

        $_MG_USERPREFS = MG_getUserPrefs();

        // $type == 1 video
        // $type == 2 audio
        if (($type == 1 || $type == 2 || $type == 5)
                && ($album->playback_type == 0 || $album->playback_type == 1)
                && $_MG_CONF['popup_from_album'] == 1) {

            if ($album->playback_type == 0) {
                if ($type == 2){
                    // determine what type of player we will use (WMP, QT or Flash)
                    $player = $_MG_CONF['mp3_player'];
                    if (isset($_MG_USERPREFS['mp3_player']) && $_MG_USERPREFS['mp3_player'] != -1) {
                        $player = $_MG_USERPREFS['mp3_player'];
                    }
                    switch ($player) {
                        case 0 :    // WMP
                            $new_y = 260;
                            $new_x = 340;
                            break;
                        case 1 :    // QT
                            $new_y = 25;
                            $new_x = 350;
                            break;
                        case 2 :
                            $new_y = 360;
                            $new_x = 580;
                            break;
                    }
                    if ($this->mime_type == 'audio/mpeg') {
                        $new_y = 360;
                        $new_x = 580;
                    }
                    if ($this->tn_attached == 1 && $player != 2) {
                        $tnsize = $this->media_size;
                        $new_y += $tnsize[0];
                        if ($tnsize[1] > $new_x) {
                            $new_x = $tnsize[1];
                        }
                    }
                    if ($album->playback_type == 0) {
                        $url_display_item = self::getHref_showvideo($this->id, $new_y, $new_x);
                    } else {
                        $url_display_item = $_MG_CONF['site_url'] . '/download.php?mid=' . $this->id;
                    }
                    $resolution_x = $new_x;
                    $resolution_y = $new_y;
                } else { // must be a video...

                    $playback_options['height'] = $_MG_CONF['swf_height'];
                    $playback_options['width']  = $_MG_CONF['swf_width'];
                    $poResult = DB_query("SELECT * FROM {$_TABLES['mg_playback_options']} "
                                       . "WHERE media_id='" . addslashes($this->id) . "'");
                    while ($poRow = DB_fetchArray($poResult)) {
                        $playback_options[$poRow['option_name']] = $poRow['option_value'];
                    }

                    if ($this->resolution_x > 0) {
                        $resolution_x = $this->resolution_x;
                        $resolution_y = $this->resolution_y;
                    } else {
                        if ($this->resolution_x == 0 && $this->remote_media != 1) {
                            $filepath = self::getFilePath('orig', $this->filename, $this->mime_ext);
                            $size = @filesize($filepath);
                            
                            // skip files over 8M in size..
                            if ($size < 8388608) {
                                list($resolution_x, $resolution_y) = self::getResolutionID3($filepath);
                            }
                        } else {
                            $resolution_x = $this->resolution_x;
                            $resolution_y = $this->resolution_y;
                        }
                    }

                    $resolution_x = $playback_options['width'];
                    $resolution_y = $playback_options['height'];
                    if ($resolution_x < 1 || $resolution_y < 1) {
                        $resolution_x = 480;
                        $resolution_y = 320;
                    } else {
                        $resolution_x = $resolution_x + 40;
                        $resolution_y = $resolution_y + 40;
                    }
                    if ($this->mime_type == 'video/x-flv' && $_MG_CONF['use_flowplayer'] != 1) {
                        $resolution_x = $resolution_x + 60;
                        if ($resolution_x < 590) {
                            $resolution_x = 590;
                        }
                        $resolution_y = $resolution_y + 80;
                        if ($resolution_y < 500) {
                            $resolution_y = 500;
                        }
                    }
                    if ($type == 5) {
                        $resolution_x = 460;
                        $resolution_y = 380;
                    }
                    $url_display_item = self::getHref_showvideo($this->id, $resolution_y, $resolution_x);
                }
            } else {
                $url_display_item = $_MG_CONF['site_url'] . '/download.php?mid=' . $this->id;
            }
        } else {
            if ($album->useAlternate == 1 && $type != 5 && !empty($this->remote_url)) {
                $url_display_item = $this->remote_url;
            } else {
                $url_display_item = $_MG_CONF['site_url'] . '/media.php?f=0' . '&amp;sort=' . $sortOrder . '&amp;s=' . $this->id ;
            }
        }

        $url_media_item = $url_display_item;

        // -- decide what thumbnail size to use, small, medium, large...

        if (isset($_MG_USERPREFS['tn_size']) && $_MG_USERPREFS['tn_size'] != -1) {
            $tn_size = $_MG_USERPREFS['tn_size'];
        } else {
            if ($searchmode == 1) {
                $tn_size = $_MG_CONF['search_tn_size'];
            } else {
                $tn_size = $album->tn_size;
            }
        }

        list($tn_width, $tn_height) = self::getTNSize($tn_size, $album->tnWidth, $album->tnHeight);

        list($newwidth, $newheight) = self::getImageWH($this->media_size[0], $this->media_size[1], $tn_width, $tn_height);
        if (!isset($resolution_x)) {
            $resolution_x = $newwidth;
        }
        if (!isset($resolution_y)) {
            $resolution_y = $newheight;
        }

        $username = 'anonymous';
        if ($this->owner_id != '' && $this->owner_id > 1) {
            $username = DB_getItem($_TABLES['users'], 'username', "uid=" . intval($this->owner_id));
        }

        $filepath = self::getFilePath('orig', $this->filename, $this->mime_ext);
        $fs_bytes = @filesize($filepath);

        $fileSize = MG_getSize($fs_bytes);

        $direct_url = self::getFileUrl('disp', $this->filename, $this->mime_ext);
        $direct_path = self::getFilePath('disp', $this->filename, $this->mime_ext);
        if (!file_exists($direct_path)) {
            $direct_url = self::getFileUrl('disp', $this->filename, 'jpg');
        }

        $edit_item = '';
        if ($album->access == 3) {
            $edit_item = '<a href="' . $_MG_CONF['site_url']
                       . '/admin.php?mode=mediaedit&amp;s=1&amp;album_id=' . $this->album_id
                       . '&amp;mid=' . $this->id . '">' . $LANG_MG01['edit'] . '</a>';
        }

        // build the small rating bar
        $rating_box = '';
        if ($album->enable_rating > 0) {
            require_once $_CONF['path'] . 'plugins/mediagallery/include/lib-rating.php';
            $starSize = ($_MG_CONF['use_large_stars'] == 1) ? '' : 'sm';
            $rating_box = MG_getRatingBar(
                $album->enable_rating,
                $this->owner_id,
                $this->id,
                $this->votes,
                $this->rating,
                $starSize
            );
        }

        $hrefdirect = '';
        if ($this->type == 0) { // standard image
            if ($this->remote == 1) {
                $hrefdirect = $this->remote_url;
            } else {
                $hrefdirect = $direct_url;
            }
        }
        $caption = PLG_replaceTags(str_replace('$', '&#36;', $this->title));

        if ($searchmode == 1) {
            $templatePath = MG_getTemplatePath_byName($_MG_CONF['search_album_skin']);
        } else {
            $templatePath = MG_getTemplatePath($this->album_id);
        }
        $L = COM_newTemplate($templatePath);
        $L->set_file('media_link','medialink.thtml');
        $L->set_var(array(
            'hrefdirect' => $hrefdirect,
            'href'       => $url_media_item,
            'caption'    => $caption,
            'id'         => 'id' . rand(),
        ));
        $media_start_link = $L->finish($L->parse('media_link_start', 'media_link'));

        if ($searchmode == 1) {
            $skin = $_MG_CONF['search_frame_skin'];
            $info = array(
                'media_type'        => $this->type,
                'mime_type'         => $this->mime_type,
                'media_filename'    => $this->filename,
                'media_mime_ext'    => $this->mime_ext,
                'remote_media'      => $this->remote_url,
                'media_tn_attached' => $this->tn_attached,
            );
            list($media_thumbnail,
                 $media_thumbnail_file,
                 $media_size) = self::getThumbInfo($info, $tn_size);
        } else {
            $skin = $album->image_skin;
            $media_thumbnail = $this->media_thumbnail;
        }
        $media_item_thumbnail = MG_getFramedImage($skin, $this->title, $url_media_item,
                                                  $media_thumbnail, $newwidth, $newheight, $media_start_link);

        if ($mode == 1) {
            return $media_item_thumbnail;
        }

        $edit_link = '';
        if (($type == 1 || $type == 2 || $type == 5)
                && ($album->playback_type == 0 || $album->playback_type == 1)
                && $_MG_CONF['popup_from_album'] == 1) {
            // check to see if comments and rating are enabled, if not, put a link to edit...
            if ($album->access == 3) {
                $edit_link = '<br' . XHTML . '><a href="' . $_MG_CONF['site_url']
                           . '/admin.php?mode=mediaedit&amp;s=1&amp;album_id=' . $this->album_id
                           . '&amp;mid=' . $this->id . '">' . $LANG_MG01['edit'] . '</a>';
            }
        }

        if ($_MG_CONF['use_upload_time'] == 1) {
            $media_time = MG_getUserDateTimeFormat($this->upload_time);
        } else {
            $media_time = MG_getUserDateTimeFormat($this->time);
        }

        $media_title = (!empty($this->title)) ? PLG_replaceTags($this->title) : 'No Name';

        $T = COM_newTemplate($templatePath);
        $T->set_file(array(
            'media_cell_image' => 'album_page_media_cell.thtml',
            'mp3_podcast'      => 'mp3_podcast.thtml',
        ));

        if ($this->mime_type == 'audio/mpeg' && $album->mp3ribbon) {
            $T->set_var(array(
                'mp3_file' => self::getFileUrl('orig', $this->filename, $this->mime_ext),
                'site_url' => $_MG_CONF['site_url'],
                'id'       => $this->mime_ext . rand(),
            ));
            $T->parse('mp3_podcast', 'mp3_podcast');
        } else {
            $T->set_var('mp3_podcast', '');
        }

        $T->set_var(array(
            'edit_link'         => $edit_link,
            'play_now'          => '',
            'download_now'      => $_MG_CONF['site_url'] . '/download.php?mid=' . $this->id,
            'play_in_popup'     => self::getHref_showvideo($this->id, $resolution_y, $resolution_x),
            'row_height'        => $tn_height,
            'media_title'       => $media_title,
            'media_description' => PLG_replaceTags(nl2br($this->description)),
            'media_tag'         => strip_tags($this->title),
            'media_time'        => $media_time[0],
            'media_owner'       => $username,
            'media_item_thumbnail' => $media_item_thumbnail,
            'site_url'          => $_MG_CONF['site_url'],
            'lang_published'    => $LANG_MG03['published'],
            'lang_on'           => $LANG_MG03['on'],
            'lang_hyphen'       => $this->album == '' ? '' : '-',
            'media_link_start'  => $media_start_link,
            'media_link_end'    => '</a>',
            'artist'            => $this->artist,
            'musicalbum'        => $this->album != '' ? $this->album : '',
            'genre'             => $this->genre != '' ? $this->genre : '',
            'alt_edit_link'     => $edit_item,
            'filesize'          => $fileSize,
            'media_id'          => $this->id,
            'rating_box'        => $rating_box,
        ));

        if ($album->enable_keywords) {
            if (!empty($this->keywords)) {
                $kwText = '';
                $keyWords = array();
                $keyWords = explode(' ', $this->keywords);
                $numKeyWords = count($keyWords);
                for ($i=0; $i<$numKeyWords; $i++) {
                    $keyWords[$i] = str_replace('"', ' ', $keyWords[$i]);
                    $searchKeyword = $keyWords[$i];
                    $keyWords[$i] = str_replace('_', ' ', $keyWords[$i]);
                    $kwText .= '<a href="' . $_MG_CONF['site_url']
                             . '/search.php?mode=search&amp;swhere=1&amp;keywords=' . $searchKeyword
                             . '&amp;keyType=any">' . $keyWords[$i] . '</a>';
                }
                $T->set_var(array(
                    'enable_keywords' => 1,
                    'media_keywords'  => $kwText,
                    'lang_keywords'   => $LANG_MG01['keywords'],
                ));
            } else {
                $T->set_var('lang_keywords', '');
            }
        } else {
            $T->set_var(array(
                'enable_keywords'     => '',
                'lang_keywords'       => '',
            ));
        }

        if ($album->enable_comments) {
            $link = '<a href="' . $_MG_CONF['site_url'] . '/media.php?f=0'
                  . '&amp;sort=' . $sortOrder
                  . '&amp;s=' . $this->id . '">'
                  . $LANG_MG03['comments'] . '</a>';
            $cmtLink = $LANG_MG03['comments'];
            $cmtLink_alt  = $link;
            if ($type == 4 ||
                    ($type == 1 && $album->playback_type != 2) ||
                    ($type == 2 && $album->playback_type != 2) ||
                    ($type == 5 && $album->playback_type != 2)) {
                $cmtLink  = $link;
                $cmtLink_alt = '';
            }
            $T->set_var(array(
                'media_comments_count' => $this->comments,
                'lang_comments'        => $cmtLink,
                'lang_comments_hot'    => $cmtLink_alt,
            ));

            $T->set_var('media_comments', $album->enable_comments);
        }

        if ($album->enable_views) {
            $T->set_var(array(
                'media_views_count' => $this->views,
                'lang_views'        => $LANG_MG03['views']
            ));
            $T->set_var('media_views', $album->enable_views);
        }
        PLG_templateSetVars('mediagallery', $T);

        return $T->finish($T->parse('media_cell', 'media_cell_image'));
    }

    function displayRawThumb($namesOnly=0)
    {
        if ($namesOnly == 1) {
            return array($this->media_thumbnail, $this->media_thumbnail_file);
        }

        list($newwidth, $newheight) = self::getImageWH($this->media_size[0], $this->media_size[1], 100, 100);
        $media_dim = 'width="' . $newwidth . '" height="' . $newheight . '"';
        $title = strip_tags($this->title);
        return '<img src="' .$this->media_thumbnail . '" ' . $media_dim
               . ' style="border:none;" alt="' . $title . '" title="' . $title . '"' . XHTML . '>';
    }

    static public function getTNSize($val, $custom_width=0, $custom_height=0)
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

    static public function getImageWH($imgwidth, $imgheight, $maxwidth, $maxheight, $stretch=true)
    {
        if ($imgwidth > $maxwidth || $imgheight > $maxheight) {

            $ratio_width  = $imgwidth / $maxwidth;
            $ratio_height = $imgheight / $maxheight;
            if ($ratio_width > $ratio_height) {
                $newwidth = $maxwidth;
                $newheight = round($imgheight / $ratio_width);
            } else {
                $newheight = $maxheight;
                $newwidth = round($imgwidth / $ratio_height);
            }

        } else {
            if ($stretch == true) {

                if ($imgwidth > $imgheight) {
                    $ratio_width  = $imgwidth / $maxwidth;
                    $newwidth = $maxwidth;
                    $newheight = round($imgheight / $ratio_width);
                } else {
                    $ratio_height = $imgheight / $maxheight;
                    $newheight = $maxheight;
                    $newwidth = round($imgwidth / $ratio_height);
                }

            } else {
                $newwidth  = $imgwidth;
                $newheight = $imgheight;
            }
        }
        return array($newwidth, $newheight);
    }

    static public function getHref_showvideo($mid, $height, $width, $mqueue=0)
    {
        global $_MG_CONF;

        $queue = ($mqueue == 1) ? '&amp;s=q' : '';
        return "javascript:showVideo('" . $_MG_CONF['site_url']
             . '/view.php?n=' . $mid . $queue . "'," . $height . ',' . $width . ')';
    }

    static public function getID3($filepath)
    {
        global $_CONF;

        // include getID3() library
        require_once $_CONF['path'] . 'plugins/mediagallery/include/lib/getid3/getid3.php';
        // Needed for windows only
        if (!defined('GETID3_HELPERAPPSDIR')) {
            define('GETID3_HELPERAPPSDIR', 'C:/helperapps/');
        }

        $getID3 = new getID3;

        // Analyze file and store returned data in $FileInfo
        $FileInfo = $getID3->analyze($filepath);
        getid3_lib::CopyTagsToComments($FileInfo);

        if (isset($FileInfo['error'])) {
            if (is_array($FileInfo['error'])) {
                foreach ($FileInfo['error'] AS $error) {
                    COM_errorLog("Media::getID3: " . $error);
                }
            }
        }

        return $FileInfo;
    }

    static public function getResolutionID3($filepath)
    {
        global $_TABLES;

        $FileInfo = self::getID3($filepath);

        $resolution_x = $FileInfo['video']['resolution_x'];
        $resolution_y = $FileInfo['video']['resolution_y'];
        if ($resolution_x < 1 || $resolution_y < 1) {
            $resolution_x = -1;
            $resolution_y = -1;
            if (isset($FileInfo['meta']['onMetaData']['width']) &&
                isset($FileInfo['meta']['onMetaData']['height'])) {
                $resolution_x = $FileInfo['meta']['onMetaData']['width'];
                $resolution_y = $FileInfo['meta']['onMetaData']['height'];
            }
        }
        if ($resolution_x != 0) {
            $sql = "UPDATE " . $_TABLES['mg_media']
                 . " SET media_resolution_x=" . intval($resolution_x)
                     . ",media_resolution_y=" . intval($resolution_y)
                 . " WHERE media_id='" . addslashes($I['media_id']) . "'";
            DB_query($sql);
        }

        return array($resolution_x, $resolution_y);
    }
}
?>