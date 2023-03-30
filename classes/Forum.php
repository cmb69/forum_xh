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

    /** @var array<string,string> */
    private $lang;

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

    /**
     * @param array<string,string> $config
     * @param array<string,string> $lang
     */
    public function __construct(
        array $config,
        array $lang,
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
        $this->lang = $lang;
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

    public function __invoke(string $forum, Request $request): Response
    {
        if (!preg_match('/^[a-z0-9\-]+$/u', $forum)) {
            return Response::create($this->view->message("fail", "msg_invalid_name", $forum));
        }
        $action = $request->url()->param("forum_actn");
        $action = is_string($action) ? $action : "";
        switch ($action) {
            default:
                return $this->show($forum, $request);
            case "delete":
                return $this->deleteComment($forum, $request);
            case "edit":
                return $this->showEditor($forum, $request);
            case "post":
                return $this->post($forum, $request);
            case "preview":
                return $this->preview($request);
        }
    }

    private function show(string $forum, Request $request): Response
    {
        $topic = $request->url()->param("forum_topic");
        $topic = is_string($topic) ? $topic : "";
        if (empty($topic)
            || ($tid = $this->contents->cleanId($topic)) === null
            || !$this->contents->hasTopic($forum, $tid)
        ) {
            $response = Response::create($this->renderTopicsView($forum, $request));
        } else {
            $response = Response::create($this->renderTopicView($forum, $tid, $request));
        }
        if ($request->url()->param("forum_ajax") !== null) {
            $response = $response->withExit();
        }
        $response = $response->withScript("{$this->pluginFolder}forum");
        return $response;
    }

    private function renderTopicsView(string $forum, Request $request): string
    {
        $topics = $this->contents->getSortedTopics($forum);
        return $this->view->render('topics', [
            'isUser' => $this->authorizer->isUser(),
            'href' => $request->url()->with("forum_actn", "edit")->relative(),
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
                "url" => $url->with("forum_topic", $tid)->relative(),
            ];
        }, array_keys($topics), array_values($topics));
    }

    private function renderTopicView(string $forum, string $tid, Request $request): string
    {
        $this->faRequireCommand->execute();
        list($title, $topic) = $this->contents->getTopicWithTitle($forum, $tid);
        $editUrl = $request->url()->with("forum_actn", "edit")->with("forum_topic", $tid);

        $this->csrfProtector->store();
        return $this->view->render('topic', [
            'title' => $title,
            'topic' => $this->commentRecords($editUrl, $topic),
            'tid' => $tid,
            'csrfToken' => $this->csrfProtector->token(),
            'isUser' => $this->authorizer->isUser(),
            'replyUrl' => $request->url()->with("forum_actn", "edit")->with("forum_topic", $tid)->relative(),
            'deleteUrl' => $request->url()->with("forum_actn", "delete")->relative(),
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
                "commentEditUrl" => $editUrl->with("forum_comment", $cid)->relative(),
            ];
        }, array_keys($comments), array_values($comments));
    }

    private function showEditor(string $forum, Request $request): Response
    {
        $tid = $request->url()->param("forum_topic");
        $tid = is_string($tid) ? $tid : "";
        $tid = $this->contents->cleanId($tid);
        $cid = $request->url()->param("forum_comment");
        $cid = is_string($cid) ? $cid : "";
        $cid = $this->contents->cleanId($cid);
        $output = $this->renderCommentForm($forum, $tid, $cid, $request);
        $response = Response::create($output)->withScript("{$this->pluginFolder}forum");
        if ($request->url()->param("forum_ajax") !== null) {
            $response = $response->withExit();
        }
        return $response;
    }

    private function renderCommentForm(string $forum, ?string $tid, ?string $cid, Request $request): string
    {
        if ($this->authorizer->isVisitor()) {
            return "";
        }
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
        $output = $this->view->render('form', [
            'newTopic' => $tid === null,
            'tid' => $tid !== null ? $tid : "",
            'cid' => $cid !== null ? $cid : "",
            'action' => $request->url()->with("forum_actn", "post")->relative(),
            'previewUrl' => $request->url()->with("forum_actn", "preview")->relative(),
            'backUrl' => $tid === null
                ? $request->url()->without("forum_topic")->relative()
                : $request->url()->with("forum_topic", $tid)->relative(),
            'headingKey' => $tid === null ? 'msg_new_topic' : (isset($cid) ? 'msg_edit_comment' : 'msg_add_comment'),
            'comment' => $comment,
            'token' => $this->csrfProtector->token(),
            'i18n' => json_encode($this->jsTexts()),
            'emoticons' => $emoticons,
        ]);
        $this->csrfProtector->store();
        return $output;
    }

    /** @return array<string,string> */
    private function jsTexts()
    {
        $keys = ['title_missing', 'comment_missing', 'enter_url'];
        $texts = array();
        foreach ($keys as $key) {
            $texts[strtoupper($key)] = $this->lang['msg_' . $key];
        }
        return $texts;
    }

    private function preview(Request $request): Response
    {
        $bbCode = $request->url()->param("forum_bbcode");
        $bbCode = is_string($bbCode) ? $bbCode : "";
        return Response::create($this->bbcode->convert($bbCode))->withExit();
    }

    private function post(string $forum, Request $request): Response
    {
        $this->csrfProtector->check();
        $post = $request->commentPost();
        $tid = $this->postComment($forum, $post["topic"], $post["comment"], $request);
        $url = $tid !== null ? $request->url()->with("forum_topic", $tid) : $request->url();
        if ($request->url()->param("forum_ajax") !== null) {
            $url = $url->with("forum_ajax", "");
        }
        $url = $url->without("forum_actn");
        return Response::redirect($url->absolute());
    }

    private function postComment(string $forum, ?string $tid, ?string $cid, Request $request): ?string
    {
        $post = $request->commentPost();
        if (!isset($tid) && empty($post["title"])
            || $this->authorizer->isVisitor() || empty($post["text"])
        ) {
            return null;
        }
        $tid = isset($tid)
            ? $this->contents->cleanId($tid)
            : $this->contents->getId();
        if ($tid === null) {
            return null;
        }

        $comment = new Comment($this->authorizer->username(), time(), $post["text"]);
        if (!isset($cid)) {
            $cid = $this->contents->getId();
            $title = $post["title"];
            $this->contents->createComment($forum, $tid, $title, $cid, $comment);
            $subject = $this->view->plain("mail_subject_new");
        } else {
            $this->contents->updateComment($forum, $tid, $cid, $comment);
            $subject = $this->view->plain("mail_subject_edit");
        }

        if (!$this->authorizer->isAdmin() && $this->config['mail_address']) {
            $url = $request->url()->with("forum_topic", $tid)->absolute();
            $this->mailer->sendMail($subject, $comment, $url);
        }

        return $tid;
    }

    private function deleteComment(string $forum, Request $request): Response
    {
        $this->csrfProtector->check();
        [$tid, $cid] = $request->deletePost();
        $tid = $this->contents->cleanId($tid);
        $cid = $this->contents->cleanId($cid);
        $url = $tid !== null && $cid !== null && $this->contents->deleteComment($forum, $tid, $cid, $this->authorizer)
            ? $request->url()->with("forum_topic", $tid)
            : $request->url();
        if ($request->url()->param("forum_ajax") !== null) {
            $url = $url->with("forum_ajax");
        }
        $url = $url->without("forum_actn");
        return Response::redirect($url->absolute());
    }
}
