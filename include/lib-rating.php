<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | lib-rating.php                                                           |
// |                                                                          |
// | Rating interface library                                                 |
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

/*
 * Pull all rated media
 */
function MG_getRatedMedia()
{
    global $_USER, $_TABLES;

    static $ratedIds;
    if (isset($ratedIds)) return $ratedIds;

    $ip = addslashes($_SERVER['REMOTE_ADDR']);
    $uid = isset($_USER['uid']) ? $_USER['uid'] : 1;
    $ratedIds = array();
    $sql = "SELECT media_id FROM {$_TABLES['mg_rating']} WHERE "
         . (($uid == 1) ? "(ip_address='$ip')" : "(uid='$uid' OR ip_address='$ip')");
    $result = DB_query($sql, 1);
    while ($row = DB_fetchArray($result)) {
        $ratedIds[] = $row['media_id'];
    }
    return $ratedIds;
}

/*
 * build the rating box
 */
function MG_getRatingBar($enable_rating, $media_user_id, $media_id, $media_votes, $media_rating, $size='')
{
    global $_USER, $_SCRIPTS, $_MG_CONF, $LANG_MG03;

    static $setjs;

    $rater = '';

    if ($enable_rating <= 0) return $rater;

    $static = '';
    $voted = 0;

    // check to see if we are the owner, if so, no rating for us...
    if (isset($_USER['uid']) && $_USER['uid'] == $media_user_id) {
        $static = 'static';
    } else {
        $ratedIds = MG_getRatedMedia();
        if (in_array($media_id, $ratedIds)) {
            $static = 'static';
            $voted = 1;
        }
    }
    if ($enable_rating == 1 && (!isset($_USER['uid']) || $_USER['uid'] < 2)) {
        $static = 'static';
    }

    $status = 'dynamic';
    if ($static == 'static') {
        $status = 'static';
    }

    //set some variables
    $units = $_MG_CONF['rater_units_num'];
    $votes = ($media_votes < 1) ? 0 : $media_votes; // how many votes total
    $tense = ($votes == 1) ? $LANG_MG03['vote'] : $LANG_MG03['votes']; //plural form votes/vote
    $tense = trim($votes . ' ' . $tense . ' ' . $LANG_MG03['cast']);
    $rating_str = number_format($media_rating / 2, 1);

    // now draw the rating bar
    $rater .= LB;
    $rater .= '<div class="ratingblock">' . LB;
    $rater .= '  <div class="raty" data-average="' . $rating_str . '" data-status="' . $status . '" data-id="' . $media_id . '"></div>' . LB;
    $rater .= '  <div class="mg_rating">' . LB;
    if ($static == 'static') {
        $rater .= '    <span class="static">';
    } else {
        $rater .= '    <span' . ($voted ? ' class="voted"' : '') . '>';
    }
    $rater .= $LANG_MG03['rating'] . ': <strong>' . $rating_str . '</strong> / ' . $units . ' (' . $tense . ')</span>' . LB;
    $rater .= '  </div>' . LB;
    $rater .= '</div>' . LB;

    $_SCRIPTS->setJavaScriptFile('jquery.raty', '/mediagallery/js/raty/jquery.raty.js');
    $_SCRIPTS->setCSSFile('mg.raty', '/mediagallery/js/raty/jquery.raty.css');

    $str_size = ($size == 'sm') ? '-small' : '';

    if ($setjs === null) {
        $_SCRIPTS->setJavaScript("
    $(document).ready(function(){
      $('.raty').raty({
        readOnly: function() {
          return ($(this).attr('data-status') == 'static');
        },
        number: " . $units . ",
        space: false,
        starHalf: 'star" . $str_size . "-half.png',
        starOff: 'star" . $str_size . "-off.png',
        starOn: 'star" . $str_size . "-on.png',
        starType: 'img',
        //half: true,
        path: 'js/raty/images',
        score: function() {
          return $(this).attr('data-average');
        },
        click: function(score, evt) {
          $.post('rater.php', {
              dataid : $(this).attr('data-id'),
              rate : score,
              action : 'rating'
            },
            function(data) {
              if (data.error) {
                alert(data.server);
              }
            },
            'json'
          );
        }
      });
    });", true, true);
        $setjs = true;
    }

    return $rater;
}
?>