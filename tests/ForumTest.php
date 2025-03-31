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
use Forum\Infra\FakeCsrfProtector;
use Forum\Infra\FakeMailer;
use Forum\Infra\FakeRepository;
use Forum\Infra\FakeRequest;
use Forum\Logic\BbCode;
use Forum\Value\Comment;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Plib\Random;
use Plib\View;

class ForumTest extends TestCase
{
    private $conf;
    private $bbcode;
    private $mailer;
    private $repository;
    private $random;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["forum"];
        $this->bbcode = $this->createStub(BbCode::class);
        $this->repository = new FakeRepository("vfs://root/forum/");
        $this->random = $this->createStub(Random::class);
    }

    private function sut(): Forum
    {
        $csrfProtector = new FakeCsrfProtector;
        $view = new View("./views/", XH_includeVar("./languages/en.php", 'plugin_tx')['forum']);
        $faRequireCommand = $this->createStub(RequireCommand::class);
        $this->mailer = new FakeMailer($this->conf, $view);
        return new Forum(
            $this->conf,
            "./plugins/forum/",
            $this->bbcode,
            $csrfProtector,
            $view,
            $faRequireCommand,
            $this->mailer,
            $this->repository,
            $this->random
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
        $this->repository->save("test", "0123456789abc", $this->comment());
        $request = new FakeRequest(["query" => "Forum"]);
        $response = ($this->sut())($request, "test");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersTopicOverview(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=AHQQ0TB341A6JX3CCM",
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        Approvals::verifyHtml($response->output());
    }

    public function testReportsNonExistentTopic(): void
    {
        $request = new FakeRequest(["query" => "Forum&forum_topic=AHQQ0TB341A6JX3CCM"]);
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
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "query" => "Forum&forum_action=edit&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef",
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCommentFormForReply(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "query" => "Forum&forum_action=create&forum_topic=AHQQ0TB341A6JX3CCM",
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
        $this->assertSame("text/html", $response->contentType());
    }

    public function testReportsMissingAuthorizationForPreview(): void
    {
        $request = new FakeRequest(["query" => "&forum_action=preview&forum_bbcode=something"]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">You are not authorized for this action!</p>\n", $response->output());
    }

    public function testCreatesNewTopic(): void
    {
        $this->random->method("bytes")->willReturn("3456789abcdef");
        $this->conf = ["mail_address" => "webmaster@example.com"] + $this->conf;
        $request = new FakeRequest([
            "time" => 1680508976,
            "query" => "Forum&forum_action=create",
            "post" => ["forum_title" => "A new Topic", "forum_text" => "A comment", "forum_do" => ""],
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        $saved = $this->repository->findComment("test", "6CT3ADHQ70WP2RK3CHJPC", "6CT3ADHQ70WP2RK3CHJPC");
        $this->assertEquals(new Comment("6CT3ADHQ70WP2RK3CHJPC", "A new Topic", "cmb", 1680508976, "A comment"), $saved);
        $this->assertEquals("http://example.com/?Forum&forum_topic=6CT3ADHQ70WP2RK3CHJPC", $response->location());
        Approvals::verifyList($this->mailer->lastMail());
    }

    public function testFailsToCreateNewTopic(): void
    {
        $this->random->method("bytes")->willReturn("3456789abcdef");
        $this->repository->options(["save" => false]);
        $request = new FakeRequest([
            "query" => "Forum&forum_action=create",
            "post" => ["forum_title" => "A new Topic", "forum_text" => "A comment", "forum_do" => ""],
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">The changes could not be stored!</p>\n", $response->output());
    }

    public function testUpdatesComment(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef&forum_action=edit",
            "post" => ["forum_title" => "Topic Title", "forum_text" => "A comment", "forum_do" => ""],
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        $saved = $this->repository->findComment("test", "AHQQ0TB341A6JX3CCM", "3456789abcdef");
        $this->assertEquals($this->comment()->with("Topic Title", "A comment"), $saved);
        $this->assertEquals("http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM", $response->location());
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
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=012345678&forum_action=edit",
            "post" => ["forum_text" => "A comment", "forum_do" => ""],
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">There is no such comment!</p>\n", $response->output());
    }

    public function testReportsMissingAuthorizationForPosting(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef&forum_action=edit",
            "post" => ["forum_text" => "A comment", "forum_do" => ""],
            "session" => ["username" => "somebody"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">You are not authorized for this action!</p>\n", $response->output());
    }

    public function testFailsToStoreUpdate(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $this->repository->options(["save" => false]);
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef&forum_action=edit",
            "post" => ["forum_text" => "A comment", "forum_do" => ""],
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">The changes could not be stored!</p>\n", $response->output());
    }

    public function testDeletesComment(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef&forum_action=delete",
            "post" => ["forum_do" => ""],
            "session" => ["username" => "cmb"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertNull($this->repository->findComment("test", "AHQQ0TB341A6JX3CCM", "3456789abcdef"));
        $this->assertEquals("http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM", $response->location());
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
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef&forum_action=delete",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">There is no such comment!</p>\n", $response->output());
    }

    public function testReportsMissingAuthorizationForDeleting(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef&forum_action=delete",
            "post" => ["forum_do" => ""],
            "session" => ["username" => "somebody"],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">You are not authorized for this action!</p>\n", $response->output());
    }

    public function testReportsFailureToStoreDeletion(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $this->repository->options(["delete" => false]);
        $request = new FakeRequest([
            "query" => "Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef&forum_action=delete",
            "post" => ["forum_do" => ""],
            "session" => ["username" => "cmb"]
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">The changes could not be stored!</p>\n", $response->output());
    }

    private function comment(): Comment
    {
        return new Comment("3456789abcdef", "Topic Title", "cmb", 1676130605, "a comment");
    }
}
