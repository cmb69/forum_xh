<?php

/**
 * Front-End of Forum_XH.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Forum
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2012-2015 Christoph M. Becker <http://3-magi.net>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Forum_XH
 */

/*
 * Prevent direct access and usage from unsupported CMSimple_XH versions.
 */
if (!defined('CMSIMPLE_XH_VERSION')
    || strpos(CMSIMPLE_XH_VERSION, 'CMSimple_XH') !== 0
    || version_compare(CMSIMPLE_XH_VERSION, 'CMSimple_XH 1.6', 'lt')
) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=UTF-8');
    die(<<<EOT
Forum_XH detected an unsupported CMSimple_XH version.
Uninstall Forum_XH or upgrade to a supported CMSimple_XH version!
EOT
    );
}

/**
 * The plugin version.
 */
define('FORUM_VERSION', '@FORUM_VERSION@');

$_Forum = new Forum_Controller();

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

$_Forum->dispatch();

?>
