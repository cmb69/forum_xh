<?php

/**
 * Back-End of Forum_XH.
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
