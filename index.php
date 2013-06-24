<?php

/**
 * Front-End of Forum_XH.
 *
 * Copyright (c) 2012 Christoph M. Becker (see license.txt)
 */


if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}


define('FORUM_VERSION', '1beta2');


/**
 * Fully qualified absolute URL to CMSimple's index.php.
 */
define('FORUM_URL', 'http://'.(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '')
	.$_SERVER['SERVER_NAME'].preg_replace('/index.php$/', '', $_SERVER['PHP_SELF']));


require_once $pth['folder']['plugin_classes'] . 'Forum.php';
$_Forum = new Forum();


/**
 * Handles the forum requests.
 *
 * @access public
 * @param string $forum  The name of the forum.
 * @return mixed
 */
function forum($forum)
{
    global $_Forum;

    return $_Forum->main($forum);
}

/**
 * Return the comment preview.
 */
if (isset($_GET['forum_preview'])) {
    echo $_Forum->commentPreview();
    exit;
}

?>
