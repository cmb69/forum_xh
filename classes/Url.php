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

namespace Forum;

class Url
{
    /** @var string */
    private $base;

    /** @var string */
    private $page;

    /** @var array<string,string|array<string>> */
    private $params;

    /** @param array<string,string|array<string>> $params */
    public function __construct(string $base, string $page, array $params)
    {
        $this->base = $base;
        $this->page = $page;
        $this->params = $params;
    }

    /** @param array<string,string> $params */
    public function replace(array $params): self
    {
        return new Url($this->base, $this->page, array_replace($this->params, $params));
    }

    public function relative(): string
    {
        $rest = http_build_query($this->params, "", "&", PHP_QUERY_RFC3986);
        $rest = preg_replace('/=(?=&|$)/', '', $rest);
        $query = $this->page . ($rest !== "" ? "&{$rest}": "");
        return $this->base . ($query !== "" ? "?{$query}" : "");
    }

    public function absolute(): string
    {
        $rest = http_build_query($this->params, "", "&", PHP_QUERY_RFC3986);
        $rest = preg_replace('/=(?=&|$)/', '', $rest);
        $query = $this->page . ($rest !== "" ? "&{$rest}": "");
        return CMSIMPLE_URL . ($query !== "" ? "?{$query}" : "");
    }
}
