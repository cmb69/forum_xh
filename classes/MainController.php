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
use Forum\Infra\DateFormatter;
use Forum\Infra\Session;
use Forum\Infra\View;

class MainController
{
    /** @var string */
    private $forum;

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

    /** @var BBCode */
    private $bbcode;

    /** @var CSRFProtection */
    private $csrfProtector;

    /** @var View */
    private $view;

    /** @var FaRequireCommand */
    private $faRequireCommand;

    /** @var MailService */
    private $mailService;

    /** @var Session */
    private $session;

    /** @var DateFormatter */
    private $dateFormatter;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $lang
     */
    public function __construct(
        string $forum,
        Url $url,
        array $config,
        array $lang,
        string $pluginFolder,
        Contents $contents,
        BBCode $bbcode,
        CSRFProtection $csrfProtector,
        View $view,
        FaRequireCommand $faRequireCommand,
        MailService $mailService,
        Session $session,
        DateFormatter $dateFormatter
    ) {
        $this->forum = $forum;
        $this->url = $url;
        $this->config = $config;
        $this->lang = $lang;
        $this->pluginFolder = $pluginFolder;
        $this->contents = $contents;
        $this->bbcode = $bbcode;
        $this->csrfProtector = $csrfProtector;
        $this->view = $view;
        $this->faRequireCommand = $faRequireCommand;
        $this->mailService = $mailService;
        $this->session = $session;
        $this->dateFormatter = $dateFormatter;
    }

    public function defaultAction(): Response
    {
        if (empty($_GET['forum_topic'])
            || ($tid = $this->contents->cleanId($_GET['forum_topic'])) === false
            || !$this->contents->hasTopic($this->forum, $tid)
        ) {
            ob_start();
            $this->renderTopicsView($this->forum);
            $response = new Response(ob_get_clean());
        } else {
            ob_start();
            $this->renderTopicView($this->forum, $tid);
            $response = new Response(ob_get_clean());
        }
        $this->addScript();
        return $response;
    }

    /** @return void */
    private function renderTopicsView(string $forum)
    {
        $topics = $this->contents->getSortedTopics($forum);
        echo $this->view->render('topics', [
            'isUser' => $this->user() !== false,
            'href' => $this->url->replace(["forum_actn" => "new"])->relative(),
            'topics' => $topics,
            'topicUrl' => function ($tid) {
                return $this->url->replace(["forum_topic" => $tid])->relative();
            },
            'topicDate' => function (Topic $topic) {
                return $this->dateFormatter->format($topic->time());
            },
        ]);
    }

    /** @return void */
    private function renderTopicView(string $forum, string $tid)
    {
        $this->faRequireCommand->execute();
        list($title, $topic) = $this->contents->getTopicWithTitle($forum, $tid);
        $editUrl = $this->url->replace(["forum_actn" => "edit", "forum_topic" => $tid]);

        $this->csrfProtector->store();
        echo $this->view->render('topic', [
            'title' => $title,
            'topic' => $topic,
            'tid' => $tid,
            'csrfTokenInput' => $this->csrfProtector->tokenInput(),
            'isUser' => $this->user() !== false,
            'replyUrl' => $this->url->replace(["forum_actn" => "reply", "forum_topic" => $tid])->relative(),
            'href' => $this->url->relative(),
            'mayDeleteComment' => function (Comment $comment) {
                return defined('XH_ADM') && XH_ADM || $comment->user() == $this->user();
            },
            'commentDate' => function (Comment $comment) {
                return $this->dateFormatter->format($comment->time());
            },
            'html' => function (Comment $comment) {
                return $this->bbcode->convert($comment->comment());
            },
            'commentEditUrl' => function ($cid) use ($editUrl) {
                return $editUrl->replace(["forum_comment" => $cid])->relative();
            },
        ]);
    }

    public function newAction(): Response
    {
        ob_start();
        $this->renderCommentForm($this->forum);
        $output = ob_get_clean();
        $this->addScript();
        return new Response($output);
    }

    public function postAction(): Response
    {
        $this->csrfProtector->check();
        $forumtopic = isset($_POST['forum_topic']) ? $_POST['forum_topic'] : null;
        if (!empty($_POST['forum_comment'])) {
            $tid = $this->postComment($this->forum, $forumtopic, $_POST['forum_comment']);
        } else {
            $tid = $this->postComment($this->forum, $forumtopic);
        }
        $url = $tid ? $this->url->replace(["forum_topic" => $tid]) : $this->url;
        return new Response("", $url->absolute());
    }

    /** @return string|false */
    private function postComment(string $forum, ?string $tid = null, ?string $cid = null)
    {
        if (!isset($tid) && empty($_POST['forum_title'])
            || ($this->user() === false && !(defined('XH_ADM') && XH_ADM)) || empty($_POST['forum_text'])
        ) {
            return false;
        }
        $tid = isset($tid)
            ? $this->contents->cleanId($tid)
            : $this->contents->getId();
        if ($tid === false) {
            return false;
        }

        $comment = new Comment($this->user(), time(), $_POST['forum_text']);
        if (!isset($cid)) {
            $cid = $this->contents->getId();
            $title = isset($_POST['forum_title'])
                ? $_POST['forum_title'] : null;
            $this->contents->createComment($forum, $tid, $title, $cid, $comment);
            $subject = $this->lang['mail_subject_new'];
        } else {
            $this->contents->updateComment($forum, $tid, $cid, $comment);
            $subject = $this->lang['mail_subject_edit'];
        }

        if (!(defined('XH_ADM') && XH_ADM) && $this->config['mail_address']) {
            $url = $this->url->replace(["forum_topic" => $tid])->absolute();
            $date = $this->dateFormatter->format($comment->time());
            $attribution = sprintf($this->lang['mail_attribution'], $comment->user(), $date);
            $content = preg_replace('/\r\n|\r|\n/', "\n> ", $comment->comment());
            assert(is_string($content));
            $message = "$attribution\n\n> $content\n\n<$url>";
            $this->mailService->sendMail($subject, $message, $url);
        }

        return $tid;
    }

    public function editAction(): Response
    {
        $tid = $this->contents->cleanId($_GET['forum_topic']);
        $cid = $this->contents->cleanId($_GET['forum_comment']);
        if ($tid && $cid) {
            ob_start();
            $this->renderCommentForm($this->forum, $tid, $cid);
            $output = ob_get_clean();
        } else {
            $output = ''; // should display error
        }
        $this->addScript();
        return new Response($output);
    }

    public function deleteAction(): Response
    {
        $this->csrfProtector->check();
        $tid = $this->contents->cleanId($_POST['forum_topic']);
        $cid = $this->contents->cleanId($_POST['forum_comment']);
        $user = defined('XH_ADM') && XH_ADM ? true : $this->user();
        $url = $tid && $cid && $this->contents->deleteComment($this->forum, $tid, $cid, $user)
            ? $this->url->replace(["forum_topic" => $tid])
            : $this->url;
        return new Response("", $url->absolute());
    }

    /** @return void */
    private function renderCommentForm(string $forum, ?string $tid = null, ?string $cid = null)
    {
        if ($this->user() === false && (!defined('XH_ADM') || !XH_ADM)) {
            return;
        }
        $this->faRequireCommand->execute();

        $comment = '';
        if ($tid !== null && $cid !== null) {
            $topics = $this->contents->getTopic($forum, $tid);
            if ($topics[$cid]->user() == $this->user() || (defined('XH_ADM') && XH_ADM)) {
                $comment = $topics[$cid]->comment();
            }
            //$newTopic = true; // FIXME: hack to force overview link to be shown
        }
        $emotions = ['smile', 'wink', 'happy', 'grin', 'tongue', 'surprised', 'unhappy'];
        $emoticons = [];
        foreach ($emotions as $emotion) {
            $emoticons[$emotion] = "{$this->pluginFolder}images/emoticon_$emotion.png";
        }
        echo $this->view->render('form', [
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
    }

    /** @return void */
    private function addScript()
    {
        global $bjs;

        if (is_file("{$this->pluginFolder}forum.min.js")) {
            $filename = "{$this->pluginFolder}forum.min.js";
        } else {
            $filename = "{$this->pluginFolder}forum.js";
        }
        $bjs .= sprintf('<script type="text/javascript" src="%s"></script>', $this->view->esc($filename));
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

    /** @return string|false */
    private function user()
    {
        $name = $this->session->get('Name');
        if ($name !== null) {
            return $name;
        }
        $name = $this->session->get('username');
        if ($name !== null) {
            return $name;
        }
        return false;
    }

    public function replyAction(): Response
    {
        $output = "";
        if (isset($_GET['forum_topic'])) {
            $tid = $this->contents->cleanId($_GET['forum_topic']);
            ob_start();
            $this->renderCommentForm($this->forum, $tid ? $tid : null);
            $output = ob_get_clean();
        }
        $this->addScript();
        return new Response($output);
    }

    public function previewAction(): Response
    {
        return new Response($this->bbcode->convert($_POST['data']), null, true);
    }
}
