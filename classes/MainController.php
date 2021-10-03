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
use function XH_formatDate;
use function XH_startSession;

class MainController
{
    /**
     * @var string
     */
    private $forum;

    /** @var Url */
    private $url;

    /**
     * @var array<string,string>
     */
    private $config;

    /**
     * @var array<string,string>
     */
    private $lang;

    /**
     * @var string
     */
    private $pluginFolder;

    /**
     * @var Contents
     */
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

    /**
     * @param string $forum
     * @param array<string,string> $config
     * @param array<string,string> $lang
     * @param string $pluginFolder
     */
    public function __construct(
        $forum,
        Url $url,
        array $config,
        array $lang,
        $pluginFolder,
        Contents $contents,
        BBCode $bbcode,
        CSRFProtection $csrfProtector,
        View $view,
        FaRequireCommand $faRequireCommand,
        MailService $mailService
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
    }

    /**
     * @return void
     */
    public function defaultAction()
    {
        if (empty($_GET['forum_topic'])
            || ($tid = $this->contents->cleanId($_GET['forum_topic'])) === false
            || !file_exists($this->contents->dataFolder($this->forum) . $tid . '.dat')
        ) {
            $this->renderTopicsView($this->forum);
        } else {
            $this->renderTopicView($this->forum, $tid);
        }
        $this->addScript();
    }

    /**
     * @param string $forum
     * @return void
     */
    private function renderTopicsView($forum)
    {
        $topics = $this->contents->getSortedTopics($forum);
        $this->view->render('topics', [
            'isUser' => $this->user() !== false,
            'href' => $this->url->withParam("forum_actn", "new")->relative(),
            'topics' => $topics,
            'topicUrl' => function ($tid) {
                return $this->url->withParam("forum_topic", $tid)->relative();
            },
            'topicDate' => function ($topic) {
                return XH_formatDate($topic['time']);
            },
        ]);
    }

    /**
     * @param string $forum
     * @param string $tid
     * @return void
     */
    private function renderTopicView($forum, $tid)
    {
        $this->faRequireCommand->execute();
        list($title, $topic) = $this->contents->getTopicWithTitle($forum, $tid);
        $editUrl = $this->url->withParam("forum_actn", "edit")->withParam("forum_topic", $tid);

        $this->csrfProtector->store();
        $this->view->render('topic', [
            'title' => $title,
            'topic' => $topic,
            'tid' => $tid,
            'csrfTokenInput' => new HtmlString($this->csrfProtector->tokenInput()),
            'isUser' => $this->user() !== false,
            'replyUrl' => $this->url->withParam("forum_actn", "reply")->withParam("forum_topic", $tid)->relative(),
            'href' => $this->url->relative(),
            'mayDeleteComment' => function ($comment) {
                return defined('XH_ADM') && XH_ADM || $comment['user'] == $this->user();
            },
            'commentDate' => function ($comment) {
                return XH_formatDate($comment['time']);
            },
            'html' => function ($comment) {
                return new HtmlString($this->bbcode->convert($comment['comment']));
            },
            'commentEditUrl' => function ($cid) use ($editUrl) {
                return $editUrl->withParam("forum_comment", $cid)->relative();
            },
        ]);
    }

    /**
     * @return void
     */
    public function newAction()
    {
        $this->renderCommentForm($this->forum);
        $this->addScript();
    }

    /**
     * @return never
     */
    public function postAction()
    {
        $this->csrfProtector->check();
        $forumtopic = isset($_POST['forum_topic']) ? $_POST['forum_topic'] : null;
        if (!empty($_POST['forum_comment'])) {
            $tid = $this->postComment($this->forum, $forumtopic, $_POST['forum_comment']);
        } else {
            $tid = $this->postComment($this->forum, $forumtopic);
        }
        $url = $tid ? $this->url->withParam("forum_topic", $tid) : $this->url;
        header("Location: {$url->absolute()}", true, 303);
        exit;
    }

    /**
     * @param string $forum
     * @param string $tid
     * @param string $cid
     * @return string|false
     */
    private function postComment($forum, $tid = null, $cid = null)
    {
        if (!isset($tid) && empty($_POST['forum_title'])
            || $this->user() === false || empty($_POST['forum_text'])
        ) {
            return false;
        }
        $tid = isset($tid)
            ? $this->contents->cleanId($tid)
            : $this->contents->getId();
        if ($tid === false) {
            return false;
        }

        $comment = array(
                'user' => $this->user(),
                'time' => time(),
                'comment' => $_POST['forum_text']);
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

        if (!defined('XH_ADM') || !XH_ADM && $this->config['mail_address']) {
            $url = $this->url->withParam("forum_topic", $tid)->absolute();
            $date = XH_formatDate($comment['time']);
            $attribution = sprintf($this->lang['mail_attribution'], $comment['user'], $date);
            $content = preg_replace('/\r\n|\r|\n/', "\n> ", $comment['comment']);
            assert(is_string($content));
            $message = "$attribution\n\n> $content\n\n<$url>";
            $this->mailService->sendMail($subject, $message, $url);
        }

        return $tid;
    }

    /**
     * @return void
     */
    public function editAction()
    {
        $tid = $this->contents->cleanId($_GET['forum_topic']);
        $cid = $this->contents->cleanId($_GET['forum_comment']);
        if ($tid && $cid) {
            $this->renderCommentForm($this->forum, $tid, $cid);
        } else {
            echo ''; // should display error
        }
        $this->addScript();
    }

    /**
     * @return never
     */
    public function deleteAction()
    {
        $this->csrfProtector->check();
        $tid = $this->contents->cleanId($_POST['forum_topic']);
        $cid = $this->contents->cleanId($_POST['forum_comment']);
        $user = defined('XH_ADM') && XH_ADM ? true : $this->user();
        $url = $tid && $cid && $this->contents->deleteComment($this->forum, $tid, $cid, $user)
            ? $this->url->withParam("forum_topic", $tid)
            : $this->url;
        header("Location: {$url->absolute()}", true, 303);
        exit;
    }

    /**
     * @param string $forum
     * @param string $tid
     * @param string $cid
     * @return void
     */
    private function renderCommentForm($forum, $tid = null, $cid = null)
    {
        if ($this->user() === false && (!defined('XH_ADM') || !XH_ADM)) {
            return;
        }
        $this->faRequireCommand->execute();

        $comment = '';
        if ($tid !== null && $cid !== null) {
            $topics = $this->contents->getTopic($forum, $tid);
            if ($topics[$cid]['user'] == $this->user() || (defined('XH_ADM') && XH_ADM)) {
                $comment = $topics[$cid]['comment'];
            }
            //$newTopic = true; // FIXME: hack to force overview link to be shown
        }
        $emotions = ['smile', 'wink', 'happy', 'grin', 'tongue', 'surprised', 'unhappy'];
        $emoticons = [];
        foreach ($emotions as $emotion) {
            $emoticons[$emotion] = "{$this->pluginFolder}images/emoticon_$emotion.png";
        }
        $this->csrfProtector->store();
        $this->view->render('form', [
            'newTopic' => $tid === null,
            'tid' => $tid,
            'cid' => $cid,
            'action' => $this->url->withParam("forum_actn", "post")->relative(),
            'previewUrl' => $this->url->withParam("forum_actn", "preview")->relative(),
            'backUrl' => $tid === null
                ? $this->url->relative()
                : $this->url->withParam("forum_topic", $tid)->relative(),
            'headingKey' => $tid === null ? 'msg_new_topic' : (isset($cid) ? 'msg_edit_comment' : 'msg_add_comment'),
            'comment' => $comment,
            'csrfTokenInput' => new HtmlString($this->csrfProtector->tokenInput()),
            'i18n' => json_encode($this->jsTexts()),
            'emoticons' => $emoticons,
        ]);
    }

    /**
     * @return void
     */
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

    /**
     * @return array<string,string>
     */
    private function jsTexts()
    {
        $keys = ['title_missing', 'comment_missing', 'enter_url'];
        $texts = array();
        foreach ($keys as $key) {
            $texts[strtoupper($key)] = $this->lang['msg_' . $key];
        }
        return $texts;
    }

    /**
     * @return string|false
     */
    private function user()
    {
        XH_startSession();
        return isset($_SESSION['Name'])
            ? $_SESSION['Name']
            : (isset($_SESSION['username']) ? $_SESSION['username'] : false);
    }

    /**
     * @return void
     */
    public function replyAction()
    {
        if (isset($_GET['forum_topic'])) {
            $tid = $this->contents->cleanId($_GET['forum_topic']);
            $this->renderCommentForm($this->forum, $tid ? $tid : null);
        }
        $this->addScript();
    }

    /**
     * @return never
     */
    public function previewAction()
    {
        echo $this->bbcode->convert($_POST['data']);
        exit;
    }
}
