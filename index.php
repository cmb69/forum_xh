<?php

/**
 * Front-End of Forum_XH.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Forum
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2012-2014 Christoph M. Becker <http://3-magi.net>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Forum_XH
 */

/*
 * Prevent direct access.
 */
if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

/**
 * The plugin version.
 */
define('FORUM_VERSION', '@FORUM_VERSION@');

/**
 * Fully qualified absolute URL to CMSimple's index.php.
 */
define(
    'FORUM_URL',
    'http://'
    . (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '')
    . $_SERVER['SERVER_NAME']
    . preg_replace('/index.php$/', '', $_SERVER['PHP_SELF'])
);

require_once $pth['folder']['plugin_classes'] . 'Forum.php';
$_Forum = new Forum();


/**
 * Handles the forum requests.
 *
 * @param string $forum A forum name.
 *
 * @return mixed
 */
function forum($forum)
{
    global $_Forum;

    return $_Forum->main($forum);
}

/*
 * Return the comment preview.
 */
if (isset($_GET['forum_preview'])) {
    echo $_Forum->commentPreview();
    exit;
}

?>
