<?php
// +--------------------------------------------------------------------------+
// | Media Gallery Plugin - Geeklog                                           |
// +--------------------------------------------------------------------------+
// | userprefs.php                                                            |
// |                                                                          |
// | User preferences interface                                               |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2015 by the following authors:                             |
// |                                                                          |
// | Yoshinori Tahara       taharaxp AT gmail DOT com                         |
// |                                                                          |
// | Based on the Media Gallery Plugin for glFusion CMS                       |
// | Copyright (C) 2002-2010 by the following authors:                        |
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

if (COM_isAnonUser()) {
    $display = SEC_loginRequiredForm();
    $display = COM_createHTMLDocument($display);
    COM_output($display);
    exit;
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';

$mode = isset($_REQUEST['mode']) ? COM_applyFilter ($_REQUEST['mode']) : '';

if ($mode == $LANG_MG01['cancel']) {
    header("Location: " . $_MG_CONF['site_url'] . '/index.php');
    exit;
}

if ($mode == $LANG_MG01['submit'] && !empty($LANG_MG01['submit'])) {
    $display_columns = 0;
    $display_rows = 0;
    $mp3_player = -1;
    $playback_mode = -1;
    $tn_size = -1;
    if (!empty($_POST['display_rows'])) {
        $display_rows    = intval(COM_applyFilter($_POST['display_rows'], true));
    }
    if (!empty($_POST['display_columns'])) {
        $display_columns = intval(COM_applyFilter($_POST['display_columns'], true));
    }
    if (!empty($_POST['mp3_player'])) {
        $mp3_player      = intval(COM_applyFilter($_POST['mp3_player'], true));
    }
    if (!empty($_POST['playback_mode'])) {
        $playback_mode   = intval(COM_applyFilter($_POST['playback_mode'], true));
    }
    if (!empty($_POST['tn_size'])) {
        $tn_size         = intval(COM_applyFilter($_POST['tn_size'], true));
    }
    $uid             = intval($_USER['uid']);

    if ($display_columns < 0 || $display_columns > 5) {
        $display_columns = 3;
    }
    if ($display_rows < 0 || $display_rows > 99) {
        $display_rows = 4;
    }
    if ($_MG_CONF['up_display_rows_enabled'] == 0) {
        $display_rows = 0;
    }
    if ($_MG_CONF['up_display_columns_enabled'] == 0) {
        $display_columns = 0;
    }
    if ($_MG_CONF['up_mp3_player_enabled'] == 0) {
        $mp3_player = -1;
    }
    if ($_MG_CONF['up_av_playback_enabled'] == 0) {
        $playback_mode = -1;
    }
    if ($_MG_CONF['up_thumbnail_size_enabled'] == 0) {
        $tn_size = -1;
    }

    DB_save($_TABLES['mg_userprefs'],
            'uid,display_rows,display_columns,mp3_player,playback_mode,tn_size',
            "$uid,$display_rows,$display_columns,$mp3_player,$playback_mode,$tn_size");

    header("Location: " . $_MG_CONF['site_url'] . '/index.php');
    exit;
}

$display = '';
$x = 0;
$_MG_USERPREFS = MG_getUserPrefs();

// let's see if anything is actually set...
if (!isset($_MG_USERPREFS['mp3_player'])) {
    $_MG_USERPREFS['mp3_player']      = -1;
    $_MG_USERPREFS['playback_mode']   = 1;
    $_MG_USERPREFS['tn_size']         = -1;
    $_MG_USERPREFS['display_rows']    = 0;
    $_MG_USERPREFS['display_columns'] = 0;
}

$T = COM_newTemplate(MG_getTemplatePath(0));
$T->set_file('admin', 'userprefs.thtml');
$T->set_block('admin', 'prefRow', 'pRow');

$T->set_var('start_block', COM_startBlock($LANG_MG01['user_prefs_title']));
$T->set_var('end_block', COM_endBlock());

// build select boxes

$mp3_select = MG_optionlist(array(
    'name'    => 'mp3_player',
    'current' => $_MG_USERPREFS['mp3_player'],
    'values'  => array(
        '-1' => $LANG_MG01['system_default'],
        '0'  => $LANG_MG01['windows_media_player'],
        '1'  => $LANG_MG01['quicktime_player'],
        '2'  => $LANG_MG01['flashplayer'],
    ),
));

$playback_select = MG_optionlist(array(
    'name'    => 'playback_mode',
    'current' => $_MG_USERPREFS['playback_mode'],
    'values'  => array(
        '-1' => $LANG_MG01['system_default'],
        '0'  => $LANG_MG01['play_in_popup'],
        '2'  => $LANG_MG01['play_inline'],
        '3'  => $LANG_MG01['use_mms'],
    ),
));

$tn_select = MG_optionlist(array(
    'name'    => 'tn_size',
    'current' => $_MG_USERPREFS['tn_size'],
    'values'  => array(
        '-1' => $LANG_MG01['system_default'],
        '0'  => $LANG_MG01['small'],
        '1'  => $LANG_MG01['medium'],
        '2'  => $LANG_MG01['large'],
    ),
));

$display_rows_input = MG_input(array(
    'type' => 'text',
    'size' => '3',
    'name' => 'display_rows',
    'value' => $_MG_USERPREFS['display_rows'],
));

$display_columns_input = MG_input(array(
    'type' => 'text',
    'size' => '3',
    'name' => 'display_columns',
    'value' => $_MG_USERPREFS['display_columns'],
));

if ($_MG_CONF['up_display_rows_enabled']) {
    $T->set_var(array(
        'lang_prompt' => $LANG_MG01['display_rows_prompt'],
        'input_field' => $display_rows_input,
        'lang_help'   => $LANG_MG01['display_rows_help'],
        'rowcounter'  => $x++ % 2,
    ));
    $T->parse('pRow', 'prefRow', true);
}
if ($_MG_CONF['up_display_columns_enabled']) {
    $T->set_var(array(
        'lang_prompt' => $LANG_MG01['display_columns_prompt'],
        'input_field' => $display_columns_input,
        'lang_help'   => $LANG_MG01['display_columns_help'],
    ));
    $T->parse('pRow', 'prefRow', true);
}
if ($_MG_CONF['up_mp3_player_enabled']) {
    $T->set_var(array(
        'lang_prompt' => $LANG_MG01['mp3_player'],
        'input_field' => $mp3_select,
        'lang_help'   => $LANG_MG01['mp3_player_help'],
    ));
    $T->parse('pRow', 'prefRow', true);
}
if ($_MG_CONF['up_av_playback_enabled']) {
    $T->set_var(array(
        'lang_prompt' => $LANG_MG01['av_play_options'],
        'input_field' => $playback_select,
        'lang_help'   => $LANG_MG01['av_play_options_help'],
    ));
    $T->parse('pRow', 'prefRow', true);
}
if ($_MG_CONF['up_thumbnail_size_enabled']) {
    $T->set_var(array(
        'lang_prompt' => $LANG_MG01['tn_size'],
        'input_field' => $tn_select,
        'lang_help'   => $LANG_MG01['tn_size_help'],
    ));
    $T->parse('pRow', 'prefRow', true);
}

$T->set_var('site_admin_url', $_CONF['site_admin_url']);
$T->set_var(array(
    'site_url'        => $_CONF['site_url'],
    's_form_action'   => $_MG_CONF['site_url'] . '/userprefs.php',
    'lang_user_prefs' => $LANG_MG01['user_prefs_title'],
    'lang_submit'     => $LANG_MG01['submit'],
    'lang_cancel'     => $LANG_MG01['cancel'],
));
$display = $T->finish($T->parse('output', 'admin'));
$display = COM_createHTMLDocument($display);

COM_output($display);
?>