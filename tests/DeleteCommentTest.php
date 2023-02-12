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

use XH\CSRFProtection as CsrfProtector;

use Forum\Infra\Authorizer;
use Forum\Infra\Contents;
use Forum\Infra\Request;
use Forum\Infra\Url;

class DeleteCommentTest extends TestCase
{
    /** @var MainController */
    private $sut;

    /** @var Contents&MockObject */
    private $contents;

    /** @var Authorizer&MockObject */
    private $authorizer;

    public function setUp(): void
    {
        $this->contents = $this->createStub(Contents::class);
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $this->authorizer = $this->createStub(Authorizer::class);
        $this->sut = new DeleteComment(
            $this->contents,
            $csrfProtector,
            $this->authorizer
        );
    }

    public function testDeletesCommentAndRedirects(): void
    {
        $this->contents->method('cleanId')->willReturnOnConsecutiveCalls("1234", "3456");
        $this->contents->expects($this->once())->method('deleteComment');
        $this->authorizer->method('isUser')->willReturn(true);
        $request = $this->createStub(Request::class);
        $request->method("url")->willReturn(new Url("/", "Forum", []));
        $request->method('post')->willReturnMap([['forum_topic', "1234"], ['forum_comment', "3456"]]);
        $response = ($this->sut)("test", $request);
        $this->assertEquals("http://example.com/index.php?Forum", $response->location());
    }
}
