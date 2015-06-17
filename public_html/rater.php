<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | rater.php                                                                |
// |                                                                          |
// | This page handles the 'AJAX' type response.                              |
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

require_once '../lib-common.php';

if (!in_array('mediagallery', $_PLUGINS)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';

function rater_sendResponse($response) {
    if ($response['error'] == true) {
        $response['server'] = '<strong>ERROR :</strong> ' . $response['server'];
    }
    echo json_encode($response); // send json datas to the js file
    exit;
}

$action = isset($_POST['action']) ? COM_applyFilter($_POST['action']) : '';

if ($action != 'rating') {
    rater_sendResponse(array('error' => true,
        'server' => '"action" post data not equal to \'rating\''));
}

$uid = isset($_USER['uid']) ? $_USER['uid'] : 1;

if ($uid < 2 && $_MG_CONF['loginrequired'] == 1) {
    rater_sendResponse(array('error' => true,
        'server' => 'Sorry, user must login first'));
}

//getting the values
//$ip_num = preg_replace("/[^0-9\.]/","",$_REQUEST['t']); // omit the check of IP address
$id_sent = isset($_POST['dataid']) ? COM_applyFilter($_POST['dataid']) : '';
$ip = $_SERVER['REMOTE_ADDR'];

$sql = "SELECT media_votes, media_rating, media_user_id "
     . "FROM {$_TABLES['mg_media']} "
     . "WHERE media_id='" . addslashes($id_sent) . "'";
$result = DB_query($sql);
if (DB_numRows($result) == 0) {
    rater_sendResponse(array('error' => true,
        'server' => 'An error occured during the request'));
}
list($votes, $rating, $owner_id) = DB_fetchArray($result);
$total_value = $rating * $votes;

if (!isset($owner_id) || $owner_id == '') {
    $owner_id = 2;
}

if ($uid == 1) {
    $sql = "SELECT id FROM {$_TABLES['mg_rating']} "
         . "WHERE ip_address='" . addslashes($ip) . "' "
         . "AND media_id='" . addslashes($id_sent) . "'";
} else {
    $sql = "SELECT id FROM {$_TABLES['mg_rating']} "
         . "WHERE (uid=" . intval($uid) . " OR ip_address='" . addslashes($ip) . "') "
         . "AND media_id='" . addslashes($id_sent) . "'";
}
$checkResult = DB_query($sql);
$voted = (DB_numRows($checkResult) > 0) ? 1 : 0;

COM_clearSpeedlimit($_MG_CONF['rating_speedlimit'], 'mgrate');
$last = COM_checkSpeedlimit('mgrate');
$speedlimiterror = ($last > 0) ? 1 : 0;

$vote_sent = isset($_POST['rate']) ? floatval($_POST['rate']) : 0;
if ($vote_sent == 0) {
    rater_sendResponse(array('error' => true,
        'server' => 'An error occured during the request'));
}
$sum = ($vote_sent * 2) + $total_value; // add together the current vote value and the total vote value
$units = $_MG_CONF['rater_units_num'];

// checking to see if the first vote has been tallied
// or increment the current number of votes
$added = ($sum == 0) ? 0 : $votes + 1;

$new_rating = $sum / $added;

if ($voted) { // prevent multiple voting
    rater_sendResponse(array('error' => true,
        'server' => 'You have already voted'));
}
if ($speedlimiterror) {
    rater_sendResponse(array('error' => true,
        'server' => 'Speed limit error'));
}
if ($vote_sent < 1 || $vote_sent > $units) { // keep votes within range
    rater_sendResponse(array('error' => true,
        'server' => 'An error occured during the request'));
}
/* 
if ($ip != $ip_num) { // make sure IP matches // omit the check of IP address
    rater_sendResponse(array('error' => true,
        'server' => 'An error occured during the request'));
}
*/
DB_change($_TABLES['mg_media'], 'media_votes', $added, 'media_id', addslashes($id_sent));
DB_change($_TABLES['mg_media'], 'media_rating', $new_rating, 'media_id', addslashes($id_sent));
$sql = "SELECT MAX(id) + 1 AS newid FROM " . $_TABLES['mg_rating'];
$result = DB_query($sql);
list($newid) = DB_fetchArray($result);
if ($newid < 1) {
    $newid = 1;
}
$sql = "INSERT INTO {$_TABLES['mg_rating']} (id, ip_address, uid, media_id, ratingdate, owner_id) "
     . "VALUES (" . $newid . ", '" . addslashes($ip) . "', " . $uid . ", '"
     . addslashes($id_sent) . "', " . time() . ", " . $owner_id . ")";
DB_query($sql);
COM_updateSpeedlimit('mgrate');

rater_sendResponse(array('error' => false,
    'server' => '<strong>Thanks for your rate.</strong><br' . XHTML . '/>'
              . '<strong>Rate received : ' . $vote_sent . '</strong>'));
exit;

?>