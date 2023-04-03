<?php

/**
 * Copyright 2021-2023 Christoph M. Becker
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

class Comment
{
    /** @var string */
    private $id;

    /** @var string|null */
    private $title;

    /** @var string */
    private $user;

    /** @var int */
    private $time;

    /** @var string */
    private $message;

    public function __construct(string $id, ?string $title, string $user, int $time, string $message)
    {
        $this->id = $id;
        $this->title = $title;
        $this->user = $user;
        $this->time = $time;
        $this->message = $message;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function user(): string
    {
        return $this->user;
    }

    public function time(): int
    {
        return $this->time;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function with(string $title, string $message): self
    {
        $that = clone $this;
        $that->title = $title;
        $that->message = $message;
        return $that;
    }
}
