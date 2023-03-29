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
use Forum\Infra\DateFormatter;
use Forum\Infra\FakeRequest;
use Forum\Infra\Mailer;
use Forum\Infra\Request;
use Forum\Value\Url;

class PostCommentTest extends TestCase
{
    /** @var PostComment */
    private $sut;

    /** @var Contents&MockObject */
    private $contents;

    /** @var Authorizer&MockObject */
    private $authorizer;

    public function setUp(): void
    {
        $lang = XH_includeVar("./languages/en.php", 'plugin_tx')['forum'];
        $this->contents = $this->createStub(Contents::class);
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $mailer = $this->createStub(Mailer::class);
        $dateFormatter = $this->createStub(DateFormatter::class);
        $this->authorizer = $this->createStub(Authorizer::class);
        $this->sut = new PostComment(
            XH_includeVar("./config/config.php", 'plugin_cf')['forum'],
            $lang,
            $this->contents,
            $csrfProtector,
            $mailer,
            $dateFormatter,
            $this->authorizer
        );
    }

    public function testCreatesNewTopicAndRedirects(): void
    {
        $this->contents->method('getId')->willReturn("3456");
        $this->contents->expects($this->once())->method('createComment');
        $this->authorizer->method('isUser')->willReturn(true);
        $request = new FakeRequest([
            "query" => "Forum",
            "post" => ["forum_title" => "A new Topic", "forum_text" => "A comment"],
        ]);
        $response = ($this->sut)("test", $request);
        $this->assertEquals("http://example.com/?Forum&forum_topic=3456", $response->location());
    }

    public function testUpdatesCommentAndRedirects(): void
    {
        $this->contents->method('cleanId')->willReturn("1234");
        $this->authorizer->method('isUser')->willReturn(true);
        $request = new FakeRequest([
            "query" => "Forum",
            "post" => [
                "forum_topic" => "1234",
                "forum_comment" => "3456",
                "forum_text" => "A comment",
            ]
        ]);
        $response = ($this->sut)("test", $request);
        $this->assertEquals("http://example.com/?Forum&forum_topic=1234", $response->location());
    }
}
