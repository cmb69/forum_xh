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

/*
 * Handle the plugin administration.
 */
if (isset($forum) && $forum == 'true') {
    $o .= print_plugin_admin('off');
    switch ($admin) {
	case '':
	    $o .= $_Forum->infoView();
	    break;
	default:
	    $o .= plugin_admin_common($action, $admin, $plugin);
    }
}

?>
