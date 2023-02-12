<?php

/**
 * Copyright 2012-2023 Christoph M. Becker
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

use XH\CSRFProtection as CsrfProtector;
use Fa\RequireCommand as FaRequireCommand;
use Forum\Infra\Authorizer;
use Forum\Infra\DateFormatter;
use Forum\Infra\Mailer;
use Forum\Infra\SystemChecker;
use Forum\Infra\View;

class Dic
{
    public static function makeMainController(): MainController
    {
        global $pth, $plugin_cf, $plugin_tx;

        return new MainController(
            self::makeUrl(),
            $plugin_cf['forum'],
            $plugin_tx['forum'],
            "{$pth['folder']['plugins']}forum/",
            new Contents("{$pth['folder']['content']}{$pth['folder']['base']}forum/"),
            new BBCode(
                $plugin_tx['forum'],
                "{$pth['folder']['plugins']}forum/images/",
                $plugin_tx['forum']['title_iframe']
            ),
            self::makeCsrfProtector(),
            new View("{$pth['folder']['plugins']}forum/views", $plugin_tx['forum']),
            new FaRequireCommand(),
            new Mailer($plugin_cf['forum']),
            new DateFormatter(),
            new Authorizer()
        );
    }

    public static function makeInfoController(): InfoController
    {
        global $pth, $plugin_tx;

        return new InfoController(
            $pth['folder']['plugins'],
            "{$pth['folder']['content']}{$pth['folder']['base']}forum/",
            $plugin_tx['forum'],
            new SystemChecker(),
            new View("{$pth['folder']['plugins']}forum/views", $plugin_tx['forum'])
        );
    }

    private static function makeCsrfProtector(): CsrfProtector
    {
        global $_XH_csrfProtection;

        if (isset($_XH_csrfProtection)) {
            return $_XH_csrfProtection;
        }
        return new CsrfProtector('forum_token');
    }

    private static function makeUrl(): Url
    {
        global $sn, $su;

        return new Url($sn, $su, []);
    }
}
