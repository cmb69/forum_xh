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

use XH\CSRFProtection;
use Fa\RequireCommand as FaRequireCommand;
use Plib\HtmlView as View;
use function XH_message;
use const CMSIMPLE_URL;
use function XH_registerStandardPluginMenuItems;
use function XH_wantsPluginAdministration;

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
        global $pth, $plugin_tx, $admin, $o;

        $o .= print_plugin_admin('off');
        switch ($admin) {
            case '':
                $controller = new InfoController(
                    new SystemCheckService(
                        $pth['folder']['plugins'],
                        "{$pth['folder']['content']}{$pth['folder']['base']}forum/",
                        $plugin_tx['forum']
                    ),
                    new View("{$pth['folder']['plugins']}forum/views", $plugin_tx['forum'])
                );
                ob_start();
                $controller->defaultAction();
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
        global $pth, $plugin_cf, $plugin_tx;

        $ptx = $plugin_tx['forum'];
        if (!preg_match('/^[a-z0-9\-]+$/u', $forum)) {
            return XH_message('fail', $ptx['msg_invalid_name'], $forum);
        }
        $controller = new MainController(
            $forum,
            self::url(),
            $plugin_cf['forum'],
            $plugin_tx['forum'],
            "{$pth['folder']['plugins']}forum/",
            new Contents("{$pth['folder']['content']}{$pth['folder']['base']}forum/"),
            new BBCode(
                $plugin_tx['forum'],
                "{$pth['folder']['plugins']}forum/images/",
                $plugin_tx['forum']['title_iframe']
            ),
            self::getCSRFProtector(),
            new View("{$pth['folder']['plugins']}forum/views", $plugin_tx['forum']),
            new FaRequireCommand(),
            new MailService($plugin_cf['forum'])
        );
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
                return (string) ob_get_clean();
            }
        }
        return "";
    }

    /**
     * @return CSRFProtection
     */
    private static function getCSRFProtector()
    {
        global $_XH_csrfProtection;

        if (isset($_XH_csrfProtection)) {
            return $_XH_csrfProtection;
        }
        return new CSRFProtection('forum_token');
    }

    private static function url(): Url
    {
        global $sl, $cf, $su;

        $base = preg_replace(['/index\.php$/', "/(?<=\\/)$sl\\/$/"], "", CMSIMPLE_URL);
        assert($base !== null);
        return new Url($base, $sl === $cf["language"]["default"] ? "" : $sl, $su);
    }
}
