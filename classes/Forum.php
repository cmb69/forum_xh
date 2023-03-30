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

use Fa\RequireCommand as FaRequireCommand;
use Forum\Infra\Authorizer;
use Forum\Infra\Contents;
use Forum\Infra\CsrfProtector;
use Forum\Infra\DateFormatter;
use Forum\Infra\Mailer;
use Forum\Infra\Request;
use Forum\Infra\View;
use Forum\Logic\BbCode;
use Forum\Value\Comment;
use Forum\Value\Html;
use Forum\Value\Response;
use Forum\Value\Topic;
use Forum\Value\Url;

class Forum
{
    /** @var array<string,string> */
    private $config;

    /** @var string */
    private $pluginFolder;

    /** @var Contents */
    private $contents;

    /** @var BbCode */
    private $bbcode;

    /** @var CSRFProtector */
    private $csrfProtector;

    /** @var View */
    private $view;

    /** @var FaRequireCommand */
    private $faRequireCommand;

    /** @var Mailer */
    private $mailer;

    /** @var DateFormatter */
    private $dateFormatter;

    /** @var Authorizer */
    private $authorizer;

    /** @param array<string,string> $config */
    public function __construct(
        array $config,
        string $pluginFolder,
        Contents $contents,
        BbCode $bbcode,
        CsrfProtector $csrfProtector,
        View $view,
        FaRequireCommand $faRequireCommand,
        Mailer $mailer,
        DateFormatter $dateFormatter,
        Authorizer $authorizer
    ) {
        $this->config = $config;
        $this->pluginFolder = $pluginFolder;
        $this->contents = $contents;
        $this->bbcode = $bbcode;
        $this->csrfProtector = $csrfProtector;
        $this->view = $view;
        $this->faRequireCommand = $faRequireCommand;
        $this->mailer = $mailer;
        $this->dateFormatter = $dateFormatter;
        $this->authorizer = $authorizer;
    }

    public function __invoke(Request $request, string $forum): Response
    {
        if (!preg_match('/^[a-z0-9\-]+$/u', $forum)) {
            return Response::create($this->view->error("msg_invalid_name", $forum));
        }
        switch ($request->action()) {
            default:
                return $this->show($request, $forum);
            case "do_delete":
                return $this->deleteComment($request, $forum);
            case "edit":
                return $this->showEditor($request, $forum);
            case "do_edit":
                return $this->postComment($request, $forum);
            case "preview":
                return $this->preview($request);
        }
    }

    private function show(Request $request, string $forum): Response
    {
        $tid = $request->topic();
        if ($tid === null) {
            return $this->respondWith($request->url(), $this->renderTopicsView($request, $forum));
        }
        if ($this->contents->hasTopic($forum, $tid)) {
            return $this->respondWith($request->url(), $this->renderTopicView($request, $forum, $tid));
        }
        return $this->respondWith($request->url(), $this->view->error("error_no_topic"));
    }

    private function renderTopicsView(Request $request, string $forum): string
    {
        $topics = $this->contents->getSortedTopics($forum);
        return $this->view->render('topics', [
            'isUser' => $this->authorizer->isUser(),
            'href' => $request->url()->with("forum_action", "edit")->relative(),
            'topics' => $this->topicRecords($request->url()->without("forum_ajax"), $topics),
            "script" => $this->pluginFolder . "forum.min.js",
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
                "url" => $url->with("forum_topic", $tid)->relative(),
            ];
        }, array_keys($topics), array_values($topics));
    }

    private function renderTopicView(Request $request, string $forum, string $tid): string
    {
        $this->faRequireCommand->execute();
        list($title, $topic) = $this->contents->getTopicWithTitle($forum, $tid);
        $url = $request->url()->without("forum_ajax");
        $token = $this->csrfProtector->token();
        $this->csrfProtector->store();
        return $this->view->render('topic', [
            'title' => $title,
            'topic' => $this->commentRecords($url, $topic),
            'tid' => $tid,
            'token' => $token,
            'isUser' => $this->authorizer->isUser(),
            'replyUrl' => $url->with("forum_action", "edit")->with("forum_topic", $tid)->relative(),
            'href' => $url->without("forum_topic")->relative(),
            "script" => $this->pluginFolder . "forum.min.js",
        ]);
    }

    /**
     * @param array<string,Comment> $comments
     * @return list<array{cid:string,user:string,mayDeleteComment:bool,commentDate:string,html:Html,commentEditUrl:string,deleteUrl:string}>
     */
    private function commentRecords(Url $url, array $comments): array
    {
        return array_map(function (string $cid, Comment $comment) use ($url) {
            $url = $url->with("forum_comment", $cid);
            return [
                "cid" => $cid,
                "user" => $comment->user(),

                "mayDeleteComment" => $this->authorizer->mayModify($comment),
                "commentDate" => $this->dateFormatter->format($comment->time()),
                "html" => Html::of($this->bbcode->convert($comment->comment())),
                "commentEditUrl" => $url->with("forum_action", "edit")->relative(),
                "deleteUrl" => $url->with("forum_action", "delete")->relative(),
            ];
        }, array_keys($comments), array_values($comments));
    }

    private function showEditor(Request $request, string $forum): Response
    {
        if ($this->authorizer->isVisitor()) {
            return $this->respondWith($request->url(), $this->view->error("error_unauthorized"));
        }
        $tid = $request->topic();
        $cid = $request->comment();
        $output = $this->renderCommentForm($request, $forum, $tid, $cid);
        return $this->respondWith($request->url(), $output);
    }

    private function renderCommentForm(Request $request, string $forum, ?string $tid, ?string $cid): string
    {
        $this->faRequireCommand->execute();

        $comment = '';
        if ($tid !== null && $cid !== null) {
            $topics = $this->contents->getTopic($forum, $tid);
            if ($this->authorizer->mayModify($topics[$cid])) {
                $comment = $topics[$cid]->comment();
            }
            //$newTopic = true; // FIXME: hack to force overview link to be shown
        }
        $emotions = ['smile', 'wink', 'happy', 'grin', 'tongue', 'surprised', 'unhappy'];
        $emoticons = [];
        foreach ($emotions as $emotion) {
            $emoticons[$emotion] = "{$this->pluginFolder}images/emoticon_$emotion.png";
        }
        $url = $request->url()->without("forum_ajax");
        $output = $this->view->render('form', [
            'newTopic' => $tid === null,
            'tid' => $tid !== null ? $tid : "",
            'cid' => $cid !== null ? $cid : "",
            'action' => $url->with("forum_action", "edit")->relative(),
            'previewUrl' => $url->with("forum_action", "preview")->relative(),
            'backUrl' => $tid === null
                ? $url->without("forum_action")->relative()
                : $url->without("forum_action")->without("forum_comment")->relative(),
            'headingKey' => $tid === null ? 'msg_new_topic' : (isset($cid) ? 'msg_edit_comment' : 'msg_add_comment'),
            'comment' => $comment,
            'token' => $this->csrfProtector->token(),
            'i18n' => ["ENTER_URL" => $this->view->plain("msg_enter_url")],
            'emoticons' => $emoticons,
            "script" => $this->pluginFolder . "forum.min.js",
        ]);
        $this->csrfProtector->store();
        return $output;
    }

    private function preview(Request $request): Response
    {
        if ($this->authorizer->isVisitor()) {
            return $this->respondWith($request->url(), $this->view->error("error_unauthorized"));
        }
        return Response::create($this->bbcode->convert($request->bbCode()))->withExit();
    }

    private function postComment(Request $request, string $forum): Response
    {
        if ($this->authorizer->isVisitor()) {
            return $this->respondWith($request->url(), $this->view->error("error_unauthorized"));
        }
        $this->csrfProtector->check();
        $tid = $request->topic();
        $cid = $request->comment();
        $post = $request->commentPost();
        if ($tid === null && empty($post["title"]) || empty($post["text"])) {
            return $this->respondWith($request->url(), "TODO: post submission failure"); // TODO should render comment form with posted content and error messages
        }
        if ($tid === null) {
            $tid = $this->contents->getId();
        }
        $comment = new Comment($this->authorizer->username(), time(), $post["text"]);
        if (!isset($cid)) {
            $cid = $this->contents->getId();
            $title = $post["title"];
            $this->contents->createComment($forum, $tid, $title, $cid, $comment);
            $subject = $this->view->plain("mail_subject_new");
        } else {
            $oldComment = $this->contents->findComment($forum, $tid, $cid);
            if ($oldComment === null) {
                return $this->respondWith($request->url(), $this->view->error("error_no_comment"));
            }
            if (!$this->authorizer->mayModify($oldComment)) {
                return $this->respondWith($request->url(), $this->view->error("error_unauthorized"));
            }
            $this->contents->updateComment($forum, $tid, $cid, $comment);
            $subject = $this->view->plain("mail_subject_edit");
        }
        if (!$this->authorizer->isAdmin() && $this->config['mail_address']) {
            $url = $request->url()->with("forum_topic", $tid)->absolute();
            $this->mailer->sendMail($subject, $comment, $url);
        }
        $url = $request->url()->without("forum_comment")->with("forum_topic", $tid);
        $url = $url->without("forum_action");
        return Response::redirect($url->absolute());
    }

    private function deleteComment(Request $request, string $forum): Response
    {
        if ($this->authorizer->isVisitor()) {
            return $this->respondWith($request->url(), $this->view->error("error_unauthorized"));
        }
        $this->csrfProtector->check();
        $tid = $request->topic();
        $cid = $request->comment();
        if ($tid !== null && $cid !== null) {
            $comment = $this->contents->findComment($forum, $tid, $cid);
            if ($comment === null) {
                return $this->respondWith($request->url(), $this->view->error("error_no_comment"));
            }
            if (!$this->authorizer->mayModify($comment)) {
                return $this->respondWith($request->url(), $this->view->error("error_unauthorized"));
            }
            $this->contents->deleteComment($forum, $tid, $cid); // TODO report failure
        } else {
            // TODO report that condition
        }
        // TODO redirect to topics overview if topic has been deleted
        $url = $request->url()->without("forum_action")->without("forum_comment");
        return Response::redirect($url->absolute());
    }

    private function respondWith(Url $url, string $output): Response
    {
        if ($url->param("forum_ajax") === null) {
            return Response::create($output);
        }
        return Response::create($output)->withExit();
    }
}
