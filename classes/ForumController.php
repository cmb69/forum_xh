<?php

/**
 * Copyright (c) Christoph M. Becker
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

use Forum\Model\BbCode;
use Forum\Model\Comment;
use Forum\Model\Forum;
use Forum\Model\Repository;
use Forum\Model\Topic;
use Plib\Codec;
use Plib\CsrfProtector;
use Plib\Random;
use Plib\Request;
use Plib\Response;
use Plib\Url;
use Plib\View;
use XH\Mail;

class ForumController
{
    /** @var array<string,string> */
    private $config;

    /** @var string */
    private $pluginFolder;

    /** @var BbCode */
    private $bbcode;

    /** @var CSRFProtector */
    private $csrfProtector;

    /** @var View */
    private $view;

    /** @var Mail */
    private $mail;

    /** @var Repository */
    private $repository;

    /** @var Random */
    private $random;

    /** @param array<string,string> $config */
    public function __construct(
        array $config,
        string $pluginFolder,
        BbCode $bbcode,
        CsrfProtector $csrfProtector,
        View $view,
        Mail $mail,
        Repository $repository,
        Random $random
    ) {
        $this->config = $config;
        $this->pluginFolder = $pluginFolder;
        $this->bbcode = $bbcode;
        $this->csrfProtector = $csrfProtector;
        $this->view = $view;
        $this->mail = $mail;
        $this->repository = $repository;
        $this->random = $random;
    }

    public function __invoke(Request $request, string $forumname): Response
    {
        if (!preg_match('/^[a-z0-9\-]+$/u', $forumname)) {
            return Response::create($this->view->message("fail", "msg_invalid_name", $forumname));
        }
        switch ($this->action($request)) {
            default:
                return $this->show($request, $forumname);
            case "create":
                return $this->createComment($request, $forumname);
            case "do_delete":
                return $this->deleteComment($request, $forumname);
            case "edit":
                return $this->showEditor($request, $forumname);
            case "do_create":
                return $this->doCreateComment($request, $forumname);
            case "do_edit":
                return $this->postComment($request, $forumname);
            case "preview":
                return $this->preview($request);
        }
    }

    private function action(Request $request): string
    {
        $action = $request->get("forum_action");
        if (!is_string($action)) {
            return "";
        }
        if (!strncmp($action, "do_", strlen("do_"))) {
            return "";
        }
        if ($request->post("forum_do") !== null) {
            return "do_$action";
        }
        return $action;
    }

    private function show(Request $request, string $forumname): Response
    {
        $tid = $this->id($request->get("forum_topic"));
        if ($tid === null) {
            return $this->respondWith($request, $this->renderTopicsView($request, $forumname));
        }
        if ($this->repository->hasTopic($forumname, $tid)) {
            return $this->respondWith($request, $this->renderTopicView($request, $forumname, $tid));
        }
        return $this->respondWith($request, $this->view->message("fail", "error_no_topic"));
    }

    private function renderTopicsView(Request $request, string $forumname): string
    {
        $js = $this->pluginFolder . "forum.min.js";
        if (!is_file($js)) {
            $js = $this->pluginFolder . "forum.js";
        }
        $forum = $this->repository->findForum($forumname);
        return $this->view->render('topics', [
            'isUser' => $request->username(),
            'href' => $request->url()->with("forum_action", "create")->relative(),
            'topics' => $this->topicRecords($request->url(), $forum),
            "script" => $request->url()->path($js)->with("v", FORUM_VERSION)->relative(),
        ]);
    }

    /** @return list<array{tid:string,title:string,user:string,comments:int,date:string,url:string}> */
    private function topicRecords(Url $url, Forum $forum): array
    {
        return array_map(function (Topic $topic) use ($url) {
            return [
                "tid" => $topic->id(),
                "title" => $topic->title(),
                "user" => $topic->user(),
                "comments" => $topic->comments(),
                "date" => date($this->view->plain("format_date"), $topic->time()),
                "url" => $url->with("forum_topic", $topic->id())->relative(),
            ];
        }, array_values($forum->topics()));
    }

    private function renderTopicView(Request $request, string $forumname, string $tid): string
    {
        [$topic, $comments] = $this->repository->findTopic($forumname, $tid);
        if ($topic === null) {
            return $this->view->message("fail", "error_no_topic");
        }
        $url = $request->url();
        $token = $this->csrfProtector->token();
        return $this->view->render('topic', [
            'title' => $topic->title(),
            'topic' => $this->commentRecords($request, $comments),
            'tid' => $tid,
            'token' => $token,
            'isUser' => $request->username(),
            'replyUrl' => $url->with("forum_action", "create")->with("forum_topic", $tid)->relative(),
            'href' => $url->without("forum_topic")->relative(),
            "script" => $this->pluginFolder . "forum.min.js",
        ]);
    }

    /**
     * @param list<Comment> $comments
     * @return list<array{cid:string,user:string,mayDeleteComment:bool,commentDate:string,html:string,commentEditUrl:string,deleteUrl:string}>
     */
    private function commentRecords(Request $request, array $comments): array
    {
        $url = $request->url();
        return array_map(function (Comment $comment) use ($request, $url) {
            assert($comment->id() !== null);
            $url = $url->with("forum_comment", $comment->id());
            return [
                "cid" => $comment->id(),
                "user" => $comment->user(),
                "mayDeleteComment" => $this->mayModify($request, $comment),
                "commentDate" => date($this->view->plain("format_date"), $comment->time()),
                "html" => $this->bbcode->convert($comment->message()),
                "commentEditUrl" => $url->with("forum_action", "edit")->relative(),
                "deleteUrl" => $url->with("forum_action", "delete")->relative(),
            ];
        }, array_values($comments));
    }

    private function createComment(Request $request, string $forumname): Response
    {
        $tid = $this->id($request->get("forum_topic"));
        if ($tid === null) {
            $topic = new Topic("", "", 0, "", 0);
        } else {
            [$topic, ] = $this->repository->findTopic($forumname, $tid);
            if ($topic === null) {
                return $this->respondWith($request, $this->view->message("fail", "error_no_topic"));
            }
        }
        $comment = new Comment("", null, "", 0, "");
        if (!$request->username()) {
            return $this->respondWith($request, $this->view->message("fail", "error_unauthorized"));
        }
        $output = $this->renderCommentForm($request, $topic, $comment);
        return $this->respondWith($request, $output);
    }

    private function showEditor(Request $request, string $forumname): Response
    {
        $tid = $this->id($request->get("forum_topic"));
        $cid = $this->id($request->get("forum_comment"));
        if ($tid === null || $cid === null) {
            return $this->respondWith($request, $this->view->message("fail", "error_id_missing"));
        }
        [$topic, ] = $this->repository->findTopic($forumname, $tid);
        if ($topic === null) {
            return $this->respondWith($request, $this->view->message("fail", "error_no_topic"));
        }
        $comment = $this->repository->findComment($forumname, $tid, $cid);
        if ($comment === null) {
            return $this->respondWith($request, $this->view->message("fail", "error_no_comment"));
        }
        if (!$this->mayModify($request, $comment)) {
            return $this->respondWith($request, $this->view->message("fail", "error_unauthorized"));
        }
        $output = $this->renderCommentForm($request, $topic, $comment);
        return $this->respondWith($request, $output);
    }

    /** @param list<array{string}> $errors */
    private function renderCommentForm(Request $request, Topic $topic, Comment $comment, array $errors = []): string
    {
        $emotions = ['smile', 'wink', 'happy', 'grin', 'tongue', 'surprised', 'unhappy'];
        $emoticons = [];
        foreach ($emotions as $emotion) {
            $emoticons[$emotion] = "{$this->pluginFolder}images/emoticon_$emotion.png";
        }
        $url = $request->url();
        $output = $this->view->render('form', [
            "errors" => $errors,
            'title_attribute' => $topic->id() === "" ? "required" : "disabled",
            "title" => $topic->title(),
            'action' => $url->with("forum_action", $comment->id() !== "" ? "edit" : "create")->relative(),
            'previewUrl' => $url->with("forum_action", "preview")->relative(),
            'backUrl' => $topic->id() === ""
                ? $url->without("forum_action")->relative()
                : $url->without("forum_action")->without("forum_comment")->relative(),
            'headingKey' => $topic->id() === ""
                ? 'msg_new_topic'
                : ($comment->id() !== "" ? 'msg_edit_comment' : 'msg_add_comment'),
            'comment' => $comment->message(),
            'token' => $this->csrfProtector->token(),
            'i18n' => ["ENTER_URL" => $this->view->plain("msg_enter_url")],
            'emoticons' => $emoticons,
            "script" => $this->pluginFolder . "forum.min.js",
        ]);
        return $output;
    }

    private function preview(Request $request): Response
    {
        if (!$request->username() && !$request->admin()) {
            return $this->respondWith($request, $this->view->message("fail", "error_unauthorized"));
        }
        return Response::create($this->bbcode->convert($request->get("forum_bbcode") ?? ""))
            ->withContentType("text/html");
    }

    private function doCreateComment(Request $request, string $forumname): Response
    {
        $tid = $this->id($request->get("forum_topic"));
        if ($tid === null) {
            $topic = new Topic(Codec::encodeBase32hex($this->random->bytes(15)), "", 0, "", 0);
        } else {
            [$topic, ] = $this->repository->findTopic($forumname, $tid);
            if ($topic === null) {
                return $this->respondWith($request, $this->view->message("fail", "error_no_topic"));
            }
        }
        $comment = new Comment(
            Codec::encodeBase32hex($this->random->bytes(15)),
            null,
            $request->username() ?? "",
            $request->time(),
            ""
        );
        if (!$request->username()) {
            return $this->respondWith($request, $this->view->message("fail", "error_unauthorized"));
        }
        $this->csrfProtector->check($request->post("forum_token"));
        $title = $request->post("forum_title") ?? "";
        $text = $request->post("forum_text") ?? "";
        $topic = $topic->withTitle($title);
        $comment = $comment->with($title, $text);
        $errors = [];
        if ($topic->title() === "") {
            $errors[] = ["error_title"];
        }
        if ($comment->message() === "") {
            $errors[] = ["error_message"];
        }
        if ($errors) {
            return $this->respondWith($request, $this->renderCommentForm($request, $topic, $comment, $errors));
        }
        if (!$this->repository->save($forumname, $topic->id(), $comment)) {
            return $this->respondWith($request, $this->view->message("fail", "error_store"));
        }
        if (!$request->admin() && $this->config['mail_address']) {
            $url = $request->url()->with("forum_topic", $topic->id())->absolute();
            $this->mail($this->view->plain("mail_subject_new"), $comment, $url);
        }
        $url = $request->url()->without("forum_comment")->with("forum_topic", $topic->id());
        $url = $url->without("forum_action");
        return Response::redirect($url->absolute());
    }

    private function postComment(Request $request, string $forumname): Response
    {
        $tid = $this->id($request->get("forum_topic"));
        $cid = $this->id($request->get("forum_comment"));
        if ($tid === null || $cid === null) {
            return $this->respondWith($request, $this->view->message("fail", "error_id_missing"));
        }
        [$topic, ] = $this->repository->findTopic($forumname, $tid);
        if ($topic === null) {
            return $this->respondWith($request, $this->view->message("fail", "error_no_topic"));
        }
        $comment = $this->repository->findComment($forumname, $tid, $cid);
        if ($comment === null) {
            return $this->respondWith($request, $this->view->message("fail", "error_no_comment"));
        }
        if (!$this->mayModify($request, $comment)) {
            return $this->respondWith($request, $this->view->message("fail", "error_unauthorized"));
        }
        $this->csrfProtector->check($request->post("forum_token"));
        $title = $request->post("forum_title") ?? "";
        $text = $request->post("forum_text") ?? "";
        $comment = $comment->with($title, $text);
        $errors = $comment->message() === "" ? [["error_message"]] : [];
        if ($errors) {
            return $this->respondWith($request, $this->renderCommentForm($request, $topic, $comment, $errors));
        }
        if (!$this->repository->save($forumname, $tid, $comment)) {
            return $this->respondWith($request, $this->view->message("fail", "error_store"));
        }
        if (!$request->admin() && $this->config['mail_address']) {
            $url = $request->url()->with("forum_topic", $tid)->absolute();
            $this->mail($this->view->plain("mail_subject_edit"), $comment, $url);
        }
        $url = $request->url()->without("forum_comment")->with("forum_topic", $tid);
        $url = $url->without("forum_action");
        return Response::redirect($url->absolute());
    }

    private function deleteComment(Request $request, string $forumname): Response
    {
        $tid = $this->id($request->get("forum_topic"));
        $cid = $this->id($request->get("forum_comment"));
        if ($tid === null || $cid === null) {
            return $this->respondWith($request, $this->view->message("fail", "error_id_missing"));
        }
        $comment = $this->repository->findComment($forumname, $tid, $cid);
        if ($comment === null) {
            return $this->respondWith($request, $this->view->message("fail", "error_no_comment"));
        }
        if (!$this->mayModify($request, $comment)) {
            return $this->respondWith($request, $this->view->message("fail", "error_unauthorized"));
        }
        $this->csrfProtector->check($request->post("forum_token"));
        if (!$this->repository->delete($forumname, $tid, $cid)) {
            return $this->respondWith($request, $this->view->message("fail", "error_store"));
        }
        $url = $request->url()->without("forum_action")->without("forum_comment");
        if (!$this->repository->hasTopic($forumname, $tid)) {
            $url = $url->without("forum_topic");
        }
        return Response::redirect($url->absolute());
    }

    private function mayModify(Request $request, Comment $comment): bool
    {
        return $request->admin() || $request->username() === $comment->user();
    }

    private function respondWith(Request $request, string $output): Response
    {
        if ($request->header("X-CMSimple-XH-Request") !== "forum") {
            return Response::create($output);
        }
        return Response::create($output)->withContentType("text/html");
    }

    private function mail(string $subject, Comment $comment, string $url): bool
    {
        $date = date($this->view->plain("format_date"), $comment->time());
        $attribution = $this->view->plain("mail_attribution", $comment->user(), $date);
        $content = preg_replace('/\R/', "\r\n> ", $comment->message());
        assert(is_string($content));
        $this->mail->setTo($this->config["mail_address"]);
        $this->mail->setSubject($subject);
        $this->mail->setMessage("$attribution\r\n\r\n> $content\r\n\r\n<$url>");
        $this->mail->addHeader("From", $this->config["mail_address"]);
        return $this->mail->send();
    }

    private function id(?string $id): ?string
    {
        if ($id === null) {
            return null;
        }
        return preg_match('/^[A-Za-z0-9]+$/u', $id) ? $id : null;
    }
}
