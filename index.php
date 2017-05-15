<?php

/**
 * Copyright 2012-2017 Christoph M. Becker
 *
 * This file is part of Forum_XH.
 *
 * Forum_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Forum_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Forum_XH.  If not, see <http://www.gnu.org/licenses/>.
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
    die(
        <<<EOT
Forum_XH detected an unsupported CMSimple_XH version.
Uninstall Forum_XH or upgrade to a supported CMSimple_XH version!
EOT
    );
}

/**
 * @param string $forum
 * @return ?string
 */
function forum($forum)
{
    global $plugin_tx;

    $ptx = $plugin_tx['forum'];
    if (!preg_match('/^[a-z0-9\-]+$/u', $forum)) {
        return XH_message('fail', $ptx['msg_invalid_name'], $forum);
    }
    $controller = new Forum\MainController($forum);
    $action = isset($_REQUEST['forum_actn']) ? $_REQUEST['forum_actn'] : 'default';
    $action .= 'Action';
    if (method_exists($controller, $action)) {
        ob_start();
        $controller->{$action}();
        return ob_get_clean();
    }
}

if (isset($_GET['forum_preview'])) {
    (new Forum\MainController(''))->previewAction();
    exit;
}

(new Forum\Plugin)->run();
