<?php

/**
 * Copyright 2023 Christoph M. Becker
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

use PHPUnit\Framework\TestCase;

class DicTest extends TestCase
{
    public function setUp(): void
    {
        global $pth, $plugin_cf, $plugin_tx;
        $pth = ["folder" => ["base" => "", "content" => "", "plugins" => ""]];
        $plugin_cf = ["forum" => []];
        $plugin_tx = ["forum" => ["title_iframe" => ""]];
    }

    public function testMakesShowForum(): void
    {
        $this->assertInstanceOf(ShowForum::class, Dic::makeShowForum());
    }

    public function testMakesShowEditor(): void
    {
        $this->assertInstanceOf(ShowEditor::class, Dic::makeShowEditor());
    }

    public function testMakesPostComment(): void
    {
        $this->assertInstanceOf(PostComment::class, Dic::makePostComment());
    }

    public function testMakesDeleteComment(): void
    {
        $this->assertInstanceOf(DeleteComment::class, Dic::makeDeleteComment());
    }

    public function testMakesShowPreview(): void
    {
        $this->assertInstanceOf(ShowPreview::class, Dic::makeShowPreview());
    }

    public function testMakesShowInfo(): void
    {
        $this->assertInstanceOf(ShowInfo::class, Dic::makeShowInfo());
    }
}
