<?php

/**
 * Back-End of Forum_XH.
 *
 * Copyright (c) 2012 Christoph M. Becker (see license.txt)
 */


if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

/**
 * Returns the system checks.
 *
 * @return array
 *
 * @global array  The paths of system files and folders.
 * @global array  The localization of the core.
 * @global array  The localization of the plugins.
 * @global object The contents object.
 */
function Forum_systemChecks()
{
    global $pth, $tx, $plugin_tx, $_Forum_Contents;

    $ptx = $plugin_tx['forum'];
    $phpVersion = '4.3.0';
    $checks = array();
    $checks[sprintf($ptx['syscheck_phpversion'], $phpVersion)] =
	version_compare(PHP_VERSION, $phpVersion) >= 0 ? 'ok' : 'fail';
    foreach (array('date', 'pcre', 'session') as $ext) {
	$checks[sprintf($ptx['syscheck_extension'], $ext)] =
	    extension_loaded($ext) ? 'ok' : 'fail';
    }
    $checks[$ptx['syscheck_magic_quotes']] =
	!get_magic_quotes_runtime() ? 'ok' : 'fail';
    $checks[$ptx['syscheck_encoding']] =
	strtoupper($tx['meta']['codepage']) == 'UTF-8' ? 'ok' : 'warn';
    $check = file_exists($pth['folder']['plugins'] . 'jquery/jquery.inc.php');
    $checks[$ptx['syscheck_jquery']] = $check ? 'ok' : 'fail';
    $folders = array();
    foreach (array('config/', 'css/', 'languages/') as $folder) {
	$folders[] = $pth['folder']['plugins'] . 'forum/' . $folder;
    }
    $folders[] = $_Forum_Contents->dataFolder();
    foreach ($folders as $folder) {
	$checks[sprintf($ptx['syscheck_writable'], $folder)] =
	    is_writable($folder) ? 'ok' : 'warn';
    }
    return $checks;
}

/**
 * Returns the plugin information view.
 *
 * @return string (X)HTML
 *
 * @global array The paths of system files and folders.
 * @global array The localization of the plugins.
 */
function Forum_infoView()
{
    global $pth, $plugin_tx;

    $ptx = $plugin_tx['forum'];
    $labels = array(
	'syscheck' => $ptx['syscheck_title'],
	'about' => $ptx['about']
    );
    $icon = $pth['folder']['plugins'] . 'forum/forum.png';
    $checks = Forum_systemChecks();
    $version = FORUM_VERSION;
    $images = array();
    foreach (array('ok', 'warn', 'fail') as $state) {
	$images[$state] = $pth['folder']['plugins']
	    . 'forum/images/' . $state . '.png';
    }
    $bag = compact('labels', 'checks', 'icon', 'version', 'images');
    return Forum_render('info', $bag);

}


/**
 * Handle the plugin administration.
 */

if (isset($forum) && $forum == 'true') {
    $o .= print_plugin_admin('off');
    switch ($admin) {
	case '':
	    $o .= Forum_infoView();
	    break;
	default:
	    $o .= plugin_admin_common($action, $admin, $plugin);
    }
}

?>
