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
use Forum\Infra\DateFormatter;
use Forum\Infra\Mailer;
use Forum\Infra\View;
use Forum\Value\Comment;
use Forum\Value\Topic;

class MainControllerTest extends TestCase
{
    /** @var MainController */
    private $sut;

    /** @var Contents&MockObject */
    private $contents;

    /** @var BBCode&MockObject */
    private $bbcode;

    /** @var Authorizer&MockObject */
    private $authorizer;

    public function setUp(): void
    {
        $lang = XH_includeVar("./languages/en.php", 'plugin_tx')['forum'];
        $this->contents = $this->createStub(Contents::class);
        $this->bbcode = $this->createStub(BBCode::class);
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $view = new View("./views/", $lang);
        $faRequireCommand = $this->createStub(RequireCommand::class);
        $mailer = $this->createStub(Mailer::class);
        $dateFormatter = $this->createStub(DateFormatter::class);
        $this->authorizer = $this->createStub(Authorizer::class);
        $this->sut = new MainController(
            new Url("/", "Forum", []),
            XH_includeVar("./config/config.php", 'plugin_cf')['forum'],
            $lang,
            "./",
            $this->contents,
            $this->bbcode,
            $csrfProtector,
            $view,
            $faRequireCommand,
            $mailer,
            $dateFormatter,
            $this->authorizer
        );
    }

    public function testDefaultActionRendersForumOverview(): void
    {
        $this->contents->method('getSortedTopics')->willReturn(["1234" => $this->topic()]);
        $response = $this->sut->defaultAction("test");
        Approvals::verifyHtml($response->output());
    }

    public function testDefaultActionRendersTopicOverview(): void
    {
        $_GET = ['forum_topic' => "1234"];
        $this->contents->method('cleanId')->willReturn("1234");
        $this->contents->method('hasTopic')->willReturn(true);
        $this->contents->method('getTopicWithTitle')->willReturn(["Topic Title", ["2345" => $this->comment()]]);
        $response = $this->sut->defaultAction("test");
        Approvals::verifyHtml($response->output());
    }

    public function testNewActionRendersCommentForm(): void
    {
        $this->authorizer->method('isUser')->willReturn(true);
        $response = $this->sut->newAction("test");
        Approvals::verifyHtml($response->output());
    }

    public function testPostActionCreatesNewTopicAndRedirects(): void
    {
        $_POST = ['forum_title' => "A new Topic", 'forum_text' => "A comment"];
        $this->contents->method('getId')->willReturn("3456");
        $this->contents->expects($this->once())->method('createComment');
        $this->authorizer->method('isUser')->willReturn(true);
        $response = $this->sut->postAction("test");
        $this->assertEquals("http://example.com/index.php?Forum&forum_topic=3456", $response->location());
    }

    public function testPostActionUpdatesCommentAndRedirects(): void
    {
        $_POST = ['forum_topic' => "1234", 'forum_comment' => "3456", 'forum_text' => "A changed comment"];
        $this->contents->method('cleanId')->willReturn("1234");
        $this->authorizer->method('isUser')->willReturn(true);
        $response = $this->sut->postAction("test");
        $this->assertEquals("http://example.com/index.php?Forum&forum_topic=1234", $response->location());
    }

    public function testEditActionRendersCommentForm(): void
    {
        $_GET = ['forum_topic' => "1234", 'forum_comment' => "3456"];
        $this->contents->method('cleanId')->willReturnOnConsecutiveCalls("1234", "3456");
        $this->contents->method('getTopic')->willReturn(["3456" => $this->comment()]);
        $this->authorizer->method('isUser')->willReturn(true);
        $this->authorizer->method('mayModify')->willReturn(true);
        $response = $this->sut->editAction("test");
        Approvals::verifyHtml($response->output());
    }

    public function testDeleteActionDeletesCommentAndRedirects(): void
    {
        $_POST = ['forum_topic' => "1234", 'forum_comment' => "3456"];
        $this->contents->method('cleanId')->willReturnOnConsecutiveCalls("1234", "3456");
        $this->contents->expects($this->once())->method('deleteComment');
        $this->authorizer->method('isUser')->willReturn(true);
        $response = $this->sut->deleteAction("test");
        $this->assertEquals("http://example.com/index.php?Forum", $response->location());
    }

    public function testReplyActionRendersCommentForm(): void
    {
        $_GET = ['forum_topic' => "1234"];
        $this->contents->method('cleanId')->willReturn("1234");
        $this->authorizer->method('isUser')->willReturn(true);
        $response = $this->sut->replyAction("test");
        Approvals::verifyHtml($response->output());
    }

    public function testPreviewActionRendersBbCodeAndExits(): void
    {
        $_POST = ['data' => "something"];
        $this->bbcode->method('convert')->willReturn("else");
        $response = $this->sut->previewAction();
        $this->assertEquals("else", $response->output());
        $this->assertTrue($response->exit());
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
