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

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;

use XH\CSRFProtection as CsrfProtector;
use Fa\RequireCommand;

use Forum\Infra\Authorizer;
use Forum\Infra\Contents;
use Forum\Infra\DateFormatter;
use Forum\Infra\Request;
use Forum\Infra\Url;
use Forum\Infra\View;
use Forum\Logic\BbCode;
use Forum\Value\Comment;
use Forum\Value\Topic;

class ShowForumTest extends TestCase
{
    /** @var ShowForum */
    private $sut;

    /** @var Contents&MockObject */
    private $contents;

    /** @var BbCode&MockObject */
    private $bbcode;

    /** @var Authorizer&MockObject */
    private $authorizer;

    public function setUp(): void
    {
        $this->contents = $this->createStub(Contents::class);
        $this->bbcode = $this->createStub(BbCode::class);
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $view = new View("./views/", XH_includeVar("./languages/en.php", 'plugin_tx')['forum']);
        $faRequireCommand = $this->createStub(RequireCommand::class);
        $dateFormatter = $this->createStub(DateFormatter::class);
        $this->authorizer = $this->createStub(Authorizer::class);
        $this->sut = new ShowForum(
            "./",
            $this->contents,
            $this->bbcode,
            $csrfProtector,
            $view,
            $faRequireCommand,
            $dateFormatter,
            $this->authorizer
        );
    }

    public function testRendersForumOverview(): void
    {
        $this->contents->method('getSortedTopics')->willReturn(["1234" => $this->topic()]);
        $request = $this->createStub(Request::class);
        $request->method("url")->willReturn(new Url("/", "Forum", []));
        $response = ($this->sut)("test", $request);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersTopicOverview(): void
    {
        $this->contents->method('cleanId')->willReturn("1234");
        $this->contents->method('hasTopic')->willReturn(true);
        $this->contents->method('getTopicWithTitle')->willReturn(["Topic Title", ["2345" => $this->comment()]]);
        $request = $this->createStub(Request::class);
        $request->method("url")->willReturn(new Url("/", "Forum", []));
        $request->method("get")->willReturnMap([["forum_topic", "1234"]]);
        $response = ($this->sut)("test", $request);
        Approvals::verifyHtml($response->output());
    }

    private function topic(): Topic
    {
        return new Topic("Topic Title", 1, "cmb", 1676130605);
    }

    private function comment(): Comment
    {
        return new Comment("cmb", 1676130605, "a comment");
    }
}
