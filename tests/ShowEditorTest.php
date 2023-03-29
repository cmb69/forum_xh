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
use Forum\Infra\FakeCsrfProtector;
use Forum\Infra\FakeRequest;
use Forum\Infra\View;
use Forum\Value\Comment;
use PHPUnit\Framework\TestCase;

class ShowEditorTest extends TestCase
{
    /** @var MainController */
    private $sut;

    /** @var Contents&MockObject */
    private $contents;

    /** @var Authorizer&MockObject */
    private $authorizer;

    public function setUp(): void
    {
        $lang = XH_includeVar("./languages/en.php", 'plugin_tx')['forum'];
        $this->contents = $this->createStub(Contents::class);
        $csrfProtector = new FakeCsrfProtector;
        $view = new View("./views/", $lang);
        $faRequireCommand = $this->createStub(RequireCommand::class);
        $this->authorizer = $this->createStub(Authorizer::class);
        $this->sut = new ShowEditor(
            $lang,
            "./",
            $this->contents,
            $csrfProtector,
            $view,
            $faRequireCommand,
            $this->authorizer
        );
    }

    public function testRendersCommentFormForNewPost(): void
    {
        $this->authorizer->method('isUser')->willReturn(true);
        $request = new FakeRequest(["query" => "Forum"]);
        $response = ($this->sut)("test", $request);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCommentForm(): void
    {
        $this->contents->method('cleanId')->willReturnOnConsecutiveCalls("1234", "3456");
        $this->contents->method('getTopic')->willReturn(["3456" => $this->comment()]);
        $this->authorizer->method('isUser')->willReturn(true);
        $this->authorizer->method('mayModify')->willReturn(true);
        $request = new FakeRequest(["query" => "Forum&forum_topic=1234&forum_comment=3456"]);
        $response = ($this->sut)("test", $request);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCommentFormForReply(): void
    {
        $this->contents->method('cleanId')->willReturnOnConsecutiveCalls("1234", null);
        $this->authorizer->method('isUser')->willReturn(true);
        $request = new FakeRequest(["query" => "Forum&forum_topic=1234"]);
        $response = ($this->sut)("test", $request);
        Approvals::verifyHtml($response->output());
    }

    private function comment(): Comment
    {
        return new Comment("cmb", 1676130605, "a comment");
    }
}
