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

class MainController
{
    /**
     * @var string
     */
    private $forum;

    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $lang;

    /**
     * @var string
     */
    private $pluginsFolder;

    /**
     * @var string
     */
    private $pluginFolder;

    /**
     * @var Contents
     */
    private $contents;

    /** @var CSRFProtection|null */
    private $csrfProtector = null;

    /**
     * @param string $forum
     */
    public function __construct($forum)
    {
        global $pth, $plugin_cf, $plugin_tx;

        $this->forum = $forum;
        $this->config = $plugin_cf['forum'];
        $this->lang = $plugin_tx['forum'];
        $this->pluginsFolder = $pth['folder']['plugins'];
        $this->pluginFolder = "{$this->pluginsFolder}forum/";
        $this->contents = new Contents("{$pth['folder']['content']}{$pth['folder']['base']}forum/");
    }

    public function defaultAction()
    {
        if (empty($_GET['forum_topic'])
            || ($tid = $this->contents->cleanId($_GET['forum_topic'])) === false
            || !file_exists($this->contents->dataFolder($this->forum) . $tid . '.dat')
        ) {
            $this->prepareTopicsView($this->forum)->render();
        } else {
            $this->prepareTopicView($this->forum, $tid)->render();
        }
        if (is_file("{$this->pluginFolder}forum.min.js")) {
            $this->addScript("{$this->pluginFolder}forum.min.js");
        } else {
            $this->addScript("{$this->pluginFolder}forum.js");
        }
    }

    /**
     * @param string $forum
     * @return View
     */
    private function prepareTopicsView($forum)
    {
        global $su;

        $topics = $this->contents->getSortedTopics($forum);
        foreach ($topics as $tid => &$topic) {
            $topic['href'] = "?$su&forum_topic=$tid";
            $topic['date'] = XH_formatDate($topic['time']);
        }
        $view = new View('topics');
        $view->isUser = $this->user() !== false;
        $view->href = "?$su&forum_actn=new";
        $view->topics = $topics;
        return $view;
    }

    /**
     * @param string $forum
     * @param string $tid
     * @return View
     */
    private function prepareTopicView($forum, $tid)
    {
        global $sn, $su;

        (new FaRequireCommand)->execute();
        $bbcode = new BBCode("{$this->pluginFolder}images/");
        list($title, $topic) = $this->contents->getTopicWithTitle($forum, $tid);
        $editUrl = $sn . '?' . $su . '&forum_actn=edit&forum_topic=' . $tid
            . '&forum_comment=';
        foreach ($topic as $cid => &$comment) {
            $mayDelete = defined('XH_ADM') && XH_ADM || $comment['user'] == $this->user();
            $comment['mayDelete'] = $mayDelete;
            $comment['date'] = XH_formatDate($comment['time']);
            $comment['comment'] = new HtmlString($bbcode->convert($comment['comment']));
            $comment['editUrl'] = $editUrl . $cid;
        }

        $csrfProtector = $this->getCSRFProtector();
        $view = new View('topic');
        $view->title = $title;
        $view->topic = $topic;
        $view->tid = $tid;
        $view->su = $su;
        $view->deleteImg = "{$this->pluginFolder}images/delete.png";
        $view->editImg = "{$this->pluginFolder}images/edit.png";
        $view->csrfTokenInput = new HtmlString($csrfProtector->tokenInput());
        $view->isUser = $this->user() !== false;
        $view->replyUrl = "$sn?$su&forum_actn=reply&forum_topic=$tid";
        $view->href = "?$su";
        $csrfProtector->store();
        return $view;
    }

    public function newAction()
    {
        $this->prepareCommentForm($this->forum)->render();
        if (is_file("{$this->pluginFolder}forum.min.js")) {
            $this->addScript("{$this->pluginFolder}forum.min.js");
        } else {
            $this->addScript("{$this->pluginFolder}forum.js");
        }
    }

    public function postAction()
    {
        global $su;

        $this->getCSRFProtector()->check();
        if (!empty($_POST['forum_comment'])) {
            $tid = $this->postComment($this->forum, $_POST['forum_topic'], $_POST['forum_comment']);
        } else {
            $tid = $this->postComment($this->forum, $_POST['forum_topic']);
        }
        $params = $tid ? "?$su&forum_topic=$tid" : "?$su";
        header('Location: ' . CMSIMPLE_URL . $params, true, 303);
        exit;
    }

    /**
     * @param string $forum
     * @param string $tid
     * @param string $cid
     * @return string
     */
    private function postComment($forum, $tid = null, $cid = null)
    {
        global $su;

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
            $url = CMSIMPLE_URL . "?$su&forum_topic=$tid";
            $date = XH_formatDate($comment['time']);
            $attribution = sprintf($this->lang['mail_attribution'], $comment['user'], $date);
            $content = preg_replace('/\r\n|\r|\n/', "\n> ", $comment['comment']);
            $message = "$attribution\n\n> $content\n\n<$url>";
            (new MailService)->sendMail($subject, $message, $url);
        }

        return $tid;
    }

    public function editAction()
    {
        $tid = $this->contents->cleanId($_GET['forum_topic']);
        $cid = $this->contents->cleanId($_GET['forum_comment']);
        if ($tid && $cid) {
            $this->prepareCommentForm($this->forum, $tid, $cid)->render();
        } else {
            echo ''; // should display error
        }
        if (is_file("{$this->pluginFolder}forum.min.js")) {
            $this->addScript("{$this->pluginFolder}forum.min.js");
        } else {
            $this->addScript("{$this->pluginFolder}forum.js");
        }
    }

    public function deleteAction()
    {
        global $su;

        $this->getCSRFProtector()->check();
        $tid = $this->contents->cleanId($_POST['forum_topic']);
        $cid = $this->contents->cleanId($_POST['forum_comment']);
        $user = defined('XH_ADM') && XH_ADM ? true : $this->user();
        $queryString = $this->contents->deleteComment($this->forum, $tid, $cid, $user)
            ? '?' . $su . '&forum_topic=' . $tid
            : '?' . $su ;
        header('Location: ' . CMSIMPLE_URL . $queryString, true, 303);
        exit;
    }

    /**
     * @param string $forum
     * @param string $tid
     * @param string $cid
     * @return View
     */
    private function prepareCommentForm($forum, $tid = null, $cid = null)
    {
        global $sn, $su;

        if ($this->user() === false && (!defined('XH_ADM') || !XH_ADM)) {
            return false;
        }
        (new FaRequireCommand)->execute();

        $newTopic = !isset($tid);
        $comment = '';
        if (isset($cid)) {
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
        $csrfProtector = $this->getCSRFProtector();
        $view = new View('form');
        $view->newTopic = $newTopic;
        $view->tid = $tid;
        $view->cid = $cid;
        $view->action = "?$su&forum_actn=post";
        $view->previewUrl = "$sn?$su&forum_actn=preview";
        if ($newTopic) {
            $view->backUrl = "?$su";
            $view->headingKey = 'msg_new_topic';
        } else {
            $view->backUrl = "$sn?$su&forum_topic=$tid";
            $view->headingKey = isset($cid) ? 'msg_edit_comment' : 'msg_add_comment';
        }
        $view->comment = $comment;
        $view->csrfTokenInput = new HtmlString($csrfProtector->tokenInput());
        $view->i18n = json_encode($this->jsTexts());
        $view->emoticons = $emoticons;
        $csrfProtector->store();
        return $view;
    }

    private function addScript($filename)
    {
        global $bjs;

        $bjs .= sprintf('<script type="text/javascript" src="%s"></script>', XH_hsc($filename));
    }

    /**
     * @return array
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
     * @return string
     */
    private function user()
    {
        XH_startSession();
        return isset($_SESSION['Name'])
            ? $_SESSION['Name']
            : (isset($_SESSION['username']) ? $_SESSION['username'] : false);
    }

    /**
     * @return XH_CSRFProtection
     */
    private function getCSRFProtector()
    {
        global $_XH_csrfProtection;

        if (isset($_XH_csrfProtection)) {
            return $_XH_csrfProtection;
        } else {
            if (!isset($this->csrfProtector)) {
                $this->csrfProtector = new CSRFProtection('forum_token');
            }
            return $this->csrfProtector;
        }
    }

    public function replyAction()
    {
        if (isset($_GET['forum_topic'])) {
            $tid = $this->contents->cleanId($_GET['forum_topic']);
            $this->prepareCommentForm($this->forum, $tid)->render();
        }
        if (is_file("{$this->pluginFolder}forum.min.js")) {
            $this->addScript("{$this->pluginFolder}forum.min.js");
        } else {
            $this->addScript("{$this->pluginFolder}forum.js");
        }
    }

    public function previewAction()
    {
        $bbcode = new BBCode("{$this->pluginFolder}images/");
        echo $bbcode->convert($_POST['data']);
        exit;
    }
}
