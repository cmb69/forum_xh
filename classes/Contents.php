<?php

/**
 * The contents.
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
 * The contents.
 *
 * @category CMSimple_XH
 * @package  Forum
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Forum_XH
 */
class Forum_Contents
{
    /**
     * The path of the data folder.
     *
     * @var string
     */
    protected $dataFolder;

    /**
     * An associative array from forum names to their lock handles.
     *
     * @var array
     */
    protected $lockHandles = array();

    /**
     * Constructs an instance.
     *
     * @param string $dataFolder The path of the data folder.
     */
    public function __construct($dataFolder)
    {
        if (substr($dataFolder, -1)) {
            $dataFolder .= '/';
        }
        $this->dataFolder = $dataFolder;
    }

    /**
     * Returns the path of the data folder of the forums or an individual forum.
     *
     * @param string $forum The name of a forum.
     *                      <var>null</var> means the general forum folder.
     *
     * @return string
     */
    function dataFolder($forum = null)
    {
        $filename = $this->dataFolder;
        if (isset($forum)) {
            $filename .= $forum . '/';
        }
        if (file_exists($filename)) {
            if (!is_dir($filename)) {
                e('cntopen', 'folder', $filename); // exception
            }
        } else {
            if (!mkdir($filename, 0777, true)) {
                e('cntsave', 'folder', $filename); // exception
            }
        }
        return $filename;
    }

    /**
     * Locks resp. unlocks a forum's database.
     *
     * @param string $forum The name of a forum.
     * @param int    $op    The locking operation.
     *
     * @return void
     *
     * @todo Error handling.
     */
    function lock($forum, $op)
    {
        $filename = $this->dataFolder($forum) . '.lock';
        touch($filename);
        switch ($op) {
        case LOCK_SH:
        case LOCK_EX:
            $this->lockHandles[$forum] = fopen($filename, 'r+b');
            flock($this->lockHandles[$forum], $op);
            break;
        case LOCK_UN:
            flock($this->lockHandles[$forum], $op);
            fclose($this->lockHandles[$forum]);
            unset($this->lockHandles[$forum]);
            break;
        }
    }

    /**
     * Returns a forum's topics.
     *
     * @param string $forum The name of a forum.
     *
     * @return array
     */
    function getTopics($forum)
    {
        $filename = $this->dataFolder($forum) . 'topics.dat';
        if (is_readable($filename)
            && ($contents = file_get_contents($filename))
        ) {
            $data = unserialize($contents);
        } else {
            $data = array();
        }
        return $data;
    }

    /**
     * Writes a forum's topics.
     *
     * @param string $forum The name of a forum.
     * @param array  $data  The topic data.
     *
     * @return void
     */
    function setTopics($forum, $data)
    {
        $filename = $this->dataFolder($forum) . 'topics.dat';
        $contents = serialize($data);
        if (!file_put_contents($filename, serialize($data))) {
            e('cntsave', 'file', $filename); // throw exeption
        }
    }

    /**
     * Returns a topic.
     *
     * @param string $forum The name of a forum.
     * @param string $tid   A topic ID.
     *
     * @return array
     */
    function getTopic($forum, $tid)
    {
        $filename = $this->dataFolder($forum) . $tid . '.dat';
        if (is_readable($filename)
            && ($contents = file_get_contents($filename))
        ) {
            $data = unserialize($contents);
        } else {
            $data = array();
        }
        return $data;
    }

    /**
     * Writes the topic $tid.
     *
     * @param string $forum The name of a forum.
     * @param string $tid   A topic ID.
     * @param array  $data  The new topic contents.
     *
     * @return void
     */
    function setTopic($forum, $tid, $data)
    {
        $filename = $this->dataFolder($forum) . $tid . '.dat';
        $contents = serialize($data);
        if (!file_put_contents($filename, $contents)) {
            e('cntsave', 'file', $filename); // exception
        }
    }

    /**
     * Returns a new ID.
     *
     * @return string
     */
    function getId()
    {
        return uniqid();
    }

    /**
     * Returns <var>$id</var>, if it's a valid ID, <var>false</var> otherwise.
     *
     * @param string $id An ID to check.
     *
     * @return string
     */
    function cleanId($id)
    {
        return preg_match('/^[a-f0-9]{13}+$/u', $id)
                ? $id : false;
    }

    /**
     * Returns the result of comparing two topic times.
     *
     * @param array $a A topic.
     * @param array $b Another topic.
     *
     * @return int
     */
    function compareTopicTime($a, $b)
    {
        return $b['time'] - $a['time'];
    }

    /**
     * Returns a chronologically ordered array of topics.
     *
     * @param string $forum A forum name.
     *
     * @return array
     */
    function getSortedTopics($forum)
    {
        $this->lock($forum, LOCK_SH);
        $topics = $this->getTopics($forum);
        $this->lock($forum, LOCK_UN);
        uasort($topics, array($this, 'compareTopicTime'));
        return $topics;
    }

    /**
     * Returns a numeric array with topic title and topic details.
     *
     * @param string $forum A forum name.
     * @param string $tid   A topic ID.
     *
     * @return array
     */
    function getTopicWithTitle($forum, $tid)
    {
        $this->lock($forum, LOCK_SH);
        $topics = $this->getTopics($forum);
        $topic = $this->getTopic($forum, $tid);
        $this->lock($forum, LOCK_UN);
        return array($topics[$tid]['title'], $topic);
    }

    /**
     * Creates a comment.
     *
     * @param string $forum   A forum name.
     * @param string $tid     A thread ID.
     * @param string $title   A title of the comment.
     * @param string $cid     A comment ID.
     * @param array  $comment A comment record.
     *
     * @return void
     */
    function createComment($forum, $tid, $title, $cid, $comment)
    {
        $this->lock($forum, LOCK_EX);

        $comments = $this->getTopic($forum, $tid);
        $comments[$cid] = $comment;
        $this->setTopic($forum, $tid, $comments);

        $topics = $this->getTopics($forum);
        $topics[$tid] = array(
            'title' => isset($title) ? $title : $topics[$tid]['title'],
            'comments' => count($comments),
            'user' => $comment['user'],
            'time' => $comment['time']);
        $this->setTopics($forum, $topics);

        $this->lock($forum, LOCK_UN);
    }

    /**
     * Updates a comment.
     *
     * @param string $forum   A forum name.
     * @param string $tid     A topic ID.
     * @param string $cid     A comment ID.
     * @param array  $comment A comment record.
     * @param string $user    A user.
     *
     * @return void
     */
    function updateComment($forum, $tid, $cid, $comment, $user)
    {
        $this->lock($forum, LOCK_EX);

        $comments = $this->getTopic($forum, $tid);
        if ($comment['user'] != $comments[$cid]['user']) {
            $this->lock($forum, LOCK_UN);
            return; // TODO throw exception
        }
        $comment['time'] = $comments[$cid]['time'];
        $comments[$cid] = $comment;
        $this->setTopic($forum, $tid, $comments);

        $this->lock($forum, LOCK_UN);
    }

    /**
     * Deletes a comment
     *
     * Returns the topic ID, if the topic has further comments,
     * otherwise <var>null</var>,
     * or <var>false</var>, if the comment couldn't be deleted.
     *
     * @param string $forum The name of a forum.
     * @param string $tid   A topic ID.
     * @param string $cid   A comment ID.
     * @param string $user  The user who triggers the delete.
     *
     * @return string
     */
    function deleteComment($forum, $tid, $cid, $user)
    {
        global $adm;

        if (!$tid || !$cid) {
            return false;
        }
        $this->lock($forum, LOCK_EX);
        $topics = $this->getTopics($forum);
        $comments = $this->getTopic($forum, $tid);
        if (!($user === true || $user == $comments[$cid]['user'])) {
            return false;
        }
        unset($comments[$cid]);
        if (count($comments) > 0) {
            $this->setTopic($forum, $tid, $comments);
            $comment = array_pop($comments);
            $topics[$tid]['comments'] = count($comments);
            $topics[$tid]['user'] = $comment['user'];
            $topics[$tid]['time'] = $comment['time'];
        } else {
            unlink($this->dataFolder($forum) . $tid . '.dat');
            unset($topics[$tid]);
            $tid = null;
        }
        $this->setTopics($forum, $topics);
        $this->lock($forum, LOCK_UN);
        return $tid;
    }
}

?>
