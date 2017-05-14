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

class Controller
{
    /**
     * @var object
     */
    protected $contents;

    /**
     * @var object
     */
    protected $bbcode;

    /**
     * @var XH_CSRFProtection
     */
    protected $csrfProtector;

    public function __construct()
    {
        global $pth, $plugin_cf;

        $folder = $plugin_cf['forum']['folder_data'] != ''
            ? $pth['folder']['base'] . $plugin_cf['forum']['folder_data']
            : $pth['folder']['plugins'] . 'forum/data/';
        $this->contents = new Contents($folder);
    }

    public function dispatch()
    {
        if (XH_ADM) {
            if (function_exists('XH_registerStandardPluginMenuItems')) {
                XH_registerStandardPluginMenuItems(false);
            }
            if ($this->isAdministrationRequested()) {
                $this->handleAdministration();
            }
        }
    }

    /**
     * @return bool
     */
    protected function isAdministrationRequested()
    {
        global $forum;

        return function_exists('XH_wantsPluginAdministration')
            && XH_wantsPluginAdministration('forum')
            || isset($forum) && $forum == 'true';
    }

    protected function handleAdministration()
    {
        global $admin, $action, $o;

        $o .= print_plugin_admin('off');
        switch ($admin) {
            case '':
                $o .= $this->infoView();
                break;
            default:
                $o .= plugin_admin_common($action, $admin, 'forum');
        }
    }

    /**
     * @return object
     */
    protected function getBbcode()
    {
        global $pth;

        if (!isset($this->bbcode)) {
            $emoticonFolder = $pth['folder']['plugins'] . 'forum/images/';
            $this->bbcode = new BBCode($emoticonFolder);
        }
        return $this->bbcode;
    }

    /**
     * @param int $count
     * @return string
     */
    protected function numerus($count)
    {
        if ($count == 1) {
            return '_singular';
        } elseif ($count >= 2 && $count < 5) {
            return '_plural_2-4';
        } else {
            return '_plural_5';
        }
    }

    /**
     * @return string
     */
    protected function user()
    {
        if (session_id() == '') {
            session_start();
        }
        return isset($_SESSION['Name'])
            ? $_SESSION['Name']
            : (isset($_SESSION['username']) ? $_SESSION['username'] : false);
    }

    /**
     * @param string $forum
     * @param string $tid
     * @param string $cid
     * @return string
     */
    protected function postComment($forum, $tid = null, $cid = null)
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
                'comment' => stsl($_POST['forum_text']));
        if (!isset($cid)) {
            $cid = $this->contents->getId();
            $title = isset($_POST['forum_title'])
                ? stsl($_POST['forum_title']) : null;
            $this->contents->createComment($forum, $tid, $title, $cid, $comment);
        } else {
            $this->contents->updateComment($forum, $tid, $cid, $comment);
        }

        return $tid;
    }

    /**
     * @param string $forum
     * @param string $tid
     * @param string $cid
     *
     */
    protected function deleteComment($forum, $tid, $cid)
    {
        global $adm, $su;

        $tid = $this->contents->cleanId($_POST['forum_topic']);
        $cid = $this->contents->cleanId($_POST['forum_comment']);
        $user = $adm ? true : $this->user();
        $queryString = $this->contents->deleteComment($forum, $tid, $cid, $user)
            ? '?' . $su . '&forum_topic=' . $tid . '#' . $forum: '?' . $su .
              '#' . $forum;
        header('Location: ' . CMSIMPLE_URL . $queryString, true, 303);
        exit;
    }

    protected function hjs()
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
    protected function jsTexts()
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
     * @param string $forum
     * @param string $tid
     * @param string $cid
     * @return string
     */
    protected function commentForm($forum, $tid = null, $cid = null)
    {
        global $su, $plugin_tx;

        if ($this->user() === false && !XH_ADM) {
            return false;
        }
        $ptx = $plugin_tx['forum'];
        $this->hjs();

        $newTopic = !isset($tid);
        $labels = array(
            'heading' => $newTopic
                ? $ptx['msg_new_topic']
                : (isset($cid) ? $ptx['msg_edit_comment'] : $ptx['msg_add_comment']),
            'anchor' => $forum,
            'title' => $ptx['msg_title'],
            'submit' => $ptx['lbl_submit'],
            'back' => $ptx['msg_back']
        );
        $comment = '';
        if (isset($cid)) {
            $topics = $this->contents->getTopic($forum, $tid);
            if ($topics[$cid]['user'] == $this->user() || XH_ADM) {
                $comment = $topics[$cid]['comment'];
            }
            //$newTopic = true; // FIXME: hack to force overview link to be shown
        }
        $action = '?' . $su . '&amp;forum_actn=post';
        $overviewUrl = '?' . $su . '#' . $forum;

        $bag = compact('newTopic', 'labels', 'tid', 'cid', 'action', 'overviewUrl', 'comment', '_XH_csrfProtection');
        return $this->render('form', $bag);
    }

    /**
     * @param array $rec
     * @return string
     */
    protected function posted($rec)
    {
        global $plugin_tx;

        $ptx = $plugin_tx['forum'];
        $date = date($ptx['format_date'], $rec['time']);
        $time = date($ptx['format_time'], $rec['time']);
        return str_replace(array('{user}', '{date}', '{time}'), array($rec['user'], $date, $time), $ptx['msg_posted']);
    }

    /**
     * @param string $forum
     * @return string
     */
    protected function viewTopics($forum)
    {
        global $su, $plugin_tx;

        $ptx = $plugin_tx['forum'];
        $topics = $this->contents->getSortedTopics($forum);
        $label = array(
            'heading' => $ptx['msg_topics'],
            'anchor' => $forum,
            'start_topic' => $ptx['msg_start_topic']
        );
        $i = 1;
        foreach ($topics as $tid => &$topic) {
            $topic['href'] = "?$su&amp;forum_topic=$tid#$forum";
            $comments = sprintf(
                $ptx['msg_comments' . $this->numerus($topic['comments'])],
                $topic['comments']
            );
            $topic['details'] = str_replace(
                array('{comments}', '{posted}'),
                array($comments, $this->posted($topic)),
                $ptx['msg_topic_details']
            );
            $topic['class'] = 'forum_' . ($i & 1 ? 'odd' : 'even');
            $i++;
        }
        $is_user = $this->user() !== false;
        $href = "?$su&amp;forum_actn=new#$forum";
        $bag = compact('label', 'topics', 'href', 'is_user');
        return $this->render('topics', $bag);
    }

    /**
     * @param string $forum
     * @param string $tid
     * @return string
     */
    protected function viewTopic($forum, $tid)
    {
        global $sn, $su, $pth, $adm, $plugin_tx;

        $ptx = $plugin_tx['forum'];
        list($title, $topic) = $this->contents->getTopicWithTitle($forum, $tid);
        $href = "?$su#$forum";
        $editUrl = $sn . '?' . $su . '&forum_actn=edit&forum_topic=' . $tid
            . '&forum_comment=';
        $i = 1;
        $label = array(
            'title' => XH_hsc($title),
            'anchor' => $forum,
            'edit' => $ptx['lbl_edit'],
            'delete' => $ptx['lbl_delete'],
            'confirmDelete' => $ptx['msg_confirm_delete'],
            'back' => $ptx['msg_back']
        );
        $deleteImg = $pth['folder']['plugins'] . 'forum/images/delete.png';
        $editImg = $pth['folder']['plugins'] . 'forum/images/edit.png';
        foreach ($topic as $cid => &$comment) {
            $mayDelete = $adm || $comment['user'] == $this->user();
            $comment['mayDelete'] = $mayDelete;
            $comment['class'] = 'forum_' . ($i & 1 ? 'odd' : 'even');
            $comment['comment'] = $this->getBbcode()->convert($comment['comment']);
            $comment['details'] = $this->posted($comment);
            $comment['editUrl'] = $editUrl . $cid;
            $i++;
        }
        $isUser = $this->user() !== false;
        $commentForm = $this->commentForm($forum, $tid);

        $bag = compact(
            'label',
            'tid',
            'topic',
            'su',
            'deleteImg',
            'editImg',
            'href',
            'isUser',
            'commentForm',
            '_XH_csrfProtection'
        );
        return $this->render('topic', $bag);
    }

    /**
     * @param string $forum
     * @return mixed
     */
    public function main($forum)
    {
        global $su, $e, $plugin_tx;

        $ptx = $plugin_tx['forum'];
        if (!preg_match('/^[a-z0-9\-]+$/u', $forum)) {
            $e .= '<li><b>' . $ptx['msg_invalid_name'] . '</b>' . tag('br')
                . $forum . '</li>' . "\n";
            return false;
        }
        $action = isset($_REQUEST['forum_actn'])
            ? $_REQUEST['forum_actn'] : 'view';
        switch ($action) {
            case 'view':
                if (empty($_GET['forum_topic'])
                    || ($tid = $this->contents->cleanId($_GET['forum_topic'])) === false
                    || !file_exists($this->contents->dataFolder($forum) . $tid . '.dat')
                ) {
                    return $this->viewTopics($forum);
                } else {
                    return $this->viewTopic($forum, $tid);
                }
                break;
            case 'new':
                return $this->commentForm($forum);
            case 'post':
                $this->getCSRFProtector()->check();
                if (!empty($_POST['forum_comment'])) {
                    $tid = $this->postComment($forum, $_POST['forum_topic'], $_POST['forum_comment']);
                } else {
                    $tid = $this->postComment($forum, $_POST['forum_topic']);
                }
                $params = $tid ? "?$su&forum_topic=$tid#$forum" : "?$su#$forum";
                header('Location: ' . CMSIMPLE_URL . $params, true, 303);
                exit;
            case 'edit':
                $tid = $this->contents->cleanId($_GET['forum_topic']);
                $cid = $this->contents->cleanId($_GET['forum_comment']);
                if ($tid && $cid) {
                    return $this->commentForm($forum, $tid, $cid);
                } else {
                    return ''; // should display error
                }
                break;
            case 'delete':
                $this->getCSRFProtector()->check();
                $this->deleteComment($forum, $tid, $cid);
                break;
        }
    }

    /**
     * @param string $_template
     * @param array  $_bag
     * @return string
     */
    protected function render($_template, $_bag)
    {
        global $pth, $cf;

        $_xhtml = $cf['xhtml']['endtags'] == 'true';
        $_template = $pth['folder']['plugins'] . 'forum/views/' . $_template
            . '.htm';
        unset($cf, $pth);
        extract($_bag);
        ob_start();
        include $_template;
        $view = ob_get_clean();
        if (!$_xhtml) {
            $view = str_replace(' />', '>', $view);
        }
        return $view;
    }

    /**
     * @return string
     */
    public function commentPreview()
    {
        global $pth;

        $comment = $this->getBbcode()->convert(stsl($_POST['data']));
        $templateStylesheet = $pth['file']['stylesheet'];
        $forumStylesheet = $pth['folder']['plugins'] . 'forum/css/stylesheet.css';
        $bag = compact('comment', 'templateStylesheet', 'forumStylesheet');
        return $this->render('preview', $bag);
    }

    /**
     * @return array
     */
    protected function systemChecks()
    {
        global $pth, $tx, $plugin_tx;

        $ptx = $plugin_tx['forum'];
        $phpVersion = '5.4.0';
        $checks = array();
        $checks[sprintf($ptx['syscheck_phpversion'], $phpVersion)]
            = version_compare(PHP_VERSION, $phpVersion) >= 0 ? 'ok' : 'fail';
        foreach (array('session') as $ext) {
            $checks[sprintf($ptx['syscheck_extension'], $ext)]
                = extension_loaded($ext) ? 'ok' : 'fail';
        }
        $checks[$ptx['syscheck_encoding']]
            = strtoupper($tx['meta']['codepage']) == 'UTF-8' ? 'ok' : 'warn';
        $check = file_exists($pth['folder']['plugins'] . 'jquery/jquery.inc.php');
        $checks[$ptx['syscheck_jquery']] = $check ? 'ok' : 'fail';
        $folders = array();
        foreach (array('config/', 'css/', 'languages/') as $folder) {
            $folders[] = $pth['folder']['plugins'] . 'forum/' . $folder;
        }
        $folders[] = $this->contents->dataFolder();
        foreach ($folders as $folder) {
            $checks[sprintf($ptx['syscheck_writable'], $folder)]
                = is_writable($folder) ? 'ok' : 'warn';
        }
        return $checks;
    }

    /**
     * @return string
     */
    protected function infoView()
    {
        global $pth, $plugin_tx;

        $ptx = $plugin_tx['forum'];
        $labels = array(
            'syscheck' => $ptx['syscheck_title'],
            'about' => $ptx['about']
        );
        $icon = $pth['folder']['plugins'] . 'forum/forum.png';
        $checks = $this->systemChecks();
        $version = FORUM_VERSION;
        $images = array();
        foreach (array('ok', 'warn', 'fail') as $state) {
            $images[$state] = $pth['folder']['plugins']
                . 'forum/images/' . $state . '.png';
        }
        $bag = compact('labels', 'checks', 'icon', 'version', 'images');
        return $this->render('info', $bag);
    }

    /**
     * @return XH_CSRFProtection
     */
    protected function getCSRFProtector()
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
}
