<?php

/**
 * Copyright 2012-2017 Christoph M. Becker
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

use XH_CSRFProtection;

class MainController
{
    /**
     * @var string
     */
    private $forum;

    /**
     * @var Contents
     */
    private $contents;

    /**
     * @param string $forum
     */
    public function __construct($forum)
    {
        global $pth;

        $this->forum = $forum;
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
    }

    /**
     * @param string $forum
     * @return View
     */
    private function prepareTopicsView($forum)
    {
        global $su;

        $topics = $this->contents->getSortedTopics($forum);
        $i = 1;
        foreach ($topics as $tid => &$topic) {
            $topic['href'] = "?$su&forum_topic=$tid#$forum";
            $topic['details'] = $this->posted($topic);
            $topic['class'] = 'forum_' . ($i & 1 ? 'odd' : 'even');
            $i++;
        }
        $view = new View('topics');
        $view->anchorLabel = $forum;
        $view->isUser = $this->user() !== false;
        $view->href = "?$su&forum_actn=new#$forum";
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
        global $sn, $su, $pth, $adm;

        $bbcode = new BBCode($pth['folder']['plugins'] . 'forum/images/');
        list($title, $topic) = $this->contents->getTopicWithTitle($forum, $tid);
        $editUrl = $sn . '?' . $su . '&forum_actn=edit&forum_topic=' . $tid
            . '&forum_comment=';
        $i = 1;
        foreach ($topic as $cid => &$comment) {
            $mayDelete = $adm || $comment['user'] == $this->user();
            $comment['mayDelete'] = $mayDelete;
            $comment['class'] = 'forum_' . ($i & 1 ? 'odd' : 'even');
            $comment['comment'] = new HtmlString($bbcode->convert($comment['comment']));
            $comment['details'] = new HtmlString($this->posted($comment));
            $comment['editUrl'] = $editUrl . $cid;
            $i++;
        }

        $csrfProtector = $this->getCSRFProtector();
        $view = new View('topic');
        $view->anchor = $forum;
        $view->title = $title;
        $view->topic = $topic;
        $view->tid = $tid;
        $view->su = $su;
        $view->deleteImg = $pth['folder']['plugins'] . 'forum/images/delete.png';
        $view->editImg = $pth['folder']['plugins'] . 'forum/images/edit.png';
        $view->csrfTokenInput = new HtmlString($csrfProtector->tokenInput());
        $view->isUser = $this->user() !== false;
        $view->commentForm = new HtmlString($this->prepareCommentForm($forum, $tid));
        $view->href = "?$su#$forum";
        $csrfProtector->store();
        return $view;
    }

    public function newAction()
    {
        $this->prepareCommentForm($this->forum)->render();
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
        $params = $tid ? "?$su&forum_topic=$tid#{$this->forum}" : "?$su#{$this->forum}";
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
        } else {
            $this->contents->updateComment($forum, $tid, $cid, $comment);
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
    }

    public function deleteAction()
    {
        global $adm, $su;

        $this->getCSRFProtector()->check();
        $tid = $this->contents->cleanId($_POST['forum_topic']);
        $cid = $this->contents->cleanId($_POST['forum_comment']);
        $user = $adm ? true : $this->user();
        $queryString = $this->contents->deleteComment($this->forum, $tid, $cid, $user)
            ? '?' . $su . '&forum_topic=' . $tid . '#' . $this->forum
            : '?' . $su . '#' . $this->forum;
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
        global $su;

        if ($this->user() === false && !XH_ADM) {
            return false;
        }
        $this->hjs();

        $newTopic = !isset($tid);
        $comment = '';
        if (isset($cid)) {
            $topics = $this->contents->getTopic($forum, $tid);
            if ($topics[$cid]['user'] == $this->user() || XH_ADM) {
                $comment = $topics[$cid]['comment'];
            }
            //$newTopic = true; // FIXME: hack to force overview link to be shown
        }
        $csrfProtector = $this->getCSRFProtector();
        $view = new View('form');
        $view->newTopic = $newTopic;
        $view->tid = $tid;
        $view->cid = $cid;
        $view->action = "?$su&forum_actn=post";
        $view->overviewUrl = "?$su#$forum";
        $view->comment = $comment;
        $view->csrfTokenInput = new HtmlString($csrfProtector->tokenInput());
        $view->headingKey = $newTopic
            ? 'msg_new_topic'
            : (isset($cid) ? 'msg_edit_comment' : 'msg_add_comment');
        $view->anchor = $forum;
        $csrfProtector->store();
        return $view;
    }

    private function hjs()
    {
        global $pth, $hjs;

        $dir = $pth['folder']['plugins'] . 'forum/markitup/';
        $hjs .= tag(
            'link rel="stylesheet" type="text/css" href="' . $dir
            . 'skins/simple/style.css"'
        ) . "\n";
        $hjs .= tag(
            'link rel="stylesheet" type="text/css" href="' . $dir
            . 'sets/bbcode/style.css"'
        ) . "\n";
        include_once $pth['folder']['plugins'] . 'jquery/jquery.inc.php';
        include_jQuery();
        include_jQueryPlugin('markitup', $dir . 'jquery.markitup.js');
        $texts = XH_encodeJson($this->jsTexts());
        $hjs .= <<<EOT
<script type="text/javascript">/* <![CDATA[ */
var Forum = {TX: $texts};
jQuery(function() {
    jQuery(".forum_comment textarea").markItUp(Forum.settings);
});
/* ]]> */</script>
<script type="text/javascript" src="{$dir}sets/bbcode/set.js"></script>
EOT;
    }

    /**
     * @return array
     */
    private function jsTexts()
    {
        global $plugin_tx;

        $keys = array(
            'title_missing', 'comment_missing', 'bold', 'italic', 'underline',
            'strikethrough', 'emoticon', 'smile', 'wink', 'happy', 'grin',
            'tongue', 'surprised', 'unhappy', 'picture', 'link', 'size', 'big',
            'normal', 'small', 'bulleted_list', 'numeric_list', 'list_item',
            'quotes', 'code', 'clean', 'preview', 'link_text'
        );
        $texts = array();
        foreach ($keys as $key) {
            $texts[strtoupper($key)] = $plugin_tx['forum']['lbl_' . $key];
        }
        return $texts;
    }

    /**
     * @return string
     */
    private function user()
    {
        if (session_id() == '') {
            session_start();
        }
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
                $this->csrfProtector = new XH_CSRFProtection('forum_token');
            }
            return $this->csrfProtector;
        }
    }

    /**
     * @param array $rec
     * @return string
     */
    private function posted($rec)
    {
        global $plugin_tx;

        $ptx = $plugin_tx['forum'];
        $date = date($ptx['format_date'], $rec['time']);
        $time = date($ptx['format_time'], $rec['time']);
        return str_replace(array('{user}', '{date}', '{time}'), array($rec['user'], $date, $time), $ptx['msg_posted']);
    }
}
