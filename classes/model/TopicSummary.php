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

class TopicSummary
{
    /** @var string */
    private $id;

    /** @var string */
    private $title;

    /** @var int */
    private $commentCount;

    /** @var string */
    private $user;

    /** @var int */
    private $time;

    public function __construct(string $id, string $title, int $commentCount, string $user, int $time)
    {
        $this->id = $id;
        $this->title = $title;
        $this->commentCount = $commentCount;
        $this->user = $user;
        $this->time = $time;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function commentCount(): int
    {
        return $this->commentCount;
    }

    public function user(): string
    {
        return $this->user;
    }

    public function time(): int
    {
        return $this->time;
    }
}
