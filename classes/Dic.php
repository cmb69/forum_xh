<?php

/**
 * Copyright (c) Christoph M. Becker
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

use Forum\Model\BbCode;
use Plib\CsrfProtector;
use Plib\DocumentStore;
use Plib\Random;
use Plib\SystemChecker;
use Plib\View;
use XH\Mail;

class Dic
{
    public static function makeForum(): ForumController
    {
        global $pth, $plugin_cf;
        return new ForumController(
            $plugin_cf["forum"],
            $pth["folder"]["plugins"] . "forum/",
            self::makeBbCode(),
            new CsrfProtector(),
            self::makeView(),
            new Mail(),
            new DocumentStore(self::contentFolder()),
            new Random()
        );
    }

    public static function makeShowInfo(): ShowInfo
    {
        global $pth;
        return new ShowInfo(
            $pth["folder"]["plugins"] . "forum/",
            new SystemChecker(),
            new DocumentStore(self::contentFolder()),
            self::makeView()
        );
    }

    private static function makeBbCode(): BbCode
    {
        global $pth, $plugin_tx;
        return new BbCode(
            $plugin_tx["forum"],
            $pth["folder"]["plugins"] . "forum/images/"
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
