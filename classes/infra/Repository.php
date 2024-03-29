<?php

/**
 * Copyright 2023 Christoph M. Becker
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

class Repository
{
    /** @var string */
    private $folder;

    public function __construct(string $folder)
    {
        $this->folder = $folder;
    }

    public function folder(string $forum = null): string
    {
        $filename = $this->folder;
        if ($forum !== null) {
            $filename .= "$forum/";
        }
        if (!file_exists($filename)) {
            if (mkdir($filename, 0777, true)) {
                chmod($filename, 0777);
            }
        }
        return $filename;
    }

    private function file(string $forum, string $tid): string
    {
        return $this->folder($forum) . "$tid.txt";
    }

    private function cacheFile(string $forum, string $tid): string
    {
        return $this->folder($forum) . "$tid.dat";
    }

    /** @return list<Topic> */
    public function findTopics(string $forum): array
    {
        if (($topics = $this->findTopicsFromCache($forum)) !== null) {
            return $topics;
        }
        $topics = [];
        foreach (array_keys($this->findTopicNames($forum)) as $name) {
            [$topic, ] = $this->findTopic($forum, $name);
            if ($topic === null) {
                continue;
            }
            $topics[] = $topic;
        }
        file_put_contents($this->cacheFile($forum, "topics"), serialize($topics));
        return $topics;
    }

    /** @return array<string,int> */
    private function findTopicNames(string $forum): array
    {
        $names = [];
        if (($dir = opendir($this->folder($forum)))) {
            while (($entry = readdir($dir)) !== false) {
                if (!preg_match('/^([0-9A-Za-z]+)\.txt$/', $entry, $matches)) {
                    continue;
                }
                $names[$matches[1]] = (int) filemtime($this->file($forum, $matches[1]));
            }
            closedir($dir);
        }
        return $names;
    }

    /** @return list<Topic>|null */
    private function findTopicsFromCache(string $forum): ?array
    {
        $cacheFile = $this->cacheFile($forum, "topics");
        if (!is_readable($cacheFile)) {
            return null;
        }
        $cacheMtime = filemtime($cacheFile);
        $stale = array_reduce($this->findTopicNames($forum), function (bool $carry, int $mtime) use ($cacheMtime) {
            return $cacheMtime < $mtime ? true : $carry;
        }, false);
        if ($stale) {
            return null;
        }
        if (($contents = file_get_contents($cacheFile)) === false) {
            return null;
        }
        if (($topics = unserialize($contents, ["allowed_classes" => [Topic::class]])) === false) {
            return null;
        }
        /** @var list<Topic> $topics */
        return $topics;
    }

    public function hasTopic(string $forum, string $tid): bool
    {
        return file_exists($this->file($forum, $tid));
    }

    /** @return array{Topic|null,list<Comment>} */
    public function findTopic(string $forum, string $tid): array
    {
        if (($result = $this->findTopicFromCache($forum, $tid)) !== null) {
            return $result;
        }
        if (($stream = @fopen($this->file($forum, $tid), "r")) === false) {
            return [null, []];
        }
        $comments = $this->readComments($stream);
        fclose($stream);
        if ($comments === []) {
            return [null, []];
        }
        usort($comments, function (Comment $a, Comment $b) {
            return $a->time() <=> $b->time();
        });
        $first = reset($comments);
        $last = end($comments);
        $result = [new Topic($tid, $first->title() ?? "", count($comments), $last->user(), $last->time()), $comments];
        file_put_contents($this->cacheFile($forum, $tid), serialize($result));
        return $result;
    }

    /** @return array{Topic|null,list<Comment>}|null */
    private function findTopicFromCache(string $forum, string $tid): ?array
    {
        $cacheFile = $this->cacheFile($forum, $tid);
        if (!is_readable($cacheFile) || filemtime($cacheFile) < filemtime($this->file($forum, $tid))) {
            return null;
        }
        if (($contents = file_get_contents($cacheFile)) === false) {
            return null;
        }
        if (($result = unserialize($contents, ["allowed_classes" => [Topic::class, Comment::class]])) === false) {
            return null;
        }
        /** @var array{Topic|null,list<Comment>} $result */
        return $result;
    }

    public function findComment(string $forum, string $tid, string $cid): ?Comment
    {
        $stream = @fopen($this->file($forum, $tid), "r");
        if ($stream === false) {
            return null;
        }
        $comments = $this->readComments($stream);
        fclose($stream);
        return array_reduce($comments, function (?Comment $carry, Comment $comment) use ($cid) {
            return $comment->id() === $cid ? $comment : $carry;
        });
    }

    public function save(string $forum, string $tid, Comment $comment): bool
    {
        $stream = @fopen($this->file($forum, $tid), "c+");
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

    public function delete(string $forum, string $tid, string $cid): bool
    {
        $stream = @fopen($this->file($forum, $tid), "c+");
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

    public function migrate(string $forum): bool
    {
        $contents = file_get_contents($this->folder($forum) . "topics.dat");
        if ($contents === false) {
            return false;
        }
        $data = unserialize($contents);
        if (!is_array($data)) {
            return false;
        }
        foreach ($data as $tid => $record) {
            if (!$this->migrateTopic($forum, $tid, $record["title"])) {
                return false;
            }
        }
        $result = unlink($this->folder($forum) . "topics.dat");
        foreach (array_keys($data) as $tid) {
            unlink($this->folder($forum) . "$tid.dat");
        }
        return $result;
    }

    private function migrateTopic(string $forum, string $tid, string $title): bool
    {
        $contents = file_get_contents($this->folder($forum) . "$tid.dat");
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
        $stream = @fopen($this->file($forum, $tid), "c+");
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
