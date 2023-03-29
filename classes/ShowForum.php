<?php

/**
 * Copyright 2012-2023 Christoph M. Becker
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

use XH\CSRFProtection;
use Fa\RequireCommand as FaRequireCommand;
use Forum\Infra\Authorizer;
use Forum\Infra\Contents;
use Forum\Infra\DateFormatter;
use Forum\Infra\Request;
use Forum\Infra\Response;
use Forum\Infra\Url;
use Forum\Infra\View;
use Forum\Logic\BbCode;
use Forum\Value\Comment;
use Forum\Value\Html;
use Forum\Value\Topic;

class ShowForum
{
    /** @var string */
    private $pluginFolder;

    /** @var Contents */
    private $contents;

    /** @var BbCode */
    private $bbcode;

    /** @var CSRFProtection */
    private $csrfProtector;

    /** @var View */
    private $view;

    /** @var FaRequireCommand */
    private $faRequireCommand;

    /** @var DateFormatter */
    private $dateFormatter;

    /** @var Authorizer */
    private $authorizer;

    public function __construct(
        string $pluginFolder,
        Contents $contents,
        BbCode $bbcode,
        CSRFProtection $csrfProtector,
        View $view,
        FaRequireCommand $faRequireCommand,
        DateFormatter $dateFormatter,
        Authorizer $authorizer
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->contents = $contents;
        $this->bbcode = $bbcode;
        $this->csrfProtector = $csrfProtector;
        $this->view = $view;
        $this->faRequireCommand = $faRequireCommand;
        $this->dateFormatter = $dateFormatter;
        $this->authorizer = $authorizer;
    }
    public function __invoke(string $forum, Request $request): Response
    {
        if (empty($request->get("forum_topic"))
            || ($tid = $this->contents->cleanId($request->get("forum_topic"))) === null
            || !$this->contents->hasTopic($forum, $tid)
        ) {
            $response = new Response(
                $this->renderTopicsView($forum, $request),
                null,
                $request->get("forum_ajax") !== null
            );
        } else {
            $response = new Response(
                $this->renderTopicView($forum, $tid, $request),
                null,
                $request->get("forum_ajax") !== null
            );
        }
        $response->addScript("{$this->pluginFolder}forum");
        return $response;
    }

    private function renderTopicsView(string $forum, Request $request): string
    {
        $topics = $this->contents->getSortedTopics($forum);
        return $this->view->render('topics', [
            'isUser' => $this->authorizer->isUser(),
            'href' => $request->url()->replace(["forum_actn" => "edit"])->relative(),
            'topics' => $this->topicRecords($request->url(), $topics),
        ]);
    }

    /**
     * @param array<string,Topic> $topics
     * @return list<array{tid:string,title:string,user:string,comments:int,date:string,url:string}>
     */
    private function topicRecords(Url $url, array $topics): array
    {
        return array_map(function (string $tid, Topic $topic) use ($url) {
            return [
                "tid" => $tid,
                "title" => $topic->title(),
                "user" => $topic->user(),
                "comments" => $topic->comments(),
                "date" => $this->dateFormatter->format($topic->time()),
                "url" => $url->replace(["forum_topic" => $tid])->relative(),
            ];
        }, array_keys($topics), array_values($topics));
    }

    private function renderTopicView(string $forum, string $tid, Request $request): string
    {
        $this->faRequireCommand->execute();
        list($title, $topic) = $this->contents->getTopicWithTitle($forum, $tid);
        $editUrl = $request->url()->replace(["forum_actn" => "edit", "forum_topic" => $tid]);

        $this->csrfProtector->store();
        return $this->view->render('topic', [
            'title' => $title,
            'topic' => $this->commentRecords($editUrl, $topic),
            'tid' => $tid,
            'csrfTokenInput' => Html::of((string) $this->csrfProtector->tokenInput()),
            'isUser' => $this->authorizer->isUser(),
            'replyUrl' => $request->url()->replace(["forum_actn" => "edit", "forum_topic" => $tid])->relative(),
            'deleteUrl' => $request->url()->replace(["forum_actn" => "delete"])->relative(),
            'href' => $request->url()->relative(),
        ]);
    }

    /**
     * @param array<string,Comment> $comments
     * @return list<array{cid:string,user:string,mayDeleteComment:bool,commentDate:string,html:Html,commentEditUrl:string}>
     */
    private function commentRecords(Url $editUrl, array $comments): array
    {
        return array_map(function (string $cid, Comment $comment) use ($editUrl) {
            return [
                "cid" => $cid,
                "user" => $comment->user(),
                "mayDeleteComment" => $this->authorizer->mayModify($comment),
                "commentDate" => $this->dateFormatter->format($comment->time()),
                "html" => Html::of($this->bbcode->convert($comment->comment())),
                "commentEditUrl" => $editUrl->replace(["forum_comment" => $cid])->relative(),
            ];
        }, array_keys($comments), array_values($comments));
    }
}
