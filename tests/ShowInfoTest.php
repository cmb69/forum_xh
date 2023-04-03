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
use ApprovalTests\Approvals;
use Forum\Infra\FakeRepository;
use Forum\Infra\FakeRequest;
use Forum\Infra\View;
use Forum\Infra\SystemChecker;

class ShowInfoTest extends TestCase
{
    private $sut;
    private $repository;

    public function setUp(): void
    {
        $systemChecker = $this->createStub(SystemChecker::class);
        $this->repository = new FakeRepository("./content/forum/");
        $this->repository->options(["findForumsToMigrate" => ["old-forum"]]);
        $lang = XH_includeVar("./languages/en.php", 'plugin_tx')['forum'];
        $this->sut = new ShowInfo(
            "./plugins/forum/",
            $systemChecker,
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
            "query" => "forum&forum_action=migrate&forum_forum=old-forum",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut)($request);
        $this->assertEquals(["old-forum"], $this->repository->lastMigration());
        $this->assertEquals("http://example.com/?forum", $response->location());
    }

    public function testReportsMissingForum(): void
    {
        $request = new FakeRequest([
            "query" => "forum&forum_action=migrate",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut)($request);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsMigrationFailure(): void
    {
        $this->repository->options(["migrate" => false]);
        $request = new FakeRequest([
            "query" => "forum&forum_action=migrate&forum_forum=old-forum",
            "post" => ["forum_do" => ""],
        ]);
        $response = ($this->sut)($request);
        Approvals::verifyHtml($response->output());
    }
}
