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

use Fa\RequireCommand as FaRequireCommand;
use Forum\Infra\Authorizer;
use Forum\Infra\Contents;
use Forum\Infra\CsrfProtector;
use Forum\Infra\DateFormatter;
use Forum\Infra\Mailer;
use Forum\Infra\SystemChecker;
use Forum\Infra\View;
use Forum\Logic\BbCode;

class Dic
{
    public static function makeForum(): Forum
    {
        global $pth, $plugin_cf, $plugin_tx;
        return new Forum(
            $plugin_cf["forum"],
            $plugin_tx["forum"],
            $pth["folder"]["plugins"] . "forum/",
            new Contents(self::contentFolder()),
            self::makeBbCode(),
            new CsrfProtector,
            self::makeView(),
            new FaRequireCommand(),
            new Mailer($plugin_cf["forum"]),
            new DateFormatter(),
            new Authorizer()
        );
    }

    public static function makeShowInfo(): ShowInfo
    {
        global $pth;
        return new ShowInfo(
            $pth["folder"]["plugins"] . "forum/",
            self::contentFolder(),
            new SystemChecker(),
            self::makeView()
        );
    }

    private static function makeBbCode(): BbCode
    {
        global $pth, $plugin_tx;
        return new BbCode(
            $plugin_tx["forum"],
            $pth["folder"]["plugins"] . "forum/images/",
            $plugin_tx["forum"]["title_iframe"]
        );
    }

    private static function makeView(): View
    {
        global $pth, $plugin_tx;
        return new View($pth["folder"]["plugins"] . "forum/views/", $plugin_tx["forum"]);
    }

    private static function contentFolder(): string
    {
        global $pth;
        $folder = $pth["folder"]["content"] . $pth["folder"]["base"];
        $folder = preg_replace(['/\/\.\/$/', '/\/[^\/]+\/\.\.\//'], "/", $folder);
        $folder .= "forum/";
        return $folder;
    }
}
