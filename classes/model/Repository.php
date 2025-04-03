<?php

/**
 * Copyright (c) Christoph M. Becker
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

namespace Forum\Model;

class Repository
{
    /** @var string */
    private $folder;

    public function __construct(string $folder)
    {
        $this->folder = $folder;
    }

    public function folder(?string $forumname = null): string
    {
        $filename = $this->folder;
        if ($forumname !== null) {
            $filename .= "$forumname/";
        }
        if (!file_exists($filename)) {
            if (mkdir($filename, 0777, true)) {
                chmod($filename, 0777);
            }
        }
        return $filename;
    }

    private function file(string $forumname, string $tid): string
    {
        return $this->folder($forumname) . "$tid.txt";
    }

    private function cacheFile(string $forumname, string $tid): string
    {
        return $this->folder($forumname) . "$tid.json";
    }

    public function findForum(string $forumname): Forum
    {
        if (($forum = $this->findForumFromCache($forumname)) !== null) {
            return $forum;
        }
        $topics = [];
        foreach (array_keys($this->findTopicNames($forumname)) as $name) {
            $topicSummary = $this->findTopicSummary($forumname, $name);
            if ($topicSummary === null) {
                continue;
            }
            $topics[] = $topicSummary;
        }
        $forum = new Forum($topics);
        file_put_contents($this->cacheFile($forumname, "index"), $forum->toString());
        return $forum;
    }

    /** @return array<string,int> */
    private function findTopicNames(string $forumname): array
    {
        $names = [];
        if (($dir = opendir($this->folder($forumname)))) {
            while (($entry = readdir($dir)) !== false) {
                if (!preg_match('/^([0-9A-Za-z]+)\.txt$/', $entry, $matches)) {
                    continue;
                }
                $names[$matches[1]] = (int) filemtime($this->file($forumname, $matches[1]));
            }
            closedir($dir);
        }
        return $names;
    }

    private function findForumFromCache(string $forumname): ?Forum
    {
        $cacheFile = $this->cacheFile($forumname, "index");
        if (!is_readable($cacheFile)) {
            return null;
        }
        if (($contents = file_get_contents($cacheFile)) === false) {
            return null;
        }
        return Forum::fromString($contents);
    }

    public function hasTopic(string $forumname, string $tid): bool
    {
        return file_exists($this->file($forumname, $tid));
    }

    public function findTopic(string $forumname, string $tid): ?Topic
    {
        if (($stream = @fopen($this->file($forumname, $tid), "r")) === false) {
            return null;
        }
        $comments = $this->readComments($stream);
        fclose($stream);
        if ($comments === []) {
            return null;
        }
        usort($comments, function (Comment $a, Comment $b) {
            return $a->time() <=> $b->time();
        });
        return new Topic($tid, $comments);
    }

    private function findTopicSummary(string $forumname, string $tid): ?TopicSummary
    {
        $topic = $this->findTopic($forumname, $tid);
        if ($topic === null) {
            return null;
        }
        return new TopicSummary($tid, $topic->title(), $topic->commentCount(), $topic->user(), $topic->time());
    }

    public function findComment(string $forumname, string $tid, string $cid): ?Comment
    {
        $stream = @fopen($this->file($forumname, $tid), "r");
        if ($stream === false) {
            return null;
        }
        $comments = $this->readComments($stream);
        fclose($stream);
        return array_reduce($comments, function (?Comment $carry, Comment $comment) use ($cid) {
            return $comment->id() === $cid ? $comment : $carry;
        });
    }

    public function save(string $forumname, string $tid, Comment $comment): bool
    {
        $stream = @fopen($this->file($forumname, $tid), "c+");
        if ($stream === false) {
            return false;
        }
        $updated = false;
        $comments = array_map(function (Comment $aComment) use ($comment, &$updated) {
            if ($aComment->id() === $comment->id()) {
                $updated = true;
                return $comment;
            } else {
                return $aComment;
            }
        }, $this->readComments($stream));
        if (!$updated) {
            $comments[] = $comment;
        }
        rewind($stream);
        $result = $this->writeComments($stream, $comments);
        if ($result !== false) {
            ftruncate($stream, $result);
        }
        fclose($stream);
        return $result !== false;
    }

    public function delete(string $forumname, string $tid, string $cid): bool
    {
        $stream = @fopen($this->file($forumname, $tid), "c+");
        if ($stream === false) {
            return false;
        }
        $comments = $this->readComments($stream);
        $comments = array_filter($comments, function (Comment $comment) use ($cid) {
            return $comment->id() !== $cid;
        });
        rewind($stream);
        $result = $this->writeComments($stream, $comments);
        if ($result !== false) {
            ftruncate($stream, $result);
        }
        fclose($stream);
        return $result !== false;
    }

    /** @return list<string> */
    public function findForumsToMigrate(): array
    {
        $forums = [];
        if (($dir = opendir($this->folder()))) {
            while (($entry = readdir($dir)) !== false) {
                if ($entry[0] !== "." && file_exists($this->folder($entry) . "/topics.dat")) {
                    $contents = file_get_contents($this->folder($entry) . "/topics.dat");
                    if ($contents === false) {
                        continue;
                    }
                    $data = unserialize($contents);
                    if (!is_array($data) || !is_array(current($data))) {
                        continue;
                    }
                    $forums[] = $entry;
                }
            }
            closedir($dir);
        }
        return $forums;
    }

    public function migrate(string $forumname): bool
    {
        $contents = file_get_contents($this->folder($forumname) . "topics.dat");
        if ($contents === false) {
            return false;
        }
        $data = unserialize($contents);
        if (!is_array($data)) {
            return false;
        }
        foreach ($data as $tid => $record) {
            if (!$this->migrateTopic($forumname, $tid, $record["title"])) {
                return false;
            }
        }
        $result = unlink($this->folder($forumname) . "topics.dat");
        foreach (array_keys($data) as $tid) {
            unlink($this->folder($forumname) . "$tid.dat");
        }
        return $result;
    }

    private function migrateTopic(string $forumname, string $tid, string $title): bool
    {
        $contents = file_get_contents($this->folder($forumname) . "$tid.dat");
        if ($contents === false) {
            return false;
        }
        $data = unserialize($contents);
        if (!is_array($data)) {
            return false;
        }
        $comments = array_map(function ($id, $record) use ($title) {
            return new Comment($id, $title, $record["user"], $record["time"], $record["comment"]);
        }, array_keys($data), array_values($data));
        $stream = @fopen($this->file($forumname, $tid), "c+");
        if ($stream === false) {
            return false;
        }
        $result = $this->writeComments($stream, $comments);
        fclose($stream);
        return $result !== false;
    }

    /**
     * @param resource $stream
     * @return list<Comment>
     */
    private function readComments($stream): array
    {
        $result = [];
        $record = [];
        $headers = true;
        $body = null;
        while (($line = fgets($stream)) !== false) {
            $line = rtrim($line);
            if (!strncmp($line, "%%", strlen("%%"))) {
                if ($record) {
                    $result[] = $this->makeComment($record, $body);
                }
                $record = [];
                $headers = true;
                $body = null;
                continue;
            }
            if ($line === "") {
                $headers = false;
                continue;
            }
            if ($headers) {
                $parts = explode(":", $line, 2);
                $record[strtolower(trim($parts[0]))] = trim($parts[1]);
            } else {
                $body = $body === null ? $line : "$body\n$line";
            }
        }
        if ($record) {
            $result[] = $this->makeComment($record, $body);
        }
        return $result;
    }

    /** @param array<string,string> $record */
    private function makeComment(array $record, ?string $body): Comment
    {
        return new Comment(
            $record["id"] ?? "",
            $record["title"] ?? null,
            $record["user"] ?? "",
            isset($record["date"]) ? (int) strtotime($record["date"]) : 0,
            $body ?? ""
        );
    }

    /**
     * @param resource $stream
     * @param list<Comment> $comments
     * @return int<0,max>|false
     */
    private function writeComments($stream, array $comments)
    {
        $records = [];
        foreach ($comments as $comment) {
            $records[] = "Id: " . $comment->id() . "\n"
                . ($comment->title() !== null ? "Title: " . $comment->title() . "\n" : "")
                . "User: " . $comment->user() . "\n"
                . "Date: " . date("r", $comment->time()) . "\n"
                . "\n"
                . $comment->message();
        }
        return fwrite($stream, implode("\n%%\n", $records));
    }
}
