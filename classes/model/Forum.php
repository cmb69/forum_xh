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

final class Forum implements Document
{
    /** @var string */
    private $name;

    /** @var array<string,BaseTopic> */
    private $topics;

    /** @return static */
    public static function fromString(string $contents, string $key)
    {
        $that = new static(dirname($key), []);
        if (strncmp($contents, "a:", 2) === 0) {
            // old serialization format
            $data = unserialize($contents);
            assert(is_array($data));
            foreach ($data as $tid => $record) {
                $baseTopic = new BaseTopic(
                    $tid,
                    $record["title"],
                    $record["comments"],
                    $record["user"],
                    $record["time"],
                );
                $that->topics[$tid] = $baseTopic;
            }
        } else {
            $json = json_decode($contents, true);
            if (is_array($json)) {
                foreach ($json as $record) {
                    $baseTopic = new BaseTopic(
                        $record["id"],
                        $record["title"],
                        $record["commentCount"],
                        $record["user"],
                        $record["time"],
                    );
                    $that->topics[$record["id"]] = $baseTopic;
                }
            }
        }
        return $that;
    }

    public function toString(): string
    {
        $topics = [];
        foreach ($this->topics as $id => $baseTopic) {
            $topics[$id] = [
                "id" => $id,
                "title" => $baseTopic->title(),
                "commentCount" => $baseTopic->commentCount(),
                "user" => $baseTopic->user(),
                "time" => $baseTopic->time(),
            ];
        }
        return (string) json_encode(
            $topics,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }

    /** @param list<BaseTopic> $topics */
    public function __construct(string $name, array $topics)
    {
        $this->name = $name;
        $this->topics = [];
        foreach ($topics as $topic) {
            $this->topics[$topic->id()] = $topic;
        }
    }

    /** @return list<BaseTopic> */
    public function topics(): array
    {
        return array_values($this->topics);
    }

    public function topic(string $id): ?BaseTopic
    {
        return $this->topics[$id] ?? null;
    }

    public function fetchTopic(string $id, DocumentStore $store): ?Topic
    {
        if (!array_key_exists($id, $this->topics)) {
            return null;
        }
        $topic = $store->update($this->name . "/$id.txt", Topic::class);
        assert($topic instanceof Topic);
        $this->topics[$id] = $topic;
        return $topic;
    }

    public function openTopic(string $id, DocumentStore $store): Topic
    {
        assert(!array_key_exists($id, $this->topics));
        $topic = $store->update($this->name . "/$id.txt", Topic::class);
        assert($topic instanceof Topic);
        $this->topics[$id] = $topic;
        return $topic;
    }

    public function addComment(string $id, Comment $comment): void
    {
        $topicSummary = $this->topics[$id];
        $this->topics[$id] = new BaseTopic(
            $id,
            $topicSummary->title(),
            $topicSummary->commentCount() + 1,
            $comment->user(),
            $comment->time()
        );
    }

    public function copy(Forum $other): void
    {
        assert(empty($this->topics));
        $this->topics = $other->topics;
    }
}
