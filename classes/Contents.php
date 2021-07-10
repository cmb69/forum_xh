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

class Contents
{
    /**
     * @var string
     */
    private $dataFolder;

    /**
     * @var array<string,resource>
     */
    private $lockHandles = array();

    /**
     * @param string $dataFolder
     */
    public function __construct($dataFolder)
    {
        if (substr($dataFolder, -1) != '/') {
            $dataFolder .= '/';
        }
        $this->dataFolder = $dataFolder;
    }

    /**
     * @param string $forum
     * @return string
     */
    public function dataFolder($forum = null)
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
            if (mkdir($filename, 0777, true)) {
                chmod($filename, 0777);
            } else {
                e('cntsave', 'folder', $filename); // exception
            }
        }
        return $filename;
    }

    /**
     * @param string $forum
     * @param int $op
     * @return void
     */
    private function lock($forum, $op)
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
     * @param string $forum
     * @return array<string,mixed>
     */
    private function getTopics($forum)
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
     * @param string $forum
     * @param array<string,array> $data
     * @return void
     */
    private function setTopics($forum, $data)
    {
        $filename = $this->dataFolder($forum) . 'topics.dat';
        if (!file_put_contents($filename, serialize($data))) {
            e('cntsave', 'file', $filename); // throw exeption
        }
    }

    /**
     * @param string $forum
     * @param string $tid
     * @return array<string,array>
     */
    public function getTopic($forum, $tid)
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
     * @param string $forum
     * @param string $tid
     * @param array<string,array> $data
     * @return void
     */
    private function setTopic($forum, $tid, $data)
    {
        $filename = $this->dataFolder($forum) . $tid . '.dat';
        $contents = serialize($data);
        if (!file_put_contents($filename, $contents)) {
            e('cntsave', 'file', $filename); // exception
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return uniqid();
    }

    /**
     * @param string $id
     * @return string|false
     */
    public function cleanId($id)
    {
        return preg_match('/^[a-f0-9]{13}+$/u', $id) ? $id : false;
    }

    /**
     * @param string $forum
     * @return array<string,mixed>
     */
    public function getSortedTopics($forum)
    {
        $this->lock($forum, LOCK_SH);
        $topics = $this->getTopics($forum);
        $this->lock($forum, LOCK_UN);
        uasort($topics, function ($a, $b) {
            return $b['time'] - $a['time'];
        });
        return $topics;
    }

    /**
     * @param string $forum
     * @param string $tid
     * @return array{0:string,1:array}
     */
    public function getTopicWithTitle($forum, $tid)
    {
        $this->lock($forum, LOCK_SH);
        $topics = $this->getTopics($forum);
        $topic = $this->getTopic($forum, $tid);
        $this->lock($forum, LOCK_UN);
        return array($topics[$tid]['title'], $topic);
    }

    /**
     * @param string $forum
     * @param string $tid
     * @param string|null $title
     * @param string $cid
     * @param array<string,mixed> $comment
     * @return void
     */
    public function createComment($forum, $tid, $title, $cid, $comment)
    {
        $this->lock($forum, LOCK_EX);

        $comments = $this->getTopic($forum, $tid);
        $comments[$cid] = $comment;
        $this->setTopic($forum, $tid, $comments);

        $topics = $this->getTopics($forum);
        $topics[$tid] = array(
            'title' => $title !== null ? $title : $topics[$tid]['title'],
            'comments' => count($comments),
            'user' => $comment['user'],
            'time' => $comment['time']);
        $this->setTopics($forum, $topics);

        $this->lock($forum, LOCK_UN);
    }

    /**
     * @param string $forum
     * @param string $tid
     * @param string $cid
     * @param array<string,mixed> $comment
     * @return void
     */
    public function updateComment($forum, $tid, $cid, $comment)
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
     * @param string $forum
     * @param string $tid
     * @param string $cid
     * @param string|bool $user
     * @return string|false
     */
    public function deleteComment($forum, $tid, $cid, $user)
    {
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
            $topics[$tid]['comments'] = count($comments) + 1;
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
