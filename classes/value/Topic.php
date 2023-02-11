<?php

/**
 * Copyright 2021 Christoph M. Becker
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

namespace Forum\Value;

class Topic
{
    /** @var string */
    private $title;

    /** @var int */
    private $comments;

    /** @var string */
    private $user;

    /** @var int */
    private $time;

    public function __construct(string $title, int $comments, string $user, int $time)
    {
        $this->title = $title;
        $this->comments = $comments;
        $this->user = $user;
        $this->time = $time;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function comments(): int
    {
        return $this->comments;
    }

    public function user(): string
    {
        return $this->user;
    }

    public function time(): int
    {
        return $this->time;
    }

    /** @return array{title:string,comments:int,user:string,time:int} */
    public function toArray(): array
    {
        return [
            "title" => $this->title,
            "comments" => $this->comments,
            "user" => $this->user,
            "time" => $this->time,
        ];
    }
}
