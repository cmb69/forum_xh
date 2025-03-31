<?php

namespace Forum;

use PHPUnit\Framework\TestCase;
use ApprovalTests\Approvals;
use Forum\Model\FakeRepository;
use Plib\FakeRequest;
use Plib\FakeSystemChecker;
use Plib\View;

class ShowInfoTest extends TestCase
{
    private $sut;
    private $repository;

    public function setUp(): void
    {
        $this->repository = new FakeRepository("./content/forum/");
        $this->repository->options(["findForumsToMigrate" => ["old-forum"]]);
        $lang = XH_includeVar("./languages/en.php", 'plugin_tx')['forum'];
        $this->sut = new ShowInfo(
            "./plugins/forum/",
            new FakeSystemChecker(),
            $this->repository,
            new View("./views/", $lang)
        );
    }

    public function testRendersPluginInfo(): void
    {
        $response = ($this->sut)(new FakeRequest());
        Approvals::verifyHtml($response->output());
    }

    public function testMigratesForum(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?forum&forum_action=migrate&forum_forum=old-forum",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut)($request);
        $this->assertEquals(["old-forum"], $this->repository->lastMigration());
        $this->assertEquals("http://example.com/?forum", $response->location());
    }

    public function testReportsMissingForum(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?forum&forum_action=migrate",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut)($request);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsMigrationFailure(): void
    {
        $this->repository->options(["migrate" => false]);
        $request = new FakeRequest([
            "url" => "http://example.com/?forum&forum_action=migrate&forum_forum=old-forum",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut)($request);
        Approvals::verifyHtml($response->output());
    }
}
