<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | classAlbum.php                                                           |
// |                                                                          |
// | Album class                                                              |
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

class mgAlbum {

    var $id;
    var $title;
    var $description;
    var $parent;
    var $order;
    var $hidden;
    var $cover;
    var $cover_filename;
    var $media_count;
    var $album_disk_usage;
    var $last_update;
    var $views;
    var $podcast;
    var $mp3ribbon;
    var $display_album_desc;
    var $enable_album_views;
    var $album_view_type;
    var $image_skin;
    var $album_skin;
    var $display_skin;
    var $enable_comments;
    var $exif_display;
    var $enable_rating;
    var $playback_type;
    var $tn_attached;
    var $enable_slideshow;
    var $enable_random;
    var $enable_views;
    var $enable_keywords;
    var $enable_sort;
    var $enable_rss;
    var $albums_first;
    var $allow_download;
    var $full;
    var $tn_size;
    var $max_image_height;
    var $max_image_width;
    var $max_filesize;
    var $display_image_size;
    var $display_rows;
    var $display_columns;
    var $valid_formats;
    var $filename_title;
    var $shopping_cart;
    var $wm_auto;
    var $wm_id;
    var $wm_opacity;
    var $wm_location;
    var $album_sort_order;
    var $member_uploads;
    var $moderate;
    var $email_mod;
    var $featured;
    var $cbposition;
    var $cbpage;
    var $owner_id;
    var $group_id;
    var $mod_group_id;
    var $perm_owner;
    var $perm_group;
    var $perm_members;
    var $perm_anon;
    var $access;
    var $children;
    var $tnHeight;
    var $tnWidth;
    var $useAlternate;
    var $skin;
    var $rssChildren;
    var $valid;

    function mgAlbum($album_id = NULL, $groups = array())
    {
        $this->valid = true;

        if ($album_id === NULL) {
            $this->init();
            $this->id = $this->createAlbumID();
        } else {
            $album_id = intval($album_id);
        }

        if ($album_id === 0) { // root album
            $this->init();
            $this->_init_root();
        }

        if ($album_id > 0) {
            $this->loadFromDB($album_id, $groups);
        }
    }

    function init()
    {
        global $_MG_CONF, $_TABLES, $_USER;

        $this->children             = NULL;
        $this->id                   = NULL;
        $this->title                = '';
        $this->description          = '';
        $this->parent               = 0;
        $this->order                = 0;
        $this->hidden               = 0;
        $this->cover                = '-1';
        $this->cover_filename       = '';
        $this->media_count          = 0;
        $this->album_disk_usage     = 0;
        $this->last_update          = 0;
        $this->views                = 0;
        $this->podcast              = isset($_MG_CONF['ad_podcast']) ? $_MG_CONF['ad_podcast'] : 0;
        $this->mp3ribbon            = isset($_MG_CONF['ad_mp3ribbon']) ? $_MG_CONF['ad_mp3ribbon'] : 0;
        $this->enable_album_views   = $_MG_CONF['ad_enable_album_views'];
        $this->image_skin           = $_MG_CONF['ad_image_skin'];
        $this->album_skin           = $_MG_CONF['ad_album_skin'];
        $this->display_skin         = $_MG_CONF['ad_display_skin'];
        $this->enable_comments      = $_MG_CONF['ad_enable_comments'];
        $this->exif_display         = $_MG_CONF['ad_exif_display'];
        $this->enable_rating        = $_MG_CONF['ad_enable_rating'];
        $this->playback_type        = $_MG_CONF['ad_playback_type'];
        $this->tn_attached          = 0;
        $this->enable_slideshow     = $_MG_CONF['ad_enable_slideshow'];
        $this->enable_random        = $_MG_CONF['ad_enable_random'];
        $this->enable_views         = $_MG_CONF['ad_enable_views'];
        $this->enable_keywords      = $_MG_CONF['ad_enable_keywords'];
        $this->display_album_desc   = $_MG_CONF['ad_display_album_desc'];
        $this->enable_sort          = $_MG_CONF['ad_enable_sort'];
        $this->enable_rss           = $_MG_CONF['ad_enable_rss'];
        $this->albums_first         = $_MG_CONF['ad_albums_first'];
        $this->allow_download       = $_MG_CONF['ad_allow_download'];
        $this->full                 = $_MG_CONF['ad_full_display'];
        $this->tn_size              = $_MG_CONF['ad_tn_size'];
        $this->tnHeight             = isset($_MG_CONF['ad_tn_height']) ? $_MG_CONF['ad_tn_height'] : 0;
        $this->tnWidth              = isset($_MG_CONF['ad_tn_width'])  ? $_MG_CONF['ad_tn_width']  : 0;
        $this->max_image_height     = $_MG_CONF['ad_max_image_height'];
        $this->max_image_width      = $_MG_CONF['ad_max_image_width'];
        $this->max_filesize         = $_MG_CONF['ad_max_filesize'];
        $this->display_image_size   = $_MG_CONF['ad_display_image_size'];
        $this->display_rows         = intval($_MG_CONF['ad_display_rows']);
        $this->display_columns      = intval($_MG_CONF['ad_display_columns']);
        $this->valid_formats        = $_MG_CONF['ad_valid_formats'];
        $this->filename_title       = $_MG_CONF['ad_filename_title'];
        $this->shopping_cart        = 0;
        $this->wm_auto              = $_MG_CONF['ad_wm_auto'];
        $this->wm_id                = $_MG_CONF['ad_wm_id'];
        $this->wm_opacity           = $_MG_CONF['ad_wm_opacity'];
        $this->wm_location          = $_MG_CONF['ad_wm_location'];
        $this->album_sort_order     = $_MG_CONF['ad_album_sort_order'];
        $this->member_uploads       = $_MG_CONF['ad_member_uploads'];
        $this->moderate             = $_MG_CONF['ad_moderate'];
        $this->email_mod            = $_MG_CONF['ad_email_mod'];
        $this->featured             = 0;
        $this->cbposition           = 0;
        $this->cbpage               = 'all';
        $this->owner_id             = $_USER['uid'];

        $this->group_id             = $_MG_CONF['ad_group_id'];
        $this->mod_group_id         = $_MG_CONF['ad_mod_group_id'];

        $grp_id = DB_getItem($_TABLES['groups'], 'grp_id', "grp_name = 'mediagallery Admin'");
        $this->group_id             = isset($_MG_CONF['ad_group_id'])     ? $_MG_CONF['ad_group_id']     : $grp_id;
        $this->mod_group_id         = isset($_MG_CONF['ad_mod_group_id']) ? $_MG_CONF['ad_mod_group_id'] : $grp_id;

        $this->perm_owner           = $_MG_CONF['ad_permissions'][0];
        $this->perm_group           = $_MG_CONF['ad_permissions'][1];
        $this->perm_members         = $_MG_CONF['ad_permissions'][2];
        $this->perm_anon            = $_MG_CONF['ad_permissions'][3];
        $this->useAlternate         = isset($_MG_CONF['ad_use_alternate']) ? $_MG_CONF['ad_use_alternate'] : 0;
        $this->skin                 = isset($_MG_CONF['ad_skin']) ? $_MG_CONF['ad_skin'] : 'default';
        $this->rssChildren          = isset($_MG_CONF['ad_rsschildren']) ? $_MG_CONF['ad_rsschildren'] : 0;

        if (!SEC_hasRights('mediagallery.admin')) {
            $this->perm_members     = $_MG_CONF['member_perm_members'];
            $this->perm_anon        = $_MG_CONF['member_perm_anon'];
        }
    }

    function loadFromDB($album_id)
    {
        global $_TABLES;

        $sql = "SELECT * FROM {$_TABLES['mg_albums']} WHERE album_id = " . intval($album_id);
        $result = DB_query($sql);
        if (DB_numRows($result) == 1) {
            $album = DB_fetchArray($result);
            $this->id                 = $album['album_id'];
            $this->title              = (!empty($album['album_title']) && $album['album_title'] != ' ') ? $album['album_title'] : '';
            $this->parent             = $album['album_parent'];
            $this->description        = (!empty($album['album_desc']) && $album['album_desc'] != ' ') ? $album['album_desc'] : '';
            $this->order              = $album['album_order'];
            $this->hidden             = $album['hidden'];
            $this->podcast            = $album['podcast'];
            $this->mp3ribbon          = $album['mp3ribbon'];
            $this->cover              = $album['album_cover'];
            $this->cover_filename     = $album['album_cover_filename'];
            $this->media_count        = $album['media_count'];
            $this->album_disk_usage   = $album['album_disk_usage'];
            $this->last_update        = $album['last_update'];
            $this->views              = $album['album_views'];
            $this->enable_album_views = $album['enable_album_views'];
            $this->image_skin         = $album['image_skin'];
            $this->album_skin         = $album['album_skin'];
            $this->display_skin       = $album['display_skin'];
            $this->display_album_desc = $album['display_album_desc'];
            $this->enable_comments    = $album['enable_comments'];
            $this->exif_display       = $album['exif_display'];
            $this->enable_rating      = $album['enable_rating'];
            $this->playback_type      = $album['playback_type'];
            $this->tn_attached        = $album['tn_attached'];
            $this->enable_slideshow   = $album['enable_slideshow'];
            $this->enable_random      = $album['enable_random'];
            $this->enable_views       = $album['enable_views'];
            $this->enable_keywords    = $album['enable_keywords'];
            $this->enable_sort        = $album['enable_sort'];
            $this->enable_rss         = $album['enable_rss'];
            $this->albums_first       = $album['albums_first'];
            $this->allow_download     = $album['allow_download'];
            $this->full               = $album['full_display'];
            $this->tn_size            = $album['tn_size'];
            $this->max_image_height   = $album['max_image_height'];
            $this->max_image_width    = $album['max_image_width'];
            $this->max_filesize       = $album['max_filesize'];
            $this->display_image_size = $album['display_image_size'];
            $this->display_rows       = intval($album['display_rows']);
            $this->display_columns    = intval($album['display_columns']);
            $this->valid_formats      = $album['valid_formats'];
            $this->filename_title     = $album['filename_title'];
            $this->shopping_cart      = 0;
            $this->wm_auto            = $album['wm_auto'];
            $this->wm_id              = $album['wm_id'];
            $this->wm_opacity         = $album['opacity'];
            $this->wm_location        = $album['wm_location'];
            $this->album_sort_order   = $album['album_sort_order'];
            $this->member_uploads     = $album['member_uploads'];
            $this->moderate           = $album['moderate'];
            $this->email_mod          = $album['email_mod'];
            $this->featured           = $album['featured'];
            $this->cbposition         = $album['cbposition'];
            $this->cbpage             = $album['cbpage'];
            $this->owner_id           = $album['owner_id'];
            $this->group_id           = $album['group_id'];
            $this->mod_group_id       = $album['mod_group_id'];
            $this->perm_owner         = $album['perm_owner'];
            $this->perm_group         = $album['perm_group'];
            $this->perm_members       = $album['perm_members'];
            $this->perm_anon          = $album['perm_anon'];
            $this->tnHeight           = $album['tnheight'];
            $this->tnWidth            = $album['tnwidth'];
            $this->useAlternate       = $album['usealternate'];
            $this->skin               = isset($album['skin']) ? $album['skin'] : 'default';
            $this->rssChildren        = isset($album['rsschildren']) ? $album['rsschildren'] : 0;

            $this->access = self::hasAccess($this->owner_id,
                                            $this->group_id,
                                            $this->perm_owner,
                                            $this->perm_group,
                                            $this->perm_members,
                                            $this->perm_anon);
        } else {
            $this->valid = false;
        }
    }

    function _init_root()
    {
        global $_MG_CONF;

        $this->id           = 0;
        $this->title        = $_MG_CONF['root_album_name'];
        $this->owner_id     = SEC_hasRights('mediagallery.admin');
        $this->group_id     = SEC_inGroup('Root');
        $this->parent       = 0;
        $this->skin         = isset($_MG_CONF['indextheme']) ? $_MG_CONF['indextheme'] : 'default';
        $this->album_skin   = isset($_MG_CONF['indexskin'])  ? $_MG_CONF['indexskin']  : 'default';

        $this->tn_size      = $_MG_CONF['gallery_tn_size'];
        $this->tnHeight     = isset($_MG_CONF['gallery_tn_height']) ? $_MG_CONF['gallery_tn_height'] : 0;
        $this->tnWidth      = isset($_MG_CONF['gallery_tn_width'])  ? $_MG_CONF['gallery_tn_width']  : 0;

        $this->display_rows    = intval($_MG_CONF['album_display_rows']);
        $this->display_columns = intval($_MG_CONF['album_display_columns']);

        $this->perm_owner   = $_MG_CONF['ad_permissions'][0];
        $this->perm_group   = $_MG_CONF['ad_permissions'][1];
        $this->perm_members = $_MG_CONF['ad_permissions'][2];
        $this->perm_anon    = $_MG_CONF['ad_permissions'][3];

        if ($this->owner_id) {
            $this->access = 3;
        } else {
            $this->access = self::hasAccess($this->owner_id,
                                            $this->group_id,
                                            $this->perm_owner,
                                            $this->perm_group,
                                            $this->perm_members,
                                            $this->perm_anon);
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

    function saveAlbum()
    {
        global $_TABLES;

        $this->album_disk_usage = intval($this->album_disk_usage);
        $this->last_update      = intval($this->last_update);
        $this->views            = intval($this->views);
        $this->enable_keywords  = intval($this->enable_keywords);
        $title         = addslashes($this->title);
        $description   = addslashes($this->description);
        $sqlFieldList  = 'album_id,album_title,album_desc,album_parent,album_order,skin,hidden,album_cover,album_cover_filename,media_count,album_disk_usage,last_update,album_views,display_album_desc,enable_album_views,image_skin,album_skin,display_skin,enable_comments,exif_display,enable_rating,playback_type,tn_attached,enable_slideshow,enable_random,enable_views,enable_keywords,enable_sort,enable_rss,albums_first,allow_download,full_display,tn_size,max_image_height,max_image_width,max_filesize,display_image_size,display_rows,display_columns,valid_formats,filename_title,shopping_cart,wm_auto,wm_id,opacity,wm_location,album_sort_order,member_uploads,moderate,email_mod,featured,cbposition,cbpage,owner_id,group_id,mod_group_id,perm_owner,perm_group,perm_members,perm_anon,podcast,mp3ribbon,tnheight,tnwidth,usealternate,rsschildren';
        $sqlDataValues = "$this->id,'$title','$description',$this->parent,$this->order,'$this->skin',$this->hidden,'$this->cover','$this->cover_filename',$this->media_count,$this->album_disk_usage,$this->last_update,$this->views,$this->display_album_desc,$this->enable_album_views,'$this->image_skin','$this->album_skin','$this->display_skin',$this->enable_comments,$this->exif_display,$this->enable_rating,$this->playback_type,$this->tn_attached,$this->enable_slideshow,$this->enable_random,$this->enable_views,$this->enable_keywords,$this->enable_sort,$this->enable_rss,$this->albums_first,$this->allow_download,$this->full,$this->tn_size,$this->max_image_height,$this->max_image_width,$this->max_filesize,$this->display_image_size,$this->display_rows,$this->display_columns,$this->valid_formats,$this->filename_title,$this->shopping_cart,$this->wm_auto,$this->wm_id,$this->wm_opacity,$this->wm_location,$this->album_sort_order,$this->member_uploads,$this->moderate,$this->email_mod,$this->featured,$this->cbposition,'$this->cbpage',$this->owner_id,$this->group_id,$this->mod_group_id,$this->perm_owner,$this->perm_group,$this->perm_members,$this->perm_anon,$this->podcast,$this->mp3ribbon,$this->tnHeight,$this->tnWidth,$this->useAlternate,$this->rssChildren";
        DB_save($_TABLES['mg_albums'], $sqlFieldList, $sqlDataValues);
    }

    function createAlbumID()
    {
        global $_TABLES;

        $sql = "SELECT MAX(album_id) AS max_album_id FROM " . $_TABLES['mg_albums'];
        $result = DB_query($sql);
        $row = DB_fetchArray($result);
        $aid = $row['max_album_id'] + 1;
        return $aid;
    }

    function isMemberAlbum()
    {
        global $_TABLES, $_MG_CONF;

        if ($_MG_CONF['member_albums'] != 1) {
            return false;
        }
        if (SEC_hasRights('mediagallery.admin')) {
            return false;
        }
        if ($_MG_CONF['member_album_root'] == $this->parent) {
            return true;
        }
        if ($_MG_CONF['member_album_root'] == 0) {    // if root is the member root, everything will fall into
                                                      // the member album slot if they are enabled....
            return true;
        }

        // now walk up the chain and see if any parents are member root
        $parent = $this->parent;
        while ($parent != 0) {
            if ($parent == $_MG_CONF['member_album_root']) {
                return true;
            }
            $parent = DB_getItem($_TABLES['mg_albums'], 'album_parent', "album_id = " . intval($parent));
        }
        return false;
    }

    function getTopParent()
    {
        global $_TABLES;

        $a = $this->parent;
        $p = $this->parent;
        while ($p != 0) {
            $a = $p;
            $p = DB_getItem($_TABLES['mg_albums'], 'album_parent', "album_id = " . intval($p));
        }
        return $a;
    }

    function getOffset()
    {
        global $_TABLES;

        $offset = 0;
        $topParent = $this->getTopParent();

        if ($topParent == 0) {
            $pid = 0;
        } else {
            $temp = DB_getItem($_TABLES['mg_albums'], 'album_parent', "album_id = " . intval($topParent));
            if (isset($temp)) {
                $pid = $temp;
            } else {
                return -1;
            }
        }

        if ( $this->parent == 0 ) {
            $matchID = $this->id;
        } else {
            $matchID = $topParent;
        }

        $parent_album = new mgAlbum($pid);
        $children = $parent_album->getChildren();
//      $children = $this->getChildren($pid); // This doesn't work properly. Why?

        $idlist = implode(',', $children);
        if (empty($idlist)) return $offset - 1;
        $sql = "SELECT album_id,hidden,owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon "
             . "FROM {$_TABLES['mg_albums']} "
             . "WHERE album_id IN ($idlist) ORDER BY album_order DESC";
        $result = DB_query($sql);

        while ($A = DB_fetchArray($result)) {
            $access = self::hasAccess($A['owner_id'], $A['group_id'], $A['perm_owner'],
                                      $A['perm_group'], $A['perm_members'], $A['perm_anon']);
            if ($access == 0 || ($A['hidden'] && $access != 3)) {
                //no op
            } else {
                $offset++;
            }
            if ( $A['album_id'] == $matchID /*$this->id*/ ) {
                return $offset - 1;
            }
        }
        return $offset - 1;
    }

    function getNextSortOrder()
    {
        global $_TABLES;

        $sql = "SELECT MAX(album_order) + 10 AS nextalbum_order FROM " . $_TABLES['mg_albums'];
        $result = DB_query($sql);
        $row = DB_fetchArray($result);
        if ($row == NULL || $result == NULL) {
            $albumOrder = 10;
        } else {
            $albumOrder = $row['nextalbum_order'];
            if ($albumOrder < 0) {
                $albumOrder = 10;
            }
        }
        if ($albumOrder == NULL)
            $albumOrder = 10;
        return $albumOrder;
    }

    function updateChildPermissions($force_update)
    {
        global $_TABLES;

        if ($this->id == 0) return true;

        $children = $this->getChildren();
        foreach ($children as $child) {
            $child_album = new mgAlbum($child);
            $change = 0;
            if ($child_album->perm_owner > $this->perm_owner || $force_update) {
                $child_album->perm_owner = $this->perm_owner;
                $change = 1;
            }
            if ($child_album->perm_group > $this->perm_group || $force_update) {
                $child_album->perm_group = $this->perm_group;
                $change = 1;
            }
            if ($child_album->perm_members > $this->perm_members || $force_update) {
                $child_album->perm_members = $this->perm_members;
                $change = 1;
            }
            if ($child_album->perm_anon > $this->perm_anon || $force_update) {
                $child_album->perm_anon = $this->perm_anon;
                $change = 1;
            }
            if ($this->hidden || $force_update) {
                $child_album->hidden = $this->hidden;
                $change = 1;
            }
            if ($change == 1) {
                $child_album->saveAlbum();
            }
            $child_album->updateChildPermissions($force_update);
        }
        return true;
    }

    function _loadChildrenFromDB($id)
    {
        global $_TABLES;

        $sql = "SELECT album_id FROM {$_TABLES['mg_albums']} "
             . "WHERE album_parent = $id ORDER BY album_order DESC";
        $result = DB_query($sql);
        $children = array();
        while ($A = DB_fetchArray($result)) {
            $children[] = $A['album_id'];
        }
        return $children;
    }

    function getChildren($id = '')
    {
        if (empty($id) || $id == $this->id) {
            if (!isset($this->children)) {
                $this->children = $this->_loadChildrenFromDB($this->id);
            }
            return $this->children;
        }
        return $this->_loadChildrenFromDB($id);
    }

    function getChildrenVisible($id = '')
    {
        global $_TABLES;

        $retval = array();
        $children = $this->getChildren();
        $idlist = implode(',', $children);
        if (empty($idlist)) return $retval;
        $sql = "SELECT album_id,hidden,owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon "
             . "FROM {$_TABLES['mg_albums']} "
             . "WHERE album_id IN ($idlist) ORDER BY album_order DESC";
        $result = DB_query($sql);
        while ($A = DB_fetchArray($result)) {
            $access = self::hasAccess($A['owner_id'], $A['group_id'], $A['perm_owner'],
                                      $A['perm_group'], $A['perm_members'], $A['perm_anon']);
            if ($access > 0) {
                if ($A['hidden'] == 1) {
                    if ($access == 3) {
                        $retval[] = $A['album_id'];
                    }
                } else {
                    $retval[] = $A['album_id'];
                }
            }
        }
        return $retval;
    }

    function getChildcount()
    {
        global $_TABLES;

        $numChildren = 0;
        $children = $this->getChildren();
        $idlist = implode(',', $children);
        if (empty($idlist)) return $numChildren;
        $sql = "SELECT hidden,owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon "
             . "FROM {$_TABLES['mg_albums']} "
             . "WHERE album_id IN ($idlist) ORDER BY album_order DESC";
        $result = DB_query($sql);
        while ($A = DB_fetchArray($result)) {
            $access = self::hasAccess($A['owner_id'], $A['group_id'], $A['perm_owner'],
                                      $A['perm_group'], $A['perm_members'], $A['perm_anon']);
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

    static public function buildLightboxSlideShow($album_id)
    {
        global $_MG_CONF, $LANG_MG03;

        $retval  = '<noscript><div style="border:1px dashed #ccc;margin-top:10px;padding:15px;" class="pluginAlert aligncenter">' . $LANG_MG03['js_warning'] . '</div></noscript>' . LB;
        $retval .= '<script type="text/javascript">' . LB;
        $retval .= 'function openGallery1() {' . LB;
        $retval .= '    return loadXMLDoc("' . $_MG_CONF['site_url'] . '/lightbox.php?aid=' . $album_id . '");';
        $retval .= '}' . LB;
        $retval .= '</script>' . LB;
        
        return $retval;
    }

    static public function getData($album_id, $data_array=array(), $check_access=false)
    {
        global $_TABLES;

        $retval = array();
        if (empty($data_array)) return $retval;

        if ($check_access == true) {
            $data_array = array_merge($data_array,
                                      array('owner_id', 'group_id', 'perm_owner',
                                            'perm_group', 'perm_members', 'perm_anon'));
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
        }

        if ($check_access == true) {
            $access = self::hasAccess($retval['owner_id'],$retval['group_id'],
                                      $retval['perm_owner'],$retval['perm_group'],
                                      $retval['perm_members'],$retval['perm_anon']);
            $retval = array_merge($retval, array('access' => $access));
        }

        return $retval;
    }

    function getMediaCount()
    {
        global $_TABLES;

        $mediaCount = 0;
        $root_album_owner_id = SEC_hasRights('mediagallery.admin');

        if ($this->access != 0) {
            if ( ($this->hidden && $root_album_owner_id == 1 ) || $this->hidden != 1) {
                $mediaCount = $this->media_count;
            }
        }

        $children = $this->getChildren();
        $idlist = implode(',', $children);
        if (empty($idlist)) return $mediaCount;
        $sql = "SELECT album_id,hidden,owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon "
             . "FROM {$_TABLES['mg_albums']} "
             . "WHERE album_id IN ($idlist) ORDER BY album_order DESC";
        $result = DB_query($sql);
        while ($A = DB_fetchArray($result)) {
            $access = self::hasAccess($A['owner_id'], $A['group_id'], $A['perm_owner'],
                                      $A['perm_group'], $A['perm_members'], $A['perm_anon']);
            if ($access > 0) {
                if ( ( $A['hidden'] && $root_album_owner_id == 1 ) || $A['hidden'] != 1 ) {
                    $child_album = new mgAlbum($A['album_id']);
                    $mediaCount += $child_album->getMediaCount();
                }
            }
        }
        return $mediaCount;
    }

    function findCover($id = '')
    {
        global $_TABLES;

        if (empty($id)) {
            $id = $this->id;
            if ($this->cover_filename != '') {
                return $this->cover_filename;
            }
        }

        $root_album_owner_id = SEC_hasRights('mediagallery.admin');

        $children = $this->getChildren($id);
        $idlist = implode(',', $children);
        if (empty($idlist)) return '';
        $sql = "SELECT album_id,album_cover_filename,hidden,owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon "
             . "FROM {$_TABLES['mg_albums']} "
             . "WHERE album_id IN ($idlist) ORDER BY album_order DESC";
        $result = DB_query($sql);
        while ($A = DB_fetchArray($result)) {
            $access = self::hasAccess($A['owner_id'],$A['group_id'],$A['perm_owner'],
                                      $A['perm_group'],$A['perm_members'],$A['perm_anon']);
            if ($access == 0) continue;
            if ($A['hidden'] != 1 || ($A['hidden'] && $root_album_owner_id)) {
                if ($A['album_cover_filename'] != '') {
                    return $A['album_cover_filename'];
                }
            }
            if ($A['hidden'] != 1 || ($A['hidden'] && $root_album_owner_id == 1)) {
                $filename = $this->findCover($A['album_id']);
                if ($filename != '') {
                    return $filename;
                }
            }
        }
        return '';
    }

    function getAlbumCount($access)
    {
        $count = 0;
        if ($this->access >= $access) {
            $count++;
        }
        $children = $this->getChildren();
        foreach ($children as $child) {
            $child_album = new mgAlbum($child);
            $count += $child_album->getAlbumCount($access);
        }
        return $count;
    }

    /*
     * This function will build a select box of albums the user has access to
     * this is used to create the jumpbox at the bottom of the page.
     */
    function buildJumpBox(&$album_jumpbox, $selected, $access=1, $hide=0, $level=0)
    {
        $mgadmin = SEC_hasRights('mediagallery.admin');
        $count = 0;
        $indent = '';
        $z = 0;
        while ($z < $level) {
            $indent .= "&nbsp;&nbsp;&nbsp;&nbsp;";
            $z++;
        }

        if ($this->access >= $access || $this->id == 0) {
            if ($this->id != $hide) {
                if (!$this->hidden || ($this->hidden && $mgadmin)) {
                    $album_jumpbox .= '<option value="' . $this->id . '"' . ($this->id == $selected ? ' selected="selected" ' : '') . '>' . $indent;
                    $tatitle = strip_tags($this->title);
                    if (strlen($tatitle) > 50) {
                        $aTitle = COM_truncate($tatitle, 50) . '...';
                    } else {
                        $aTitle = $tatitle;
                    }
                    $album_jumpbox .= $aTitle .'</option>' . LB;
                    $count++;
                }
            }

            $children = $this->getChildren();
            foreach ($children as $child) {
                $child_album = new mgAlbum($child);
                $count += $child_album->buildJumpBox($album_jumpbox, $selected, $access, $hide, $level + 1);
            }
        }
        return $count;
    }

    function getAlbumArray(&$album_array, $access=1, $hide=0, $level=0)
    {
        $mgadmin = SEC_hasRights('mediagallery.admin');
        $count = 0;
        $indent = '';
        $z = 0;
        while ($z < $level) {
            $indent .= "&nbsp;&nbsp;&nbsp;&nbsp;";
            $z++;
        }

        if ($this->access >= $access || $this->id == 0) {
            if ($this->id != $hide) {
                if (!$this->hidden || ($this->hidden && $mgadmin)) {
                    $tatitle = strip_tags($this->title);
                    if (strlen($tatitle) > 50) {
                        $aTitle = COM_truncate($tatitle, 50) . '...';
                    } else {
                        $aTitle = $tatitle;
                    }
                    $album_array[$indent . $aTitle] = $this->id;
                    $count++;
                }
            }

            $children = $this->getChildren();
            foreach ($children as $child) {
                $child_album = new mgAlbum($child);
                $count += $child_album->getAlbumArray($album_array, $access, $hide, $level + 1);
            }
        }
        return $count;
    }

    /*
     * this function will return a list of valid albums for maint routines
     */
    function buildAlbumBox(&$album_selectbox, $selected, $access=1, $hide=0, $type='upload', $level=0)
    {
        global $_USER, $_MG_CONF;

        $_MG_USERPREFS = MG_getUserPrefs();

        $mgadmin = SEC_hasRights('mediagallery.admin');
        $count = 0;
        $indent = '';
        $z = 0;
        while ($z < $level) {
            $indent .= "&nbsp;&nbsp;&nbsp;&nbsp;";
            $z++;
        }

        if ($type == 'upload') {
            if (
                ($_MG_CONF['member_albums'] && $this->isMemberAlbum() && $this->owner_id == $_USER['uid'] && $_MG_USERPREFS['active']) ||
                ($this->member_uploads && $this->access >= 2) ||
                ($this->access >= $access) ||
                $mgadmin
                ) {

                if ( $this->id != $hide ) {
                    if ( !$this->hidden || ( $this->hidden && $mgadmin ) ) {
                        if ( $this->id != 0 ) {
                            $album_selectbox .= '<option value="' . $this->id . '"' . ($this->id == $selected ? ' selected="selected" ' : '') .'>' . $indent;
                            $tatitle = strip_tags($this->title);
                            if ( strlen( $tatitle ) > 50 ) {
                                $aTitle = COM_truncate( $tatitle, 50 ) . '...';
                            } else {
                                $aTitle = $tatitle;
                            }
                            $album_selectbox .= $aTitle .'</option>';
                            $count++;
                        }
                    }
                }
            }
        }

        if ($type == 'edit') {
            if (
                ($this->id == $selected) ||
                ($_MG_CONF['member_albums'] && $_MG_CONF['member_album_root'] == $this->id && $_MG_CONF['member_create_new'] && $_MG_USERPREFS['active']) ||
                ($this->access >= $access)
                ) {
                if ( $this->id != $hide ) {
                    if ( !$this->hidden || $mgadmin ) {
                    
                        $album_selectbox .= '<option value="' . $this->id . '"' . ($this->id == $selected ? ' selected="selected" ' : '') .'>' . $indent;
                        $tatitle = strip_tags($this->title);
                        if ( strlen( $tatitle ) > 50 ) {
                            $aTitle = COM_truncate( $tatitle, 50 ) . '...';
                        } else {
                            $aTitle = $tatitle;
                        }
                        //$aTitle = $tatitle; //  . '(' . $this->access . ')';
                        $album_selectbox .= $aTitle .'</option>';
                        $count++;
                    }
                }
            }
        }

        if ($type == 'create') {
            if (
                ($_MG_CONF['member_albums'] && $_MG_CONF['member_album_root'] == $this->id && $_MG_CONF['member_create_new'] && $_MG_USERPREFS['active']) ||
                ($this->access >= $access)
                ) {
                if ( $this->id != $hide ) {
                    if ( !$this->hidden || ( $this->hidden && $mgadmin ) ) {
                        if ( $this->id != 0 || ($mgadmin || ($_MG_CONF['member_albums'] == 1 && $_MG_CONF['member_album_root'] == 0 && $_MG_CONF['member_create_new']))) {
                            $album_selectbox .= '<option value="' . $this->id . '"' . ($this->id == $selected ? ' selected="selected" ' : '') .'>' . $indent;
                            $tatitle = strip_tags($this->title);
                            if ( strlen( $tatitle ) > 50 ) {
                                $aTitle = COM_truncate( $tatitle, 50 ) . '...';
                            } else {
                                $aTitle = $tatitle;
                            }
                            $album_selectbox .= $aTitle .'</option>';
                            $count++;
                        }
                    }
                }
            }
        }

        if ($type == 'manage') {
            if ($this->access >= $access) {
                if ( !$this->hidden || ( $this->hidden && $mgadmin ) ) {
                    if ( $this->id != 0 || ($mgadmin || ($_MG_CONF['member_albums'] == 1 && $_MG_CONF['member_album_root'] == 0 && $_MG_CONF['member_create_new']))) {
                        $album_selectbox .= '<option ' .  ($this->id == $hide ? 'disabled="disabled" ' : '') . ' value="' . $this->id . '"' . ($this->id == $selected && $this->id != $hide ? ' selected="selected" ' : '') . '>' . $indent;
                        $tatitle = strip_tags($this->title);
                        if ( strlen( $tatitle ) > 50 ) {
                            $aTitle = COM_truncate( $tatitle, 50 ) . '...';
                        } else {
                            $aTitle = $tatitle;
                        }
                        $album_selectbox .= $aTitle . '</option>';
                        $count++;
                    }
                }
            }
        }

        if ($this->id != $hide || ($this->id == $hide && $type == 'manage')) {
            $children = $this->getChildren();
            foreach ($children as $child) {
                $child_album = new mgAlbum($child);
                $count += $child_album->buildAlbumBox($album_selectbox, $selected, $access, $hide, $type, $level + 1);
            }
        }

        return $count;
    }
}

?>