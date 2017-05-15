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

namespace Forum;

use XH_CSRFProtection;

class Controller
{
    /**
     * @var object
     */
    private $contents;

    /**
     * @var object
     */
    private $bbcode;

    public function __construct()
    {
        global $pth;

        $folder = "{$pth['folder']['content']}{$pth['folder']['base']}forum/";
        $this->contents = new Contents($folder);
    }

    public function dispatch()
    {
        if (XH_ADM) {
            XH_registerStandardPluginMenuItems(false);
            if (XH_wantsPluginAdministration('forum')) {
                $this->handleAdministration();
            }
        }
    }

    private function handleAdministration()
    {
        global $admin, $action, $o;

        $o .= print_plugin_admin('off');
        switch ($admin) {
            case '':
                ob_start();
                (new InfoController)->defaultAction();
                $o .= ob_get_clean();
                break;
            default:
                $o .= plugin_admin_common($action, $admin, 'forum');
        }
    }

    /**
     * @return object
     */
    private function getBbcode()
    {
        global $pth;

        if (!isset($this->bbcode)) {
            $emoticonFolder = $pth['folder']['plugins'] . 'forum/images/';
            $this->bbcode = new BBCode($emoticonFolder);
        }
        return $this->bbcode;
    }

    /**
     * @param string $forum
     * @return mixed
     */
    public function main($forum)
    {
        global $e, $plugin_tx;

        $ptx = $plugin_tx['forum'];
        if (!preg_match('/^[a-z0-9\-]+$/u', $forum)) {
            $e .= '<li><b>' . $ptx['msg_invalid_name'] . '</b>' . '<br>'
                . $forum . '</li>' . "\n";
            return false;
        }
        $controller = new MainController($forum);
        $action = isset($_REQUEST['forum_actn']) ? $_REQUEST['forum_actn'] : 'default';
        $action .= 'Action';
        if (method_exists($controller, $action)) {
            ob_start();
            $controller->{$action}();
            return ob_get_clean();
        }
    }

    /**
     * @return string
     */
    public function commentPreview()
    {
        global $pth;

        $view = new View('preview');
        $view->comment = new HtmlString($this->getBbcode()->convert($_POST['data']));
        $view->templateStylesheet = $pth['file']['stylesheet'];
        $view->forumStylesheet = $pth['folder']['plugins'] . 'forum/css/stylesheet.css';
        return (string) $view;
    }
}
