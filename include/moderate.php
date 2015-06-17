<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | moderate.php                                                             |
// |                                                                          |
// | Moderation routines                                                      |
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

function MG_approveSubmission($media_id)
{
    global $_CONF, $_TABLES, $LANG_MG01;

    $mid = addslashes($media_id);

    $owner_uid = DB_getItem($_TABLES['mg_mediaqueue'], 'media_user_id', "media_id='" . $mid . "'");

    DB_delete($_TABLES['mg_mediaqueue'], 'media_id', $mid);

    $album_id = DB_getItem($_TABLES['mg_media_album_queue'], 'album_id', "media_id='" . $mid . "'");

    DB_save($_TABLES['mg_media_albums'], 'album_id, media_id, media_order', "$album_id, '$mid', 0");

    require_once $_CONF['path'] . 'plugins/mediagallery/include/sort.php';
    MG_SortMedia($album_id);

    DB_delete($_TABLES['mg_media_album_queue'], 'media_id', $mid);

    $sql = "SELECT media_filename, media_type "
         . "FROM {$_TABLES['mg_media']} WHERE media_id='" . $mid . "'";
    $result = DB_query($sql);
    list($media_filename, $media_type) = DB_fetchArray($result);

    $media_count = DB_getItem($_TABLES['mg_albums'], 'media_count', 'album_id=' . $album_id);
    $media_count++;
    DB_change($_TABLES['mg_albums'], 'media_count', $media_count, 'album_id', $album_id);

    MG_updateAlbumLastUpdate($album_id);

    $album_cover = DB_getItem($_TABLES['mg_albums'], 'album_cover', 'album_id=' . $album_id);
    if ($album_cover == -1 && $media_type == 0) {
        DB_change($_TABLES['mg_albums'], 'album_cover_filename', $media_filename, 'album_id', $album_id);
    }

    // email the owner / uploader that the item has been approved.
    COM_clearSpeedlimit(600, 'mgapprove');
    $last = COM_checkSpeedlimit('mgapprove');
    if ($last == 0) {
        $result2 = DB_query("SELECT username, fullname, email FROM {$_TABLES['users']} WHERE uid='" . $owner_uid . "'");
        list($username, $fullname, $email) = DB_fetchArray($result2);
        if ($email != '') {
            $subject = $LANG_MG01['upload_approved'];
            $body  = $LANG_MG01['upload_approved'];
            $body .= '<br' . XHTML . '><br' . XHTML . '>';
            $body .= $LANG_MG01['thanks_submit'];
            $body .= '<br' . XHTML . '><br' . XHTML . '>';
            $body .= $_CONF['site_name'] . '<br' . XHTML . '>';
            $body .= $_CONF['site_url'] . '<br' . XHTML . '>';
            $to   = array();
            $from = array();
            $to   = COM_formatEmailAddress($username, $email);
            $from = COM_formatEmailAddress($_CONF['site_name'], $_CONF['site_mail']);
            if (!COM_mail($to, $subject, $body, $from, true)) {
                COM_errorLog("Media Gallery Error - Unable to send queue notification email");
            }
            COM_updateSpeedlimit('mgapprove');
        }
    }

    // PLG_itemSaved($media_id, 'mediagallery');
    // COM_rdfUpToDateCheck();
    // COM_olderStuff();

    return;
}


function MG_deleteSubmission($media_id)
{
    global $_TABLES, $_MG_CONF;

    $mid = addslashes($media_id);

    $result = DB_query("SELECT media_filename, media_mime_ext FROM {$_TABLES['mg_mediaqueue']} WHERE media_id='" . $mid . "'");
    $row = DB_fetchArray($result);

    DB_delete($_TABLES['mg_media_album_queue'], 'media_id', $mid);

    // remove the media
    foreach ($_MG_CONF['validExtensions'] as $ext) {
        if (file_exists($_MG_CONF['path_mediaobjects'] . 'tn/' . $row['media_filename'][0] . '/' . $row['media_filename'] . $ext)) {
            @unlink($_MG_CONF['path_mediaobjects'] . 'tn/'   . $row['media_filename'][0] . '/' . $row['media_filename'] . $ext);
            @unlink($_MG_CONF['path_mediaobjects'] . 'tn/'   . $row['media_filename'][0] . '/' . $row['media_filename'] . '_150x150' . $ext);
            @unlink($_MG_CONF['path_mediaobjects'] . 'disp/' . $row['media_filename'][0] . '/' . $row['media_filename'] . $ext);
            break;
        }
    }
    @unlink($_MG_CONF['path_mediaobjects'] . 'orig/' . $row['media_filename'][0] . '/' . $row['media_filename'] . '.' . $row['media_mime_ext']);

    return;
}
?>