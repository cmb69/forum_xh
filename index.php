<?php

/**
 * Copyright 2012-2021 Christoph M. Becker
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

use Forum\Dic;

const FORUM_VERSION = "1.0beta5";

/** @return string|never */
function forum(string $forum)
{
    global $plugin_tx;

    $ptx = $plugin_tx['forum'];
    if (!preg_match('/^[a-z0-9\-]+$/u', $forum)) {
        return XH_message('fail', $ptx['msg_invalid_name'], $forum);
    }
    switch ($_GET['forum_actn'] ?? "") {
        case "default":
        case "":
            return Dic::makeShowForum()($forum)->fire();
        case "post":
            return Dic::makePostComment()($forum)->fire();
        case "preview":
            return Dic::makeShowPreview()()->fire();
    }
    $controller = Dic::makeMainController();
    $action = $_GET['forum_actn'] ?? 'default';
    $action .= 'Action';
    if (!is_callable([$controller, $action])) {
        $action = 'defaultAction';
    }
    return $controller->{$action}($forum)->fire();
}
