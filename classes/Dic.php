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
    public static function makeShowForum(): ShowForum
    {
        /**
         * @var array{file:array<string,string>,folder:array<string,string} $pth
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $pth, $plugin_tx;

        return new ShowForum(
            "{$pth['folder']['plugins']}forum/",
            new Contents("{$pth['folder']['content']}{$pth['folder']['base']}forum/"),
            self::makeBbCode(),
            new CsrfProtector,
            new View("{$pth['folder']['plugins']}forum/views/", $plugin_tx['forum']),
            new FaRequireCommand(),
            new DateFormatter(),
            new Authorizer()
        );
    }

    public static function makeShowEditor(): ShowEditor
    {
        /**
         * @var array{file:array<string,string>,folder:array<string,string} $pth
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $pth, $plugin_tx;

        return new ShowEditor(
            $plugin_tx['forum'],
            "{$pth['folder']['plugins']}forum/",
            new Contents("{$pth['folder']['content']}{$pth['folder']['base']}forum/"),
            new CsrfProtector,
            new View("{$pth['folder']['plugins']}forum/views/", $plugin_tx['forum']),
            new FaRequireCommand(),
            new Authorizer()
        );
    }

    public static function makePostComment(): PostComment
    {
        /**
         * @var array{file:array<string,string>,folder:array<string,string} $pth
         * @var array<string,array<string,string>> $plugin_cf
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $pth, $plugin_cf, $plugin_tx;

        return new PostComment(
            $plugin_cf['forum'],
            $plugin_tx['forum'],
            new Contents("{$pth['folder']['content']}{$pth['folder']['base']}forum/"),
            new CsrfProtector,
            new Mailer($plugin_cf['forum']),
            new DateFormatter(),
            new Authorizer()
        );
    }

    public static function makeDeleteComment(): DeleteComment
    {
        /** @var array{file:array<string,string>,folder:array<string,string} $pth */
        global $pth;

        return new DeleteComment(
            new Contents("{$pth['folder']['content']}{$pth['folder']['base']}forum/"),
            new CsrfProtector,
            new Authorizer()
        );
    }

    public static function makeShowPreview(): ShowPreview
    {
        return new ShowPreview(self::makeBbCode());
    }

    public static function makeShowInfo(): ShowInfo
    {
        /**
         * @var array{file:array<string,string>,folder:array<string,string} $pth
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $pth, $plugin_tx;

        $contentFolder = $pth['folder']['content'];
        if ($pth['folder']['base'] === "../") {
            $contentFolder = dirname($contentFolder) . "/";
        }
        $contentFolder .= "forum/";
        return new ShowInfo(
            $pth['folder']['plugins'],
            $contentFolder,
            $plugin_tx['forum'],
            new SystemChecker(),
            new View("{$pth['folder']['plugins']}forum/views/", $plugin_tx['forum'])
        );
    }

    private static function makeBbCode(): BbCode
    {
        /**
         * @var array{file:array<string,string>,folder:array<string,string} $pth
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $pth, $plugin_tx;

        return new BbCode(
            $plugin_tx['forum'],
            "{$pth['folder']['plugins']}forum/images/",
            $plugin_tx['forum']['title_iframe']
        );
    }
}
