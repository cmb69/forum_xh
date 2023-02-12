<?php

/**
 * Copyright 2012-2021 Christoph M. Becker
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
use Forum\Infra\Mailer;
use Forum\Infra\Response;
use Forum\Infra\Url;
use Forum\Infra\View;
use Forum\Value\Comment;

class MainController
{
    /** @var Url */
    private $url;

    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $lang;

    /** @var string */
    private $pluginFolder;

    /** @var Contents */
    private $contents;

    /** @var CSRFProtection */
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
        Url $url,
        array $config,
        array $lang,
        string $pluginFolder,
        Contents $contents,
        CSRFProtection $csrfProtector,
        View $view,
        FaRequireCommand $faRequireCommand,
        Mailer $mailer,
        DateFormatter $dateFormatter,
        Authorizer $authorizer
    ) {
        $this->url = $url;
        $this->config = $config;
        $this->lang = $lang;
        $this->pluginFolder = $pluginFolder;
        $this->contents = $contents;
        $this->csrfProtector = $csrfProtector;
        $this->view = $view;
        $this->faRequireCommand = $faRequireCommand;
        $this->mailer = $mailer;
        $this->dateFormatter = $dateFormatter;
        $this->authorizer = $authorizer;
    }

    public function newAction(string $forum): Response
    {
        $output = $this->renderCommentForm($forum);
        $response = new Response($output, null, isset($_GET['forum_ajax']));
        $response->addScript("{$this->pluginFolder}forum");
        return $response;
    }

    public function postAction(string $forum): Response
    {
        $this->csrfProtector->check();
        $forumtopic = $_POST['forum_topic'] ?? null;
        if (!empty($_POST['forum_comment'])) {
            $tid = $this->postComment($forum, $forumtopic, $_POST['forum_comment']);
        } else {
            $tid = $this->postComment($forum, $forumtopic);
        }
        $url = $tid ? $this->url->replace(["forum_topic" => $tid]) : $this->url;
        if (isset($_GET['forum_ajax'])) {
            $url = $url->replace(['forum_ajax' => ""]);
        }
        return new Response("", $url->absolute());
    }

    /** @return string|false */
    private function postComment(string $forum, ?string $tid = null, ?string $cid = null)
    {
        if (!isset($tid) && empty($_POST['forum_title'])
            || $this->authorizer->isVisitor() || empty($_POST['forum_text'])
        ) {
            return false;
        }
        $tid = isset($tid)
            ? $this->contents->cleanId($tid)
            : $this->contents->getId();
        if ($tid === false) {
            return false;
        }

        $comment = new Comment($this->authorizer->username(), time(), $_POST['forum_text']);
        if (!isset($cid)) {
            $cid = $this->contents->getId();
            $title = $_POST['forum_title'] ?? null;
            $this->contents->createComment($forum, $tid, $title, $cid, $comment);
            $subject = $this->lang['mail_subject_new'];
        } else {
            $this->contents->updateComment($forum, $tid, $cid, $comment);
            $subject = $this->lang['mail_subject_edit'];
        }

        if (!$this->authorizer->isAdmin() && $this->config['mail_address']) {
            $url = $this->url->replace(["forum_topic" => $tid])->absolute();
            $date = $this->dateFormatter->format($comment->time());
            $attribution = sprintf($this->lang['mail_attribution'], $comment->user(), $date);
            $content = preg_replace('/\r\n|\r|\n/', "\n> ", $comment->comment());
            assert(is_string($content));
            $message = "$attribution\n\n> $content\n\n<$url>";
            $this->mailer->sendMail($subject, $message, $url);
        }

        return $tid;
    }

    public function editAction(string $forum): Response
    {
        $tid = $this->contents->cleanId($_GET['forum_topic']);
        $cid = $this->contents->cleanId($_GET['forum_comment']);
        if ($tid && $cid) {
            $output = $this->renderCommentForm($forum, $tid, $cid);
        } else {
            $output = ''; // should display error
        }
        $response =  new Response($output, null, isset($_GET['forum_ajax']));
        $response->addScript("{$this->pluginFolder}forum");
        return $response;
    }

    public function deleteAction(string $forum): Response
    {
        $this->csrfProtector->check();
        $tid = $this->contents->cleanId($_POST['forum_topic']);
        $cid = $this->contents->cleanId($_POST['forum_comment']);
        $url = $tid && $cid && $this->contents->deleteComment($forum, $tid, $cid, $this->authorizer)
            ? $this->url->replace(["forum_topic" => $tid])
            : $this->url;
        if (isset($_GET['forum_ajax'])) {
            $url = $url->replace(['forum_ajax' => ""]);
        }
        return new Response("", $url->absolute());
    }

    private function renderCommentForm(string $forum, ?string $tid = null, ?string $cid = null): string
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
            'tid' => $tid,
            'cid' => $cid !== null ? $cid : "",
            'action' => $this->url->replace(["forum_actn" => "post"])->relative(),
            'previewUrl' => $this->url->replace(["forum_actn" => "preview"])->relative(),
            'backUrl' => $tid === null
                ? $this->url->relative()
                : $this->url->replace(["forum_topic" => $tid])->relative(),
            'headingKey' => $tid === null ? 'msg_new_topic' : (isset($cid) ? 'msg_edit_comment' : 'msg_add_comment'),
            'comment' => $comment,
            'csrfTokenInput' => $this->csrfProtector->tokenInput(),
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

    public function replyAction(string $forum): Response
    {
        $output = "";
        if (isset($_GET['forum_topic'])) {
            $tid = $this->contents->cleanId($_GET['forum_topic']);
            $output = $this->renderCommentForm($forum, $tid ? $tid : null);
        }
        $response = new Response($output, null, isset($_GET['forum_ajax']));
        $response->addScript("{$this->pluginFolder}forum");
        return $response;
    }
}
