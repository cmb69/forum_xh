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
use Fa\RequireCommand;
use Forum\Infra\Authorizer;
use Forum\Infra\Contents;
use Forum\Infra\DateFormatter;
use Forum\Infra\FakeCsrfProtector;
use Forum\Infra\FakeRequest;
use Forum\Infra\Mailer;
use Forum\Infra\View;
use Forum\Logic\BbCode;
use Forum\Value\Comment;
use Forum\Value\Topic;
use PHPUnit\Framework\TestCase;

class ForumTest extends TestCase
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
        $csrfProtector = new FakeCsrfProtector;
        $view = new View("./views/", XH_includeVar("./languages/en.php", 'plugin_tx')['forum']);
        $faRequireCommand = $this->createStub(RequireCommand::class);
        $dateFormatter = $this->createStub(DateFormatter::class);
        $this->authorizer = $this->createStub(Authorizer::class);
        $this->sut = new Forum(
            XH_includeVar("./config/config.php", 'plugin_cf')['forum'],
            XH_includeVar("./languages/en.php", 'plugin_tx')['forum'],
            "./",
            $this->contents,
            $this->bbcode,
            $csrfProtector,
            $view,
            $faRequireCommand,
            $this->createStub(Mailer::class),
            $dateFormatter,
            $this->authorizer
        );
    }

    public function testReportsInvalidTopicName(): void
    {
        $request = new FakeRequest;
        $response = ($this->sut)("invalid_name", $request);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersForumOverview(): void
    {
        $this->contents->method('getSortedTopics')->willReturn(["1234" => $this->topic()]);
        $request = new FakeRequest(["query" => "Forum"]);
        $response = ($this->sut)("test", $request);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersTopicOverview(): void
    {
        $this->contents->method('cleanId')->willReturn("1234");
        $this->contents->method('hasTopic')->willReturn(true);
        $this->contents->method('getTopicWithTitle')->willReturn(["Topic Title", ["2345" => $this->comment()]]);
        $request = new FakeRequest(["query" => "Forum&forum_topic=1234"]);
        $response = ($this->sut)("test", $request);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCommentFormForNewPost(): void
    {
        $this->authorizer->method('isUser')->willReturn(true);
        $request = new FakeRequest(["query" => "Forum&forum_actn=edit"]);
        $response = ($this->sut)("test", $request);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCommentForm(): void
    {
        $this->contents->method('cleanId')->willReturnOnConsecutiveCalls("1234", "3456");
        $this->contents->method('getTopic')->willReturn(["3456" => $this->comment()]);
        $this->authorizer->method('isUser')->willReturn(true);
        $this->authorizer->method('mayModify')->willReturn(true);
        $request = new FakeRequest(["query" => "Forum&forum_actn=edit&forum_topic=1234&forum_comment=3456"]);
        $response = ($this->sut)("test", $request);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCommentFormForReply(): void
    {
        $this->contents->method('cleanId')->willReturnOnConsecutiveCalls("1234", null);
        $this->authorizer->method('isUser')->willReturn(true);
        $request = new FakeRequest(["query" => "Forum&forum_actn=edit&forum_topic=1234"]);
        $response = ($this->sut)("test", $request);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersBbCodeAndExits(): void
    {
        $this->bbcode->method('convert')->willReturn("else");
        $request = new FakeRequest(["query" => "&forum_actn=preview&forum_bbcode=something"]);
        $response = ($this->sut)("test", $request);
        $this->assertEquals("else", $response->output());
        $this->assertTrue($response->exit());
    }

    public function testCreatesNewTopicAndRedirects(): void
    {
        $this->contents->method('getId')->willReturn("3456");
        $this->contents->expects($this->once())->method('createComment');
        $this->authorizer->method('isUser')->willReturn(true);
        $request = new FakeRequest([
            "query" => "Forum&forum_actn=post",
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
            "query" => "Forum&forum_actn=post",
            "post" => [
                "forum_topic" => "1234",
                "forum_comment" => "3456",
                "forum_text" => "A comment",
            ]
        ]);
        $response = ($this->sut)("test", $request);
        $this->assertEquals("http://example.com/?Forum&forum_topic=1234", $response->location());
    }

    public function testDeletesCommentAndRedirects(): void
    {
        $this->contents->method('cleanId')->willReturnOnConsecutiveCalls("1234", "3456");
        $this->contents->expects($this->once())->method('deleteComment');
        $this->authorizer->method('isUser')->willReturn(true);
        $request = new FakeRequest([
            "query" => "Forum&forum_actn=delete",
            "post" => ["forum_topic" => "1234", "forum_comment" =>  "3456"],
        ]);
        $response = ($this->sut)("test", $request);
        $this->assertEquals("http://example.com/?Forum", $response->location());
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
