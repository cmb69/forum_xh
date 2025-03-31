<?php

namespace Forum;

use ApprovalTests\Approvals;
use Forum\Model\BbCode;
use Forum\Model\Comment;
use Forum\Model\FakeRepository;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Plib\CsrfProtector;
use Plib\FakeRequest;
use Plib\Random;
use Plib\View;
use XH\Mail;

class ForumTest extends TestCase
{
    private $conf;
    private $bbcode;
    private $mail;
    private $repository;
    private $random;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["forum"];
        $this->bbcode = $this->createStub(BbCode::class);
        $this->repository = new FakeRepository("vfs://root/forum/");
        $this->random = $this->createStub(Random::class);
        $this->mail = $this->createMock(Mail::class);
    }

    private function sut(): Forum
    {
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $csrfProtector->method("token")->willReturn("e3c1b42a6098b48a39f9f54ddb3388f7");
        $view = new View("./views/", XH_includeVar("./languages/en.php", 'plugin_tx')['forum']);
        return new Forum(
            $this->conf,
            "./plugins/forum/",
            $this->bbcode,
            $csrfProtector,
            $view,
            $this->mail,
            $this->repository,
            $this->random
        );
    }

    public function testReportsInvalidTopicName(): void
    {
        $request = new FakeRequest();
        $response = ($this->sut())($request, "invalid_name");
        $this->assertStringContainsString(
            "&quot;invalid_name&quot; is an invalid forum name (may contain a-z, 0-9 and - only)!",
            $response->output()
        );
    }

    public function testRendersForumOverview(): void
    {
        $this->repository->save("test", "0123456789abc", $this->comment());
        $request = new FakeRequest(["url" => "http://example.com/?Forum"]);
        $response = ($this->sut())($request, "test");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersTopicOverview(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM",
            "username" => "cmb",
        ]);
        $response = ($this->sut())($request, "test");
        Approvals::verifyHtml($response->output());
    }

    public function testReportsNonExistentTopic(): void
    {
        $request = new FakeRequest(["url" => "http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM"]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">There is no such topic!</p>\n", $response->output());
    }

    public function testRendersCommentFormForNewPost(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_action=create",
            "username" => "cmb",
        ]);
        $response = ($this->sut())($request, "test");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCommentForm(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_action=edit&forum_topic=AHQQ0TB341A6JX3CCM"
                . "&forum_comment=3456789abcdef",
            "username" => "cmb",
        ]);
        $response = ($this->sut())($request, "test");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCommentFormForReply(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_action=create&forum_topic=AHQQ0TB341A6JX3CCM",
            "username" => "cmb",
        ]);
        $response = ($this->sut())($request, "test");
        Approvals::verifyHtml($response->output());
    }

    public function testReportsMissingIdForEditing(): void
    {
        $request = new FakeRequest(["url" => "http://example.com/?Forum&forum_action=edit&forum_topic=0123456789abc"]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">ID is missing!</p>\n", $response->output());
    }

    public function testRendersBbCodeAndExits(): void
    {
        $this->bbcode->method('convert')->willReturn("else");
        $request = new FakeRequest([
            "url" => "http://example.com/?&forum_action=preview&forum_bbcode=something",
            "username" => "cmb",
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("else", $response->output());
        $this->assertSame("text/html", $response->contentType());
    }

    public function testReportsMissingAuthorizationForPreview(): void
    {
        $request = new FakeRequest(["url" => "http://example.com/?&forum_action=preview&forum_bbcode=something"]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">You are not authorized for this action!</p>\n", $response->output());
    }

    public function testCreatesNewTopic(): void
    {
        $this->random->method("bytes")->willReturn("123456789abcdef");
        $this->conf = ["mail_address" => "webmaster@example.com"] + $this->conf;
        $this->mail->expects($this->once())->method("setTo")->with("webmaster@example.com");
        $this->mail->expects($this->once())->method("setSubject")->with("A new comment has been posted");
        $this->mail->expects($this->once())->method("setMessage")->with($this->stringContains(
            "<http://example.com/?Forum&forum_action=create&forum_topic=64P36D1L6ORJGEB1C9HM8PB6>"
        ));
        $this->mail->expects($this->once())->method("addHeader")->with("From", "webmaster@example.com");
        $this->mail->expects($this->once())->method("send")->willReturn(true);
        $request = new FakeRequest([
            "time" => 1680508976,
            "url" => "http://example.com/?Forum&forum_action=create",
            "post" => ["forum_title" => "A new Topic", "forum_text" => "A comment", "forum_do" => ""],
            "username" => "cmb",
        ]);
        $response = ($this->sut())($request, "test");
        $saved = $this->repository->findComment("test", "64P36D1L6ORJGEB1C9HM8PB6", "64P36D1L6ORJGEB1C9HM8PB6");
        $this->assertEquals(
            new Comment("64P36D1L6ORJGEB1C9HM8PB6", "A new Topic", "cmb", 1680508976, "A comment"),
            $saved
        );
        $this->assertEquals(
            "http://example.com/?Forum&forum_topic=64P36D1L6ORJGEB1C9HM8PB6",
            $response->location()
        );
    }

    public function testFailsToCreateNewTopic(): void
    {
        $this->random->method("bytes")->willReturn("3456789abcdef");
        $this->repository->options(["save" => false]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_action=create",
            "post" => ["forum_title" => "A new Topic", "forum_text" => "A comment", "forum_do" => ""],
            "username" => "cmb",
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">The changes could not be stored!</p>\n", $response->output());
    }

    public function testUpdatesComment(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef"
                . "&forum_action=edit",
            "post" => ["forum_title" => "Topic Title", "forum_text" => "A comment", "forum_do" => ""],
            "username" => "cmb",
        ]);
        $response = ($this->sut())($request, "test");
        $saved = $this->repository->findComment("test", "AHQQ0TB341A6JX3CCM", "3456789abcdef");
        $this->assertEquals($this->comment()->with("Topic Title", "A comment"), $saved);
        $this->assertEquals("http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM", $response->location());
    }

    public function testReportsMissingIdWhenPosting(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_topic=0123456789abc&forum_action=edit",
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
            "url" => "http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=012345678"
                . "&forum_action=edit",
            "post" => ["forum_text" => "A comment", "forum_do" => ""],
            "username" => "cmb",
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">There is no such comment!</p>\n", $response->output());
    }

    public function testReportsMissingAuthorizationForPosting(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef"
                . "&forum_action=edit",
            "post" => ["forum_text" => "A comment", "forum_do" => ""],
            "username" => "somebody",
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">You are not authorized for this action!</p>\n", $response->output());
    }

    public function testFailsToStoreUpdate(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $this->repository->options(["save" => false]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef"
                . "&forum_action=edit",
            "post" => ["forum_text" => "A comment", "forum_do" => ""],
            "username" => "cmb",
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">The changes could not be stored!</p>\n", $response->output());
    }

    public function testDeletesComment(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef"
                . "&forum_action=delete",
            "post" => ["forum_do" => ""],
            "username" => "cmb",
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertNull($this->repository->findComment("test", "AHQQ0TB341A6JX3CCM", "3456789abcdef"));
        $this->assertEquals("http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM", $response->location());
    }

    public function testReportsMissingIdWhenDeleting(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_topic=0123456789abc&forum_action=delete",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">ID is missing!</p>\n", $response->output());
    }

    public function testReportsNonExistentCommentWhenDeleting(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef"
                . "&forum_action=delete",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">There is no such comment!</p>\n", $response->output());
    }

    public function testReportsMissingAuthorizationForDeleting(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef"
                . "&forum_action=delete",
            "post" => ["forum_do" => ""],
            "username" => "somebody",
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">You are not authorized for this action!</p>\n", $response->output());
    }

    public function testReportsFailureToStoreDeletion(): void
    {
        $this->repository->save("test", "AHQQ0TB341A6JX3CCM", $this->comment());
        $this->repository->options(["delete" => false]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Forum&forum_topic=AHQQ0TB341A6JX3CCM&forum_comment=3456789abcdef"
                . "&forum_action=delete",
            "post" => ["forum_do" => ""],
            "username" => "cmb",
        ]);
        $response = ($this->sut())($request, "test");
        $this->assertEquals("<p class=\"xh_fail\">The changes could not be stored!</p>\n", $response->output());
    }

    private function comment(): Comment
    {
        return new Comment("3456789abcdef", "Topic Title", "cmb", 1676130605, "a comment");
    }
}
