<?php

/**
 * The autoloader.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Forum
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Forum_XH
 */

spl_autoload_register('Forum_autoload');

/**
 * The autoloader.
 *
 * @param string $className A class name.
 *
 * @return void
 *
 * @global array The paths of system files and folders.
 */
function Forum_autoload($className)
{
    global $pth;

    $parts = explode('_', $className);
    if ($parts[0] == 'Forum') {
        include_once $pth['folder']['plugins'] . 'forum/classes/'
            . $parts[1] . '.php';
    }
}

?>
