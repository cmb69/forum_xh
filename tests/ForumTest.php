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
use Forum\Infra\Contents;
use Forum\Infra\DateFormatter;
use Forum\Infra\FakeCsrfProtector;
use Forum\Infra\FakeMailer;
use Forum\Infra\FakeRequest;
use Forum\Infra\View;
use Forum\Logic\BbCode;
use Forum\Value\Comment;
use Forum\Value\Topic;
use PHPUnit\Framework\TestCase;

class ForumTest extends TestCase
{
    private $conf;
    private $contents;
    private $bbcode;
    private $mailer;

    public function setUp(): void
    {
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["forum"];
        $this->contents = $this->createStub(Contents::class);
        $this->bbcode = $this->createStub(BbCode::class);
    }

    private function sut(): Forum
    {
        $csrfProtector = new FakeCsrfProtector;
        $view = new View("./views/", XH_includeVar("./languages/en.php", 'plugin_tx')['forum']);
        $faRequireCommand = $this->createStub(RequireCommand::class);
        $dateFormatter = $this->createStub(DateFormatter::class);
        $this->mailer = new FakeMailer($this->conf, $dateFormatter, $view);
        return new Forum(
            $this->conf,
            "./plugins/forum/",
            $this->contents,
            $this->bbcode,
            $csrfProtector,
            $view,
            $faRequireCommand,
            $this->mailer,
            $dateFormatter
        );
    }

    public function testReportsInvalidTopicName(): void
    {
        $request = new FakeRequest;
        $response = ($this->sut())($request, "invalid_name");
        $this->assertEquals(
            "<p class=\"xh_fail\">&quot;invalid_name&quot; is an invalid forum name (may contain a-z, 0-9 and - only)!</p>\n",
            $response->output()
        );
    }

    public function testRendersForumOverview(): void
    {
        $this->contents->method('getSortedTopics')->willReturn(["1234" => $this->topic()]);
        $request = new FakeRequest(["query" => "Forum"]);
        $response = ($this->sut())($request, "test");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersTopicOverview(): void
    {
        $this->contents->method('hasTopic')->willReturn(true);
        $this->contents->method('getTopicWithTitle')->willReturn(["Topic Title", ["2345" => $this->comment()]]);
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=0123456789abc",
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        Approvals::verifyHtml($response->output());
    }

    public function testReportsNonExistentTopic(): void
    {
        $this->contents->method('hasTopic')->willReturn(false);
        $request = new FakeRequest(["query" => "Forum&forum_topic=0123456789abc"]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">There is no such topic!</p>\n", $response->output());
    }

    public function testRendersCommentFormForNewPost(): void
    {
        $request = new FakeRequest([
            "query" => "Forum&forum_action=create",
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCommentForm(): void
    {
        $this->contents->method('getTopic')->willReturn(["3456789abcdef" => $this->comment()]);
        $this->contents->method("findTopic")->willReturn($this->topic());
        $this->contents->method("findComment")->willReturn($this->comment());
        $request = new FakeRequest([
            "query" => "Forum&forum_action=edit&forum_topic=0123456789abc&forum_comment=3456789abcdef",
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCommentFormForReply(): void
    {
        $this->contents->method("findTopic")->willReturn($this->topic());
        $request = new FakeRequest([
            "query" => "Forum&forum_action=create&forum_topic=0123456789abc",
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        Approvals::verifyHtml($response->output());
    }

    public function testReportsMissingIdForEditing(): void
    {
        $request = new FakeRequest(["query" => "Forum&forum_action=edit&forum_topic=0123456789abc"]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">ID is missing!</p>\n", $response->output());
    }

    public function testRendersBbCodeAndExits(): void
    {
        $this->bbcode->method('convert')->willReturn("else");
        $request = new FakeRequest([
            "query" => "&forum_action=preview&forum_bbcode=something",
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("else", $response->output());
        $this->assertTrue($response->exit());
    }

    public function testReportsMissingAuthorizationForPreview(): void
    {
        $request = new FakeRequest(["query" => "&forum_action=preview&forum_bbcode=something"]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">You are not authorized for this action!</p>\n", $response->output());
    }

    public function testCreatesNewTopicAndRedirects(): void
    {
        $this->contents->method('getId')->willReturn("3456789abcdef");
        $this->contents->expects($this->once())->method('createComment')->willReturn(true);
        $request = new FakeRequest([
            "query" => "Forum&forum_action=create",
            "post" => ["forum_title" => "A new Topic", "forum_text" => "A comment", "forum_do" => ""],
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("http://example.com/?Forum&forum_topic=3456789abcdef", $response->location());
    }

    public function testCreatesNewTopicAndSendsMail(): void
    {
        $this->contents->method('getId')->willReturn("3456");
        $this->contents->expects($this->once())->method('createComment')->willReturn(true);
        $this->conf = ["mail_address" => "webmaster@example.com"] + $this->conf;
        $request = new FakeRequest([
            "query" => "Forum&forum_action=create",
            "post" => ["forum_title" => "A new Topic", "forum_text" => "A comment", "forum_do" => ""],
            "session" => ["username" => "cmb"],
        ]);
        ($this->sut())($request, "test");
        Approvals::verifyList($this->mailer->lastMail());
    }

    public function testFailsToCreateNewTopic(): void
    {
        $this->contents->method('getId')->willReturn("3456789abcdef");
        $this->contents->expects($this->once())->method('createComment')->willReturn(false);
        $request = new FakeRequest([
            "query" => "Forum&forum_action=create",
            "post" => ["forum_title" => "A new Topic", "forum_text" => "A comment", "forum_do" => ""],
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">The changes could not be stored!</p>\n", $response->output());
    }

    public function testUpdatesCommentAndRedirects(): void
    {
        $this->contents->method("findTopic")->willReturn($this->topic());
        $this->contents->method("findComment")->willReturn($this->comment());
        $this->contents->expects($this->once())->method("updateComment")->willReturn(true);
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=0123456789abc&forum_comment=3456789abcdef&forum_action=edit",
            "post" => ["forum_text" => "A comment", "forum_do" => ""],
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("http://example.com/?Forum&forum_topic=0123456789abc", $response->location());
    }
    
    public function testReportsMissingIdWhenPosting(): void
    {
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=0123456789abc&forum_action=edit",
            "post" => [
                "forum_text" => "A comment",
                "forum_do" => "",
            ]
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">ID is missing!</p>\n", $response->output());
    }

    public function testReportsNonExistentCommentWhenUpdating(): void
    {
        $this->contents->method("findTopic")->willReturn($this->topic());
        $this->contents->method("findComment")->willReturn(null);
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=0123456789abc&forum_comment=3456789abcdef&forum_action=edit",
            "post" => ["forum_text" => "A comment", "forum_do" => ""],
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">There is no such comment!</p>\n", $response->output());
    }

    public function testReportsMissingAuthorizationForPosting(): void
    {
        $this->contents->method("findTopic")->willReturn($this->topic());
        $this->contents->method("findComment")->willReturn($this->comment());
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=0123456789abc&forum_comment=3456789abcdef&forum_action=edit",
            "post" => ["forum_text" => "A comment", "forum_do" => ""],
            "session" => ["username" => "somebody"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">You are not authorized for this action!</p>\n", $response->output());
    }

    public function testFailsToStoreUpdate(): void
    {
        $this->contents->method("findTopic")->willReturn($this->topic());
        $this->contents->method("findComment")->willReturn($this->comment());
        $this->contents->expects($this->once())->method("updateComment")->willReturn(false);
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=0123456789abc&forum_comment=3456789abcdef&forum_action=edit",
            "post" => ["forum_text" => "A comment", "forum_do" => ""],
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">The changes could not be stored!</p>\n", $response->output());
    }

    public function testDeletesCommentAndRedirects(): void
    {
        $this->contents->method("findComment")->willReturn($this->comment());
        $this->contents->expects($this->once())->method('deleteComment')->willReturn(true);
        $this->contents->method("hasTopic")->willReturn(true);
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=0123456789abc&forum_comment=3456789abcdef&forum_action=delete",
            "post" => ["forum_do" => ""],
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("http://example.com/?Forum&forum_topic=0123456789abc", $response->location());
    }

    public function testReportsMissingIdWhenDeleting(): void
    {
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=0123456789abc&forum_action=delete",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">ID is missing!</p>\n", $response->output());
    }

    public function testReportsNonExistentCommentWhenDeleting(): void
    {
        $this->contents->method("findComment")->willReturn(null);
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=0123456789abc&forum_comment=3456789abcdef&forum_action=delete",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">There is no such comment!</p>\n", $response->output());
    }

    public function testReportsMissingAuthorizationForDeleting(): void
    {
        $this->contents->method("findComment")->willReturn($this->comment());
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=0123456789abc&forum_comment=3456789abcdef&forum_action=delete",
            "post" => ["forum_do" => ""],
            "session" => ["username" => "somebody"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">You are not authorized for this action!</p>\n", $response->output());
    }

    public function testReportsFailureToStoreDeletion(): void
    {
        $this->contents->method("findComment")->willReturn($this->comment());
        $this->contents->expects($this->once())->method('deleteComment')->willReturn(false);
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=0123456789abc&forum_comment=3456789abcdef&forum_action=delete",
            "post" => ["forum_do" => ""],
            "session" => ["username" => "cmb"]
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">The changes could not be stored!</p>\n", $response->output());
    }

    private function topic(): Topic
    {
        return new Topic("0123456789abc", "Topic Title", 1, "cmb", 1676130605);
    }

    private function comment(): Comment
    {
        return new Comment("3456789abcdef", "cmb", 1676130605, "a comment");
    }
}
