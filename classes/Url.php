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

use const CMSIMPLE_URL;

final class Url
{
    /** @var string */
    private $scriptName;

    /** @var string */
    private $page;

    /** @var array<string,string> */
    private $params = [];

    public function __construct(string $scriptName, string $page)
    {
        $this->scriptName = $scriptName;
        $this->page = $page;
    }

    public function with(string $name, string $value = ""): self
    {
        $url = clone $this;
        $url->params[$name] = $value;
        return $url;
    }

    public function absolute(): string
    {
        $query = http_build_query($this->params, "", "&");
        return CMSIMPLE_URL . "?$this->page" . ($query ? "&$query" : "");
    }

    public function relative(): string
    {
        $query = http_build_query($this->params, "", "&");
        return "$this->scriptName?$this->page" . ($query ? "&$query" : "");
    }
}
