<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | newmedia.php                                                             |
// |                                                                          |
// | Media Upload routines                                                    |
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

if (stripos($_SERVER['PHP_SELF'], basename(__FILE__)) !== false) {
    die('This file can not be used on its own!');
}

require_once $_CONF['path'].'plugins/mediagallery/include/classAlbum.php';
require_once $_CONF['path'].'plugins/mediagallery/include/lib-upload.php';
require_once $_CONF['path'].'plugins/mediagallery/include/sort.php';

/**
* Upload form
*
* @param    int     album_id    album_id upload media
* @return   string              HTML
*/
function MG_uploadForm($album_id)
{
    global $_USER, $_CONF, $_MG_CONF, $LANG_MG01, $LANG_MG03;

    $retval = '';
    return MG_userUpload($album_id);
    $root_album = new mgAlbum(0);

    // Construct the album selectbox
    $album_selectbox  = MG_buildAlbumBox($root_album, $album_id, 3, -1, 'upload');
    if (empty($album_selectbox)) {
        return '';
    }

    // Construct the album jumpbox
    $album_jumpbox = MG_buildAlbumJumpbox($root_album, $album_id);

    // tell the flash uploader what the maximum file size can be.
    $file_size_limit = MG_getUploadLimit($album_id) . ' bytes';
    if($_MG_CONF['verbose']) {
        COM_errorLog('file_size_limit=' . $file_size_limit);
    }

    // Determine the valid filetypes for the current album
    $allowed_file_types = MG_getValidFileTypes($album_id);
    if ($_MG_CONF['verbose']) {
        COM_errorLog('allowed_file_types=' . $allowed_file_types);
    }

    $user_id = $_USER['uid'];
    $T = COM_newTemplate(MG_getTemplatePath($album_id));
    $T->set_file('mupload', 'swfupload.thtml');
    $T->set_var(array(
        'start_block'               => COM_startBlock($LANG_MG03['upload_media']),
        'end_block'                 => COM_endBlock(),
        'navbar'                    => MG_navbar($LANG_MG01['swfupload_media'], $album_id),
        'site_url'                  => $_CONF['site_url'], // no $_MG_CONF['site_url']
        'album_id'                  => $album_id,
        'album_select'              => $album_selectbox,
        'jumpbox'                   => $album_jumpbox,
        'lang_destination'          => $LANG_MG01['destination_album'],
        'upload_url'                => 'swfupload/swfupload.php',
        'flash_url'                 => 'swfupload/swfupload.swf',
        'user_id'                   => $user_id,
        'user_token'                => @$user_token,
        'swfupload_usage'           => $LANG_MG01['swfupload_usage'],
        'swfupload_allowed_types'   => $LANG_MG01['swfupload_allowed_types'],
        'swfupload_file_types'      => $allowed_file_types,
        'swfupload_file_size_limit' => $LANG_MG01['swfupload_file_size_limit'],
        'swfupload_size_limit'      => $file_size_limit,
        'swfupload_pending'         => $LANG_MG01['swfupload_pending'],
        'swfupload_q_too_many'      => $LANG_MG01['swfupload_q_too_many'],
        'sfwupload_q_limit'         => $LANG_MG01['swfupload_q_limit'],
        'swfupload_q_select'        => $LANG_MG01['swfupload_q_select'],
        'swfupload_q_up_to'         => $LANG_MG01['swfupload_q_up_to'],
        'swfupload_files'           => $LANG_MG01['swfupload_files'],
        'swfupload_one_file'        => $LANG_MG01['swfupload_one_file'],
        'swfupload_err_filesize'    => $LANG_MG01['swfupload_err_filesize'],
        'swfupload_err_zerosize'    => $LANG_MG01['swfupload_err_zerosize'],
        'swfupload_err_filetype'    => $LANG_MG01['swfupload_err_filetype'],
        'swfupload_err_general'     => $LANG_MG01['swfupload_err_general'],
        'swfupload_uploading'       => $LANG_MG01['swfupload_uploading'],
        'swfupload_complete'        => $LANG_MG01['swfupload_complete'],
        'swfupload_error'           => $LANG_MG01['swfupload_error'],
        'swfupload_failed'          => $LANG_MG01['swfupload_failed'],
        'swfupload_io_error'        => $LANG_MG01['swfupload_io_error'],
        'swfupload_sec_error'       => $LANG_MG01['swfupload_sec_error'],
        'swfupload_limit_exceeded'  => $LANG_MG01['swfupload_limit_exceeded'],
        'swfupload_fail_validation' => $LANG_MG01['swfupload_fail_validation'],
        'swfupload_cancelled'       => $LANG_MG01['swfupload_cancelled'],
        'swfupload_stopped'         => $LANG_MG01['swfupload_stopped'],
        'swfupload_unhandled'       => $LANG_MG01['swfupload_unhandled'],
        'swfupload_file'            => $LANG_MG01['swfupload_file'],
        'swfupload_uploaded'        => $LANG_MG01['swfupload_uploaded'],
        'swfupload_types_desc'      => $LANG_MG01['swfupload_types_desc'],
        'swfupload_queue'           => $LANG_MG01['swfupload_queue'],
        'swfupload_continue'        => $LANG_MG01['swfupload_continue'],
        'swfupload_cancel_all'      => $LANG_MG01['swfupload_cancel_all'],
        'swfupload_noscript'        => $LANG_MG01['swfupload_noscript'],
        'swfupload_is_loading'      => $LANG_MG01['swfupload_is_loading'],
        'swfupload_not_loading'     => $LANG_MG01['swfupload_not_loading'],
        'swfupload_didnt_load'      => $LANG_MG01['swfupload_didnt_load'],
        'save_exit'                 => $LANG_MG01['save_exit'],
        'title'                     => $LANG_MG01['title'],
        'description'               => $LANG_MG01['description'],
    ));

    $T->parse('output', 'mupload');
    $retval .= $T->finish($T->get_var('output'));

    return $retval;
}

/**
 * Save upload(s)
 *
 * @param    int     album_id    album_id save uploaded media
 * @return   string              HTML
 */
function MG_saveUpload($album_id)
{
    global $_TABLES, $_MG_CONF, $LANG_MG01, $LANG_MG02, $new_media_id;

    $statusMsg = '';
    $file = isset($_FILES) && is_array($_FILES) ? $_FILES : array();
    $album = new mgAlbum($album_id);

    if ($_MG_CONF['verbose']) {
        COM_errorLog('*** Inside MG_saveUpload()***');
        COM_errorLog('uploading to album_id=' . $album_id);
        COM_errorLog("album owner_id=" . $album->owner_id);
    }

    if (!isset($album->id) || $album_id == 0) {
        COM_errorLog('MediaGallery: Upload was unable to determine album id');
        return $LANG_MG01['upload_err_album_id'];
    }

    $successfull_upload = 0;

    foreach ($file as $tagname => $object) {
        $filename    = $object['name'];
        $filetype    = $object['type'];
        $filesize    = $object['size'];
        $filetmp     = $object['tmp_name'];
        $error       = $object['error'];
        $caption     = '';
        $description = '';
        $attachtn    = '';
        $thumbnail   = '';

        if ($_MG_CONF['verbose']) {
            COM_errorLog('filename=' . $filename, 1);
            COM_errorLog('filesize=' . $filesize, 1);
            COM_errorLog('filetype=' . $filetype, 1);
            COM_errorLog('filetmp='  . $filetmp,  1);
            COM_errorLog('error='    . $error,    1);
        }

        // we need to move the max filesize stuff to the flash uploader
        if (($album->max_filesize != 0) && ($filesize > $album->max_filesize)) {
            COM_errorLog('MediaGallery: File ' . $filename . ' exceeds maximum allowed filesize for this album');
            COM_errorLog('MediaGallery: Max filesize for this album=' . $album->max_filesize);
            $tmpmsg = sprintf($LANG_MG02['upload_exceeds_max_filesize'], $filename);
            return $tmpmsg;
        }

        $attach_tn = 0;

        // process the uploaded file(s)
        $opt = array(
            'caption'     => $caption,
            'description' => $description,
            'filetype'    => $filetype,
            'atttn'       => $attach_tn,
            'thumbnail'   => $thumbnail,
        );
        list($rc, $msg) = MG_getFile($filetmp, $filename, $album_id, $opt);

        if ($rc == true) {
            $successfull_upload++;
        } else {
            COM_errorLog('MG_saveUpload error: ' . $msg, 1);
            return $msg;
        }
    }

    if ($successfull_upload) {
        MG_notifyModerators($album_id);
    }

    // failsafe check - after all the uploading is done, double check that the database counts
    // equal the actual count of items shown in the database, if not, fix the counts and log
    // the error
    $dbCount = DB_count($_TABLES['mg_media_albums'], 'album_id', intval($album_id));
    $aCount  = DB_getItem($_TABLES['mg_albums'], 'media_count', "album_id=" . intval($album_id));

    if ($dbCount != $aCount) {
        DB_change($_TABLES['mg_albums'], 'media_count', $dbCount, 'album_id', intval($album_id));
        COM_errorLog("MediaGallery: Upload processing - Counts don't match - dbCount = " . $dbCount . " aCount = " . $aCount);
    }
    MG_SortMedia($album_id);

    return 'FILEID:' . $new_media_id;
}

/**
* Browser upload form
*
* @param    int     album_id    album_id upload media
* @return   string              HTML
*
*/
function MG_userUpload($album_id)
{
    global $_USER, $_TABLES, $_MG_CONF, $LANG_MG01, $LANG_MG03;

    $retval = '';

    $root_album = new mgAlbum(0);

    // build a select box of valid albums for upload
    $album_selectbox  = MG_buildAlbumBox($root_album, $album_id, 3, -1, 'upload');

    // build category list...
    $result = DB_query("SELECT * FROM {$_TABLES['mg_category']} ORDER BY cat_id ASC");
    $nRows = DB_numRows($result);
    $catRow = array();
    for ($i=0; $i < $nRows; $i++) {
        $catRow[$i] = DB_fetchArray($result);
    }
    $cRows = count($catRow);
    if ($cRows > 0) {
        $cat_select = '<select name="cat_id[]">';
        $cat_select .= '<option value="0">' . $LANG_MG01['no_category'] . '</option>';
        for ($i=0; $i < $cRows; $i++) {
            $cat_select .= '<option value="' . $catRow[$i]['cat_id'] . '">' . $catRow[$i]['cat_name'] . '</option>';
        }
        $cat_select .= '</select>';
    } else {
        $cat_select = '';
    }

    $user_quota = DB_getItem($_TABLES['mg_userprefs'], 'quota', "uid=" . intval($_USER['uid']));
    if ($user_quota > 0) {
        $disk_used = MG_quotaUsage($_USER['uid']);
        $user_quota = $user_quota / 1024;
        $disk_used =  $disk_used / 1024;  // $disk_used / 1048576;
        $quota = sprintf($LANG_MG01['user_quota'],$user_quota,$disk_used,$user_quota-$disk_used);
    } else {
        $quota = '';
    }
    $post_max_size     = ini_get('post_max_size');
    $post_max_size_b   = MG_return_bytes($post_max_size);

    $upload_max_size   = ini_get('upload_max_filesize');
    $upload_max_size_b = MG_return_bytes($upload_max_size);

    $max_upload_size = $upload_max_size_b / 1048576;    // take to Mb
    $post_max_size   = $post_max_size_b / 1048576;      // take to Mb
    $html_max_filesize = $upload_max_size_b;

    $msg_upload_size = sprintf($LANG_MG03['upload_size'],$post_max_size,$max_upload_size);

    $T = COM_newTemplate(MG_getTemplatePath($album_id));
    $T->set_file('mupload', 'userupload.thtml');
    $T->set_var(array(
        'start_block'       => COM_startBlock($LANG_MG03['upload_media']),
        'end_block'         => COM_endBlock(),
        'navbar'            => MG_navbar($LANG_MG01['browser_upload'], $album_id),
        'admin_url'         => $_MG_CONF['admin_url'],
        's_form_action'     => $_MG_CONF['site_url'] .'/admin.php',
        'lang_upload_help'  => $LANG_MG03['upload_help'],
        'lang_upload_size'  => $msg_upload_size,
        'lang_zip_help'     => ($_MG_CONF['zip_enabled'] == 1 ? $LANG_MG03['zip_file_help'] . '<br' . XHTML . '><br' . XHTML . '>' : ''),
        'lang_media_upload' => $LANG_MG01['upload_media'],
        'lang_caption'      => $LANG_MG01['title'],
        'lang_file'         => $LANG_MG01['file'],
        'lang_description'  => $LANG_MG01['description'],
        'lang_attached_tn'  => $LANG_MG01['attached_thumbnail'],
        'lang_save'         => $LANG_MG01['save'],
        'lang_cancel'       => $LANG_MG01['cancel'],
        'lang_reset'        => $LANG_MG01['reset'],
        'lang_category'     => ($cRows > 0 ? $LANG_MG01['category'] : ''),
        'lang_keywords'     => $LANG_MG01['keywords'],
        'lang_destination_album' => $LANG_MG01['destination_album'],
        'lang_do_not_convert_orig' => $LANG_MG01['do_not_convert_orig'],
        'lang_file_number'  => $LANG_MG01['file_number'],
        'cat_select'        => $cat_select,
        'album_id'          => $album_id,
        'action'            => 'upload',
        'max_file_size'     => '<input type="hidden" name="MAX_FILE_SIZE" value="' . $html_max_filesize .'"' . XHTML . '>',
        'lang_quota'        => $quota,
        'album_select'      => $album_selectbox,
        'max_upload_size'   => $max_upload_size,
        'post_max_size'     => $post_max_size,
    ));

    $retval .= $T->finish($T->parse('output', 'mupload'));
    return $retval;
}

/**
* Save browser upload(s)
*
* @param    int     album_id    album_id save uploaded media
* @return   string              HTML
*
*/
function MG_saveUserUpload($album_id)
{
    global $_USER, $_CONF, $_TABLES, $_MG_CONF, $LANG_MG02, $LANG_MG03;

    $retval = '';
    $retval .= COM_startBlock($LANG_MG03['upload_results'], '',
                              COM_getBlockTemplate('_admin_block', 'header'));

    $T = COM_newTemplate(MG_getTemplatePath($album_id));
    $T->set_file('mupload', 'useruploadstatus.thtml');

    $statusMsg = '';
    $file = array();
    $file = $_FILES['newmedia'];
    $thumbs = $_FILES['thumbnail'];
    $album = new mgAlbum($album_id);
    $successfull_upload = 0;
    $br = '<br' . XHTML . '>';

    foreach ($file['name'] as $key => $name) {
        $filename    = $file['name'][$key];
        $filetype    = $file['type'][$key];
        $filesize    = $file['size'][$key];
        $filetmp     = $file['tmp_name'][$key];
        $error       = $file['error'][$key];
        $caption     = COM_stripslashes($_POST['caption'][$key]);
        $description = COM_stripslashes($_POST['description'][$key]);
        $keywords    = COM_stripslashes($_POST['keywords'][$key]);
        $category    = COM_applyFilter($_POST['cat_id'][$key],true);
        $attachtn    = isset($_POST['attachtn'][$key]) ? $_POST['attachtn'][$key] : '';
        $thumbnail   = isset($thumbs['tmp_name'][$key]) ? $thumbs['tmp_name'][$key] : '';
        if (isset($_POST['dnc'][$key]) && $_POST['dnc'][$key] == 'on') {
            $dnc = 1;
        } else {
            $dnc = 0;
        }

        if ($filename == '') continue;

        if ($album->max_filesize != 0 && $filesize > $album->max_filesize) {
            COM_errorLog("MG Upload: File " . $filename . " exceeds maximum allowed filesize for this album");
            $tmpmsg = sprintf($LANG_MG02['upload_exceeds_max_filesize'], $filename);
            $statusMsg .= $tmpmsg . $br;
            continue;
        }

        if ($attachtn == "on") {
            $attach_tn = 1;
        } else {
            $attach_tn = 0;
        }

        if ($error != UPLOAD_ERR_OK) {
            switch ($error) {
                case 1 :
                    $tmpmsg = sprintf($LANG_MG02['upload_too_big'], $filename);
                    $statusMsg .= $tmpmsg . $br;
                    COM_errorLog('MediaGallery:  Error - ' .$tmpmsg);
                    break;
                case 2 :
                    $tmpmsg = sprintf($LANG_MG02['upload_too_big_html'], $filename);
                    $statusMsg .= $tmpmsg  . $br;
                    COM_errorLog('MediaGallery: Error - ' .$tmpmsg);
                    break;
                case 3 :
                    $tmpmsg = sprintf($LANG_MG02['partial_upload'], $filename);
                    $statusMsg .= $tmpmsg  . $br;
                    COM_errorLog('MediaGallery: Error - ' .$tmpmsg);
                    break;
                case 4 :
                    break;
                case 6 :
                    $statusMsg .= $LANG_MG02['missing_tmp'] . $br;
                    break;
                case 7 :
                    $statusMsg .= $LANG_MG02['disk_fail'] . $br;
                    break;
                default :
                    $statusMsg .= $LANG_MG02['unknown_err'] . $br;
                    break;
            }
            continue;
        }

        // check user quota -- do we have one????
        $user_quota = DB_getItem($_TABLES['mg_userprefs'], 'quota', "uid=" . intval($_USER['uid']));
        if ($user_quota > 0) {
            $disk_used = MG_quotaUsage($_USER['uid']);
            if ($disk_used+$filesize > $user_quota) {
                COM_errorLog("MG Upload: File " . $filename . " would exceeds the users quota");
                $tmpmsg = sprintf($LANG_MG02['upload_exceeds_quota'], $filename);
                $statusMsg .= $tmpmsg . $br;
                continue;
            }
        }

        // process the uploaded files
        $opt = array(
            'caption'     => $caption,
            'description' => $description,
            'filetype'    => $filetype,
            'atttn'       => $attach_tn,
            'thumbnail'   => $thumbnail,
            'keywords'    => $keywords,
            'category'    => $category,
            'dnc'         => $dnc,
        );
        list($rc, $msg) = MG_getFile($filetmp, $filename, $album_id, $opt);

        $statusMsg .= $filename . " " . $msg . $br;
        if ($rc == true) {
            $successfull_upload++;
        }
    }

    if ($successfull_upload) {
        MG_notifyModerators($album_id);
    }

    // failsafe check - after all the uploading is done, double check that the database counts
    // equal the actual count of items shown in the database, if not, fix the counts and log
    // the error

    $dbCount = DB_count($_TABLES['mg_media_albums'], 'album_id', intval($album_id));
    $aCount  = DB_getItem($_TABLES['mg_albums'], 'media_count', "album_id=" . intval($album_id));
    if ($dbCount != $aCount) {
        DB_change($_TABLES['mg_albums'], 'media_count', $dbCount, 'album_id', intval($album_id));
        COM_errorLog("MediaGallery: Upload processing - Counts don't match - dbCount = " . $dbCount . " aCount = " . $aCount);
    }

    MG_SortMedia($album_id);

    $T->set_var('status_message', $statusMsg);

    $tmp = $_MG_CONF['site_url'] . '/album.php?aid=' . $album_id . '&amp;page=1';
    $redirect = sprintf($LANG_MG03['album_redirect'], $tmp);

    $T->set_var('redirect', $redirect);
    $T->parse('output', 'mupload');
    $retval .= $T->finish($T->get_var('output'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

    return $retval;
}

/**
* Save flash upload(s)
*
* @param    int     album_id    album_id save uploaded media
* @return   string              HTML
*
*/
function MG_saveFileUpload($album_id)
{
    global $_TABLES, $_MG_CONF, $LANG_MG01, $LANG_MG02, $new_media_id;

    $statusMsg = '';
    $file = array();
    $album = new mgAlbum($album_id);

    if ($_MG_CONF['verbose']) {
        COM_errorLog('*** Inside MG_saveFileUpload()***');
        COM_errorLog('uploading to album_id=' . $album_id);
        COM_errorLog('album owner_id=' . $album->owner_id);
    }

    if (!isset($album->id) || $album_id == 0) {
        COM_errorLog('MediaGallery: FileUpload was unable to determine album id');
        return $LANG_MG01['swfupload_err_album_id'];
    }

    $successfull_upload = 0;

    $upload = isset($_FILES['files']) ? $_FILES['files'] : null;
    if ($upload && is_array($upload['tmp_name'])) {
        // param_name is an array identifier like "files[]",
        // $_FILES is a multi-dimensional array:
        foreach ($upload['tmp_name'] as $index => $value) {
            $file[$index] = array(
                'name'     => $upload['name'][$index],
                'type'     => $upload['type'][$index],
                'size'     => $upload['size'][$index],
                'tmp_name' => $upload['tmp_name'][$index],
                'error'    => $upload['error'][$index],
            );
        }
    } else {
        // param_name is a single object identifier like "file",
        // $_FILES is a one-dimensional array:
        $file[0]['name']     = $upload['name'];
        $file[0]['type']     = $upload['type'];
        $file[0]['size']     = $upload['size'];
        $file[0]['tmp_name'] = $upload['tmp_name'];
        $file[0]['error']    = $upload['error'];
    }

    $info = array();

    foreach ($file as $tagname => $object) {
        $filename    = $object['name'];
        $filetype    = $object['type'];
        $filesize    = $object['size'];
        $filetmp     = $object['tmp_name'];
        $error       = $object['error'];
        $caption     = 'No Name';
        $description = 'No Description';
        $attachtn    = '';
        $thumbnail   = '';

        if ($_MG_CONF['verbose']) {
            COM_errorLog('filename=' . $filename, 1);
            COM_errorLog('filesize=' . $filesize, 1);
            COM_errorLog('filetype=' . $filetype, 1);
            COM_errorLog('filetmp='  . $filetmp,  1);
            COM_errorLog('error='    . $error,    1);
        }

        // we need to move the max filesize stuff to the flash uploader
        if ($album->max_filesize != 0 && $filesize > $album->max_filesize) {
            COM_errorLog('MediaGallery: File ' . $filename . ' exceeds maximum allowed filesize for this album');
            COM_errorLog('MediaGallery: Max filesize for this album=' . $album->max_filesize);
            $tmpmsg = sprintf($LANG_MG02['upload_exceeds_max_filesize'], $filename);
            return $tmpmsg;
        }

        $attach_tn = 0;

        // process the uploaded file(s)
        $opt = array(
            'caption'     => $caption,
            'description' => $description,
            'filetype'    => $filetype,
            'atttn'       => $attach_tn,
            'thumbnail'   => $thumbnail,
        );
        list($rc, $msg) = MG_getFile($filetmp, $filename, $album_id, $opt);

        if ($rc == true) {
            $successfull_upload++;

            $temp = new stdClass();
            $temp->name = $filename;
            $temp->size = $filesize;
            $temp->type = $filetype;
            $temp->mid  = $new_media_id;
            $temp->caption = $caption;
            $temp->description = $description;
            $info[] = $temp;

        } else {
            COM_errorLog('MG_saveFileUpload error: ' . $msg, 1);
            return $msg;
        }
    }

    if ($successfull_upload) {
        MG_notifyModerators($album_id);
    }

    // failsafe check - after all the uploading is done, double check that the database counts
    // equal the actual count of items shown in the database, if not, fix the counts and log
    // the error

    $dbCount = DB_count($_TABLES['mg_media_albums'], 'album_id', intval($album_id));
    $aCount  = DB_getItem($_TABLES['mg_albums'], 'media_count', "album_id=" . intval($album_id));
    if ($dbCount != $aCount) {
        DB_change($_TABLES['mg_albums'], 'media_count', $dbCount, 'album_id', intval($album_id));
        COM_errorLog("MediaGallery: Upload processing - Counts don't match - dbCount = " . $dbCount . " aCount = " . $aCount);
    }
    MG_SortMedia($album_id);

//    return 'FILEID:' . $new_media_id;

    $json = json_encode($info);

    return $json;
}
