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

use JsonSerializable;
use Plib\Document;

final class Forum implements Document, JsonSerializable
{
    /** @var array<string,TopicSummary> */
    private $topicSummaries;

    /** @return ?static */
    public static function fromString(string $contents)
    {
        $json = json_decode($contents, true);
        if (!is_array($json)) {
            return null;
        }
        $that = new static([]);
        foreach ($json as $record) {
            $topicSummary = new TopicSummary(
                $record["id"],
                $record["title"],
                $record["commentCount"],
                $record["user"],
                $record["time"],
            );
            $that->topicSummaries[$record["id"]] = $topicSummary;
        }
        return $that;
    }

    public function toString(): string
    {
        return (string) json_encode($this, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /** @return array<string,TopicSummary> */
    public function jsonSerialize(): array
    {
        return $this->topicSummaries;
    }

    /** @param list<TopicSummary> $topicSummaries */
    public function __construct(array $topicSummaries)
    {
        $this->topicSummaries = [];
        foreach ($topicSummaries as $topicSummary) {
            $this->topicSummaries[$topicSummary->id()] = $topicSummary;
        }
    }

    /** @return list<TopicSummary> */
    public function topicSummaries(): array
    {
        return array_values($this->topicSummaries);
    }

    public function topicSummary(string $id): ?TopicSummary
    {
        return $this->topicSummaries[$id] ?? null;
    }
}
