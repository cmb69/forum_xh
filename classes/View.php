<?php

/**
 * Copyright 2017-2021 Christoph M. Becker
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

use function XH_hsc;
use function XH_numberSuffix;

class View
{
    /** @var string */
    private $templateFolder;

    /** @var array<string,string> */
    private $lang;

    /**
     * @param string $templateFolder
     * @param array<string,string> $lang
     */
    public function __construct($templateFolder, array $lang)
    {
        $this->lang = $lang;
        $this->templateFolder = $templateFolder;
    }

    /**
     * @param string $key
     * @param string|HtmlString $args
     * @return string
     */
    public function text($key, ...$args)
    {
        $args = array_map([$this, "esc"], $args);
        return sprintf($this->esc($this->lang[$key]), ...$args);
    }

    /**
     * @param string $key
     * @param int $count
     * @param string|HtmlString $args
     * @return string
     */
    public function plural($key, $count, ...$args)
    {
        if ($count == 0) {
            $key .= '_0';
        } else {
            $key .= XH_numberSuffix($count);
        }
        $args = array_map([$this, "esc"], $args);
        return sprintf($this->esc($this->lang[$key]), $count, ...$args);
    }

    /**
     * @param string $_template
     * @param array<string,mixed> $_data
     * @return void
     */
    public function render($_template, array $_data)
    {
        extract($_data);
        include "{$this->templateFolder}/{$_template}.php";
    }

    /**
     * @param string|HtmlString $value
     * @return string
     */
    public function esc($value)
    {
        if ($value instanceof HtmlString) {
            return $value->asString();
        } else {
            return XH_hsc($value);
        }
    }
}
