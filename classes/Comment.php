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

class Comment
{
    /** @var string */
    private $user;

    /** @var int */
    private $time;

    /** @var string */
    private $comment;

    /**
     * @param string $user
     * @param int $time
     * @param string $comment
     */
    public function __construct($user, $time, $comment)
    {
        $this->user = $user;
        $this->time = $time;
        $this->comment = $comment;
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
     * @return string
     */
    public function comment()
    {
        return $this->comment;
    }

    /**
     * @return array{user:string,time:int,comment:string}
     */
    public function toArray()
    {
        return [
            "user" => $this->user,
            "time" => $this->time,
            "comment" => $this->comment,
        ];
    }
}
