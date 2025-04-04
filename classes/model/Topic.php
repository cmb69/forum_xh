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

use Plib\Document;
use Plib\DocumentStore;

final class Topic extends BaseTopic implements Document
{
    /** @var string */
    private $id;

    /** @var array<string,Comment> */
    private $comments;

    /** @return static */
    public static function fromString(string $contents, string $key)
    {
        $that = new static(basename($key, ".txt"), []);
        if (strncmp($contents, "a:", 2) === 0) {
            // old serialization format
            $data = unserialize($contents);
            assert(is_array($data));
            foreach ($data as $id => $record) {
                $that->comments[$id] = new Comment($id, null, $record["user"], $record["time"], $record["comment"]);
            }
        } else {
            $lines = explode("\n", $contents);
            $record = [];
            $headers = true;
            $body = null;
            foreach ($lines as $line) {
                $line = rtrim($line);
                if (!strncmp($line, "%%", strlen("%%"))) {
                    if ($record) {
                        $comment = self::makeComment($record, $body);
                        $that->comments[$comment->id()] = $comment;
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
                $comment = self::makeComment($record, $body);
                $that->comments[$comment->id()] = $comment;
            }
        }
        return $that;
    }

    /** @param array<string,string> $record */
    private static function makeComment(array $record, ?string $body): Comment
    {
        return new Comment(
            $record["id"] ?? "",
            $record["title"] ?? null,
            $record["user"] ?? "",
            isset($record["date"]) ? (int) strtotime($record["date"]) : 0,
            $body ?? ""
        );
    }

    public static function retrieve(string $forumname, string $id, DocumentStore $store): Topic
    {
        $topic = $store->retrieve($forumname . "/$id.txt", Topic::class);
        assert($topic !== null);
        return $topic;
    }

    /** @param list<Comment> $comments */
    public function __construct(string $id, array $comments)
    {
        $this->id = $id;
        $this->comments = [];
        foreach ($comments as $comment) {
            $this->comments[$comment->id()] = $comment;
        }
    }

    public function toString(): string
    {
        $records = [];
        foreach ($this->comments as $comment) {
            $records[] = "Id: " . $comment->id() . "\n"
                . ($comment->title() !== null ? "Title: " . $comment->title() . "\n" : "")
                . "User: " . $comment->user() . "\n"
                . "Date: " . date("r", $comment->time()) . "\n"
                . "\n"
                . $comment->message();
        }
        return implode("\n%%\n", $records);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        if (empty($this->comments)) {
            return "";
        }
        $first = reset($this->comments);
        return $first->title() ?? "";
    }

    public function commentCount(): int
    {
        return count($this->comments);
    }

    /** @return list<Comment> */
    public function comments(): array
    {
        return array_values($this->comments);
    }

    public function comment(string $id): ?Comment
    {
        return $this->comments[$id] ?? null;
    }

    public function user(): string
    {
        if (empty($this->comments)) {
            return "";
        }
        $last = end($this->comments);
        return $last->user();
    }

    public function time(): int
    {
        if (empty($this->comments)) {
            return 0;
        }
        $last = end($this->comments);
        return $last->time();
    }

    public function sortComments(): void
    {
        usort($this->comments, function (Comment $a, Comment $b) {
            return $a->time() <=> $b->time();
        });
    }

    public function addComment(string $id, string $title, string $user, int $time, string $message): Comment
    {
        assert(!array_key_exists($id, $this->comments));
        $this->comments[$id] = new Comment($id, $title, $user, $time, $message);
        return $this->comments[$id];
    }

    public function updateComment(string $id, string $title, string $message): void
    {
        assert(array_key_exists($id, $this->comments));
        $comment = $this->comments[$id];
        $comment->setTitle($title);
        $comment->setMessage($message);
    }

    public function deleteComment(string $id): void
    {
        assert(array_key_exists($id, $this->comments));
        unset($this->comments[$id]);
    }

    public function copy(Topic $other, string $title): void
    {
        assert(empty($this->comments));
        $this->comments = $other->comments;
        foreach ($this->comments as $comment) {
            $comment->setTitle($title);
        }
    }
}
