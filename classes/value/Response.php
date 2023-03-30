<?php

/**
 * Copyright 2023 Christoph M. Becker
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

class Response
{
    public static function create(string $output): self
    {
        $that = new self;
        $that->output = $output;
        return $that;
    }

    public static function redirect(string $location): self
    {
        $that = new self;
        $that->location = $location;
        return $that;
    }

    /** @var string */
    private $output = "";

    /** @var string|null */
    private $location = null;

    /** @var bool */
    private $exit = false;

    public function withExit(): self
    {
        $that = clone $this;
        $that->exit = true;
        return $that;
    }

    public function output(): string
    {
        return $this->output;
    }

    public function location(): ?string
    {
        return $this->location;
    }

    public function exit(): bool
    {
        return $this->exit;
    }
}
