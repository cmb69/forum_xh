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
    private $selectedUrl;

    /** @var array<string,string> */
    private $params = [];

    /**
     * @param string $scriptName
     * @param string $selectedUrl
     */
    public function __construct($scriptName, $selectedUrl)
    {
        $this->scriptName = $scriptName;
        $this->selectedUrl = $selectedUrl;
    }

    /**
     * @param string $key
     * @param string $value
     * @return self
     */
    public function withParam($key, $value)
    {
        $url = clone $this;
        $url->params[$key] = $value;
        return $url;
    }

    /**
     * @return string
     */
    public function relative()
    {
        $query = http_build_query($this->params, "", "&");
        return "$this->scriptName?$this->selectedUrl" . ($query ? "&$query" : "");
    }

    /**
     * @return string
     */
    public function absolute()
    {
        $query = http_build_query($this->params, "", "&");
        return CMSIMPLE_URL . "?$this->selectedUrl" . ($query ? "&$query" : "");
    }
}
