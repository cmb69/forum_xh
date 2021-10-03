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

namespace Forum;

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

    /**
     * @param string $title
     * @param int $comments
     * @param string $user
     * @param int $time
     */
    public function __construct($title, $comments, $user, $time)
    {
        $this->title = $title;
        $this->comments = $comments;
        $this->user = $user;
        $this->time = $time;
    }

    /**
     * @return string
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function comments()
    {
        return $this->comments;
    }

    /**
     * @return string
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * @return int
     */
    public function time()
    {
        return $this->time;
    }

    /**
     * @return array{title:string,comments:int,user:string,time:int}
     */
    public function toArray()
    {
        return [
            "title" => $this->title,
            "comments" => $this->comments,
            "user" => $this->user,
            "time" => $this->time,
        ];
    }
}
