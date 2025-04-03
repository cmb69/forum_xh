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

final class Topic implements Document
{
    /** @var list<Comment> */
    private $comments;

    /** @return static */
    public static function fromString(string $contents)
    {
        $that = new static([]);
        $lines = explode("\n", $contents);
        $record = [];
        $headers = true;
        $body = null;
        foreach ($lines as $line) {
            $line = rtrim($line);
            if (!strncmp($line, "%%", strlen("%%"))) {
                if ($record) {
                    $that->comments[] = self::makeComment($record, $body);
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
            $that->comments[] = self::makeComment($record, $body);
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

    /** @param list<Comment> $comments */
    public function __construct(array $comments)
    {
        $this->comments = $comments;
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
        return $this->comments;
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
}
