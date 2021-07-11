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

namespace Forum;

class Plugin
{
    const VERSION = '1.0beta5';

    /**
     * @return void
     */
    public static function run()
    {
        eval('function forum($forum) {return \Forum\Plugin::forum($forum);}');
        if (defined('XH_ADM') && XH_ADM) {
            XH_registerStandardPluginMenuItems(false);
            if (XH_wantsPluginAdministration('forum')) {
                self::handleAdministration();
            }
        }
    }

    /**
     * @return void
     */
    private static function handleAdministration()
    {
        global $admin, $o;

        $o .= print_plugin_admin('off');
        switch ($admin) {
            case '':
                ob_start();
                (new InfoController(new SystemCheckService()))->defaultAction();
                $o .= ob_get_clean();
                break;
            default:
                $o .= plugin_admin_common();
        }
    }

    /**
     * @param string $forum
     * @return string|never
     */
    public static function forum($forum)
    {
        global $plugin_tx;

        $ptx = $plugin_tx['forum'];
        if (!preg_match('/^[a-z0-9\-]+$/u', $forum)) {
            return XH_message('fail', $ptx['msg_invalid_name'], $forum);
        }
        $controller = new MainController($forum);
        $action = isset($_REQUEST['forum_actn']) ? $_REQUEST['forum_actn'] : 'default';
        $action .= 'Action';
        if (method_exists($controller, $action)) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('X-Location: ' . CMSIMPLE_URL . "?{$_SERVER['QUERY_STRING']}");
                while (ob_get_level()) {
                    ob_end_clean();
                }
                $controller->{$action}();
                exit;
            } else {
                ob_start();
                $controller->{$action}();
                return ob_get_clean();
            }
        }
        return "";
    }
}
