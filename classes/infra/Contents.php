<?php

/**
 * Copyright 2012-2023 Christoph M. Becker
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

namespace Forum\Infra;

use Forum\Value\Comment;
use Forum\Value\Topic;

class Contents
{
    /** @var string */
    private $dataFolder;

    public function __construct(string $dataFolder)
    {
        if (substr($dataFolder, -1) != '/') {
            $dataFolder .= '/';
        }
        $this->dataFolder = $dataFolder;
    }

    public function dataFolder(?string $forum = null): string
    {
        $filename = $this->dataFolder;
        if (isset($forum)) {
            $filename .= $forum . '/';
        }
        if (!file_exists($filename)) {
            if (mkdir($filename, 0777, true)) {
                chmod($filename, 0777);
            }
        }
        return $filename;
    }

    /** @return resource|null */
    private function lock(string $forum, bool $exclusive)
    {
        $filename = $this->dataFolder($forum) . '.lock';
        touch($filename);
        $stream = fopen($filename, 'r+b');
        if (!$stream) {
            return null;
        }
        flock($stream, $exclusive ? LOCK_EX : LOCK_SH);
        return $stream;
    }

    /**
     * @param resource|null $stream
     * @return void
     */
    private function unlock($stream)
    {
        if ($stream === null) {
            return;
        }
        flock($stream, LOCK_UN);
        fclose($stream);
    }

    /** @return array<string,Topic> */
    private function getTopics(string $forum): array
    {
        $filename = $this->dataFolder($forum) . 'topics.dat';
        if (is_readable($filename)
            && ($contents = file_get_contents($filename))
        ) {
            $data = unserialize($contents);
        } else {
            $data = array();
        }
        assert(is_array($data));
        $topics = [];
        foreach ($data as $tid => $topic) {
            assert(is_string($tid) && is_array($topic));
            $topics[$tid] = new Topic(...array_values($topic));
        }
        return $topics;
    }

    /**
     * @param array<string,Topic> $topics
     */
    private function setTopics(string $forum, array $topics): bool
    {
        $data = [];
        foreach ($topics as $tid => $topic) {
            $data[$tid] = $topic->toArray();
        }
        $filename = $this->dataFolder($forum) . 'topics.dat';
        return file_put_contents($filename, serialize($data)) !== false;
    }

    public function hasTopic(string $forum, string $tid): bool
    {
        $filename = $this->dataFolder($forum) . $tid . '.dat';
        return file_exists($filename);
    }

    /** @return array<string,Comment> */
    public function getTopic(string $forum, string $tid): array
    {
        $filename = $this->dataFolder($forum) . $tid . '.dat';
        if (is_readable($filename)
            && ($contents = file_get_contents($filename))
        ) {
            $data = unserialize($contents);
        } else {
            $data = array();
        }
        assert(is_array($data));
        $topic = [];
        foreach ($data as $cid => $comment) {
            assert(is_string($cid) && is_array($comment));
            $topic[$cid] = new Comment(...array_values($comment));
        }
        return $topic;
    }

    /**
     * @param array<string,Comment> $topic
     */
    private function setTopic(string $forum, string $tid, array $topic): bool
    {
        $data = [];
        foreach ($topic as $cid => $comment) {
            $data[$cid] = $comment->toArray();
        }
        $filename = $this->dataFolder($forum) . $tid . '.dat';
        $contents = serialize($data);
        return file_put_contents($filename, $contents) !== false;
    }

    public function getId(): string
    {
        return uniqid();
    }

    public function cleanId(string $id): ?string
    {
        return preg_match('/^[a-f0-9]{13}+$/u', $id) ? $id : null;
    }

    /** @return array<string,Topic> */
    public function getSortedTopics(string $forum): array
    {
        $lock = $this->lock($forum, false);
        $topics = $this->getTopics($forum);
        $this->unlock($lock);
        uasort($topics, function ($a, $b) {
            return $b->time() - $a->time();
        });
        return $topics;
    }

    /** @return array{0:string,1:array<string,Comment>} */
    public function getTopicWithTitle(string $forum, string $tid): array
    {
        $lock = $this->lock($forum, false);
        $topics = $this->getTopics($forum);
        $topic = $this->getTopic($forum, $tid);
        $this->unlock($lock);
        return array($topics[$tid]->title(), $topic);
    }

    public function createComment(string $forum, string $tid, ?string $title, string $cid, Comment $comment): bool
    {
        $lock = $this->lock($forum, true);
        $comments = $this->getTopic($forum, $tid);
        $comments[$cid] = $comment;
        if (!$this->setTopic($forum, $tid, $comments)) {
            $this->unlock($lock);
            return false;
        }
        $topics = $this->getTopics($forum);
        $topics[$tid] = new Topic(
            $title !== null ? $title : $topics[$tid]->title(),
            count($comments),
            $comment->user(),
            $comment->time()
        );
        $res = $this->setTopics($forum, $topics);
        $this->unlock($lock);
        return $res;
    }

    public function updateComment(string $forum, string $tid, string $cid, Comment $comment): bool
    {
        $lock = $this->lock($forum, true);
        $comments = $this->getTopic($forum, $tid);
        if ($comment->user() != $comments[$cid]->user() && !(defined('XH_ADM') && XH_ADM)) {
            $this->unlock($lock);
            return false;
        }
        $newComment = new Comment($comment->user(), $comments[$cid]->time(), $comment->comment());
        $comments[$cid] = $newComment;
        $res = $this->setTopic($forum, $tid, $comments);
        $this->unlock($lock);
        return $res;
    }

    public function deleteComment(string $forum, string $tid, string $cid, Authorizer $authorizer): ?string
    {
        if (!$tid || !$cid) {
            return null;
        }
        $lock = $this->lock($forum, true);
        $topics = $this->getTopics($forum);
        $comments = $this->getTopic($forum, $tid);
        if (!$authorizer->mayModify($comments[$cid])) {
            return null;
        }
        unset($comments[$cid]);
        if (count($comments) > 0) {
            if (!$this->setTopic($forum, $tid, $comments)) {
                $this->unlock($lock);
                return null;
            }
            $comment = array_pop($comments);
            $topic = new Topic($topics[$tid]->title(), count($comments) + 1, $comment->user(), $comment->time());
            $topics[$tid] = $topic;
        } else {
            unlink($this->dataFolder($forum) . $tid . '.dat');
            unset($topics[$tid]);
            $tid = null;
        }
        $res = $this->setTopics($forum, $topics);
        $this->unlock($lock);
        return $res ? $tid : null;
    }
}
