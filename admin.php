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

/**
 *Prevent direct access.
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
