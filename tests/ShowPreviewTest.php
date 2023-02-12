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
use Forum\Logic\BbCode;

class ShowPreviewTest extends TestCase
{
    /** @var ShowPreview */
    private $sut;

    /** @var BbCode&MockObject */
    private $bbCode;

    public function setUp(): void
    {
        $this->bbCode = $this->createStub(BbCode::class);
        $this->sut = new ShowPreview($this->bbCode);
    }

    public function testRendersBbCodeAndExits(): void
    {
        $_GET = ['forum_bbcode' => "something"];
        $this->bbCode->method('convert')->willReturn("else");
        $response = ($this->sut)();
        $this->assertEquals("else", $response->output());
        $this->assertTrue($response->exit());
    }
}
