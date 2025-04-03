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

final class Topic
{
    /** @var list<Comment> */
    private $comments;

    /** @param list<Comment> $comments */
    public function __construct(array $comments)
    {
        $this->comments = $comments;
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
}
