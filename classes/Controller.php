<?php

/**
 * The controllers.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Forum
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2012-2014 Christoph M. Becker <http://3-magi.net>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Forum_XH
 */

/**
 * The controllers.
 *
 * @category CMSimple_XH
 * @package  Forum
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Forum_XH
 */
class Forum_Controller
{
    /**
     * The contents object.
     *
     * @var object
     */
    var $contents;

    /**
     * The BBCode to HTML converter.
     *
     * @var object
     */
    var $bbcode;

    /**
     * Constructs an instance.
     */
    function __construct()
    {
        global $pth, $plugin_cf;

        $folder = $plugin_cf['forum']['folder_data'] != ''
            ? $pth['folder']['base'] . $plugin_cf['forum']['folder_data']
            : $pth['folder']['plugins'] . 'forum/data/';
        $this->contents = new Forum_Contents($folder);
    }

    /**
     * Returns the BBCode to HTML converter. Creates the object, if necessary.
     *
     * @return object
     *
     * @global array The paths of system files and folders.
     */
    function getBbcode()
    {
        global $pth;

        if (!isset($this->bbcode)) {
            $emoticonFolder = $pth['folder']['plugins'] . 'forum/images/';
            $this->bbcode = new Forum_BBCode($emoticonFolder);
        }
        return $this->bbcode;
    }

    /**
     * Returns the numerus suffix for the language keys.
     *
     * @param int $count A number.
     *
     * @return string
     */
    function numerus($count)
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
     * Returns the currently logged in user (Memberpages or Register).
     *
     * @return string
     */
    function user()
    {
        if (session_id() == '') {
            session_start();
        }
        return isset($_SESSION['Name'])
            ? $_SESSION['Name']
            : (isset($_SESSION['username']) ? $_SESSION['username'] : false);
    }

    /**
     * Processes a posted comment.
     *
     * Returns the topic ID, if the comment could be posted,
     * <var>false</var> otherwise.
     *
     * @param string $forum A forum name.
     * @param string $tid   A topic ID (<var>null</var> means new topic).
     * @param string $cid   A comment ID.
     *
     * @return string
     */
    function postComment($forum, $tid = null, $cid = null)
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
     * Deletes a comment
     *
     * Returns the topic ID, if the topic has further comments,
     * otherwise <var>null</var>,
     * or <var>false</var>, if the comment couldn't be deleted.
     *
     * @param string $forum A forum name.
     * @param string $tid   A topic ID.
     * @param string $cid   A comment ID.
     *
     * @return void
     *
     * @global bool   Whether we're logged in as administrator.
     * @global string The URL of the current page.
     */
    function deleteComment($forum, $tid, $cid)
    {
        global $adm, $su;

        $tid = $this->contents->cleanId($_POST['forum_topic']);
        $cid = $this->contents->cleanId($_POST['forum_comment']);
        $user = $adm ? true : $this->user();
        $queryString = $this->contents->deleteComment($forum, $tid, $cid, $user)
            ? '?' . $su . '&forum_topic=' . $tid : '?' . $su;
        header('Location: ' . FORUM_URL . $queryString, true, 303);
        exit;
    }

    /**
     * Includes JS and CSS to the <head>.
     *
     * @return void
     *
     * @global array  The paths of system files and folders.
     * @global string The (X)HTML of the head element.
     * @global array  The localization of the plugins.
     */
    function hjs()
    {
        global $pth, $hjs, $plugin_tx;

        $ptx = $plugin_tx['forum'];
        $dir = $pth['folder']['plugins'].'forum/markitup/';
        $hjs .= tag(
            'link rel="stylesheet" type="text/css" href="' . $dir
            . 'skins/simple/style.css"'
        ) . "\n"
            . tag(
                'link rel="stylesheet" type="text/css" href="' . $dir
                . 'sets/bbcode/style.css"'
            ) . "\n";
        include_once $pth['folder']['plugins'] . 'jquery/jquery.inc.php';
        include_jQuery();
        include_jQueryPlugin('markitup', $dir . 'jquery.markitup.js');
        $hjs .= '<script type="text/javascript">/* <![CDATA[ */' . "\n"
            . 'Forum = {TX: {';
        $texts = array(
            'title_missing', 'comment_missing', 'bold', 'italic', 'underline',
            'emoticon', 'smile', 'wink', 'happy', 'grin', 'tongue', 'surprised',
            'unhappy', 'picture', 'link', 'size', 'big', 'normal', 'small',
            'bulleted_list', 'numeric_list', 'list_item', 'quotes', 'code',
            'clean', 'preview', 'link_text'
        );
        foreach ($texts as $i => $key) {
            if ($i > 0) {
                $hjs .= ', ';
            }
            $hjs .= strtoupper($key) . ': \''
                . addcslashes(
                    $ptx['lbl_'.$key], "\0..\37\\\'"
                ) . '\'';
        }
        $hjs .= '}}' . "\n"
            . 'jQuery(function() {jQuery(\'form.forum_comment textarea\')'
            . '.markItUp(Forum.settings)})' . "\n"
            . '/* ]]> */</script>' . "\n";
        $hjs .= '<script type="text/javascript" src="' . $dir
            . 'sets/bbcode/set.js"></script>' . "\n";
    }

    /**
     * Returns the comment form.
     *
     * @param string $tid A topic ID.
     * @param string $cid A comment ID.
     *
     * @return string  The (X)HTML.
     *
     * @global string The URL of the requested page.
     * @global array  The localization of the plugins.
     */
    function commentForm($tid = null, $cid = null)
    {
        global $su, $plugin_tx;

        if ($this->user() === false) {
            return false;
        }
        $ptx = $plugin_tx['forum'];
        $this->hjs();

        $newTopic = !isset($tid);
        $labels = array(
            'heading' => $newTopic
                ? $ptx['msg_new_topic']
                : (isset($cid) ? $ptx['msg_edit_comment'] : $ptx['msg_add_comment']),
            'title' => $ptx['msg_title'],
            'submit' => $ptx['lbl_submit'],
            'back' => $ptx['msg_back']
        );
        $comment = '';
        if (isset($cid)) {
            $topics = $this->contents->getTopic('test', $tid);
            if ($topics[$cid]['user'] == $this->user()) {
                $comment = $topics[$cid]['comment'];
            }
            //$newTopic = true; // FIXME: hack to force overview link to be shown
        }
        $action = '?' . $su . '&amp;forum_actn=post';
        $overviewUrl = '?' . $su;

        $bag = compact(
            'newTopic', 'labels', 'tid', 'cid', 'action', 'overviewUrl', 'comment'
        );
        return $this->render('form', $bag);
    }

    /**
     * Returns the posted by/on/at view.
     *
     * @param array $rec A topic or comment record.
     *
     * @return string The (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    function posted($rec)
    {
        global $plugin_tx;

        $ptx = $plugin_tx['forum'];
        $date = date($ptx['format_date'], $rec['time']);
        $time = date($ptx['format_time'], $rec['time']);
        return str_replace(
            array('{user}', '{date}', '{time}'),
            array($rec['user'], $date, $time), $ptx['msg_posted']
        );
    }

    /**
     * Returns the topics overview.
     *
     * @param string $forum A forum name.
     *
     * @return string The (X)HTML.
     *
     * @global string The URL of the requested page.
     * @global array  The localization of the plugins.
     */
    function viewTopics($forum)
    {
        global $su, $plugin_tx;

        $ptx = $plugin_tx['forum'];
        $topics = $this->contents->getSortedTopics($forum);
        $label = array(
            'heading' => $ptx['msg_topics'],
            'start_topic' => $ptx['msg_start_topic']
        );
        $i = 1;
        foreach ($topics as $tid => &$topic) {
            $topic['href'] = "?$su&amp;forum_topic=$tid";
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
        $href = "?$su&amp;forum_actn=new";
        $bag = compact('label', 'topics', 'href', 'is_user');
        return $this->render('topics', $bag);
    }

    /**
     * Returns the topic view.
     *
     * @param string $forum A forum name.
     * @param string $tid   A topic ID.
     *
     * @return string  The (X)HTML.
     *
     * @global string The script name.
     * @global string The requested page URL.
     * @global array  The paths of system files and folders.
     * @global bool   Whether we're logged in as administrator.
     * @global array  The localization of the plugins.
     */
    function viewTopic($forum, $tid)
    {
        global $sn, $su, $pth, $adm, $plugin_tx;

        $ptx = $plugin_tx['forum'];
        list($title, $topic) = $this->contents->getTopicWithTitle($forum, $tid);
        $href = "?$su";
        $editUrl = $sn . '?' . $su . '&forum_actn=edit&forum_topic=' . $tid
            . '&forum_comment=';
        $i = 1;
        $label = array(
            'title' => htmlspecialchars($title, ENT_NOQUOTES, 'UTF-8'),
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
            $comment['comment'] = $this->getBbcode()->toHtml($comment['comment']);
            $comment['details'] = $this->posted($comment);
            $comment['editUrl'] = $editUrl . $cid;
            $i++;
        }
        $isUser = $this->user() !== false;
        $commentForm = $this->commentForm($tid);

        $bag = compact(
            'label', 'tid', 'topic', 'su', 'deleteImg', 'editImg', 'href',
            'isUser', 'commentForm'
        );
        return $this->render('topic', $bag);
    }

    /**
     * Handles the forum requests.
     *
     * @param string $forum A forum name.
     *
     * @return mixed
     *
     * @global string The requested page URL.
     * @global string The (X)HTML of the error messages.
     * @global array  The localization of the plugins.
     */
    function main($forum)
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
        case 'new':
            return $this->commentForm();
        case 'post':
            if (!empty($_POST['forum_comment'])) {
                $tid = $this->postComment(
                    $forum, $_POST['forum_topic'], $_POST['forum_comment']
                );
            } else {
                $tid = $this->postComment($forum, $_POST['forum_topic']);
            }
            $params = $tid ? "?$su&forum_topic=$tid" : "?$su";
            header('Location: ' . FORUM_URL . $params, true, 303);
            exit;
        case 'edit':
            $tid = $this->contents->cleanId($_GET['forum_topic']);
            $cid = $this->contents->cleanId($_GET['forum_comment']);
            if ($tid && $cid) {
                return $this->commentForm($tid, $cid);
            } else {
                return ''; // should display error
            }
        case 'delete':
            $this->deleteComment($forum, $tid, $cid);
            break;
        }
    }

    /**
     * Returns an instantiated view template.
     *
     * @param string $_template A template name.
     * @param array  $_bag      Variables for the template.
     *
     * @global array The paths of system files and folders.
     * @global array The configuration of the core.
     *
     * @return string (X)HTML
     */
    function render($_template, $_bag)
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
     * Returns the comment preview.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     */
    function commentPreview()
    {
        global $pth;

        $comment = $this->getBbcode()->toHtml(stsl($_POST['data']));
        $templateStylesheet = $pth['file']['stylesheet'];
        $forumStylesheet = $pth['folder']['plugins'] . 'forum/css/stylesheet.css';
        $bag = compact('comment', 'templateStylesheet', 'forumStylesheet');
        return $this->render('preview', $bag);
    }

    /**
     * Returns the system checks.
     *
     * @return array
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the core.
     * @global array The localization of the plugins.
     */
    function systemChecks()
    {
        global $pth, $tx, $plugin_tx;

        $ptx = $plugin_tx['forum'];
        $phpVersion = '5.0.0';
        $checks = array();
        $checks[sprintf($ptx['syscheck_phpversion'], $phpVersion)]
            = version_compare(PHP_VERSION, $phpVersion) >= 0 ? 'ok' : 'fail';
        foreach (array('pcre', 'session') as $ext) {
            $checks[sprintf($ptx['syscheck_extension'], $ext)]
                = extension_loaded($ext) ? 'ok' : 'fail';
        }
        $checks[$ptx['syscheck_magic_quotes']]
            = !get_magic_quotes_runtime() ? 'ok' : 'fail';
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
     * Returns the plugin information view.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the plugins.
     */
    function infoView()
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
}

?>