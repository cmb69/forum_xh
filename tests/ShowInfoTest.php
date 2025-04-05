<?php

namespace Forum;

use PHPUnit\Framework\TestCase;
use ApprovalTests\Approvals;
use Forum\Model\BaseTopic;
use Forum\Model\Forum;
use Forum\Model\Topic;
use Plib\DocumentStore;
use Plib\FakeRequest;
use Plib\FakeSystemChecker;
use Plib\View;

class ShowInfoTest extends TestCase
{
    private $sut;
    private $repository;
    private $store;

    public function setUp(): void
    {
        $this->store = $this->createMock(DocumentStore::class);
        $this->store->method("folder")->willReturn("./content/forum/");
        $lang = XH_includeVar("./languages/en.php", 'plugin_tx')['forum'];
        $this->sut = new ShowInfo(
            "./plugins/forum/",
            new FakeSystemChecker(),
            $this->store,
            new View("./views/", $lang)
        );
    }

    public function testRendersPluginInfo(): void
    {
        $this->store->method("find")->willReturn(["old-forum/topics.dat"]);
        $response = ($this->sut)(new FakeRequest());
        Approvals::verifyHtml($response->output());
    }

    public function testMigratesForum(): void
    {
        $this->store->method("retrieve")->willReturnOnConsecutiveCalls(
            $this->forum(),
            new Topic([])
        );
        $this->store->method("update")->willReturnOnConsecutiveCalls(
            new Forum("", []),
            new Topic([])
        );
        $this->store->method("commit")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?forum&forum_action=migrate&forum_forum=old-forum",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut)($request);
        // $this->assertEquals(["old-forum"], $this->repository->lastMigration());
        $this->assertEquals("http://example.com/?forum", $response->location());
    }

    public function testReportsMissingForum(): void
    {
        $this->store->method("find")->willReturn(["old-forum/topics.dat"]);
        $request = new FakeRequest([
            "url" => "http://example.com/?forum&forum_action=migrate",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut)($request);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsMigrationFailure(): void
    {
        $this->store->method("retrieve")->willReturn(new Forum("old-forum", []));
        $this->store->method("update")->willReturn(new Forum("", []));
        $this->store->method("commit")->willReturn(false);
        $this->store->method("find")->willReturn(["old-forum/topics.dat"]);
        $request = new FakeRequest([
            "url" => "http://example.com/?forum&forum_action=migrate&forum_forum=old-forum",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut)($request);
        Approvals::verifyHtml($response->output());
    }

    private function forum(): Forum
    {
        return new Forum("old-forum", [new BaseTopic("12345", "Topic Title", 1, "cmb", 1676130605)]);
    }
}
