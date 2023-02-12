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

namespace Forum\Infra;

class Response
{
    /** @var string */
    private $output;

    /** @var string|null */
    private $location;

    /** @var bool */
    private $exit;

    /** @var string */
    private $hjs = "";

    public function __construct(string $output, ?string $location = null, bool $exit = false)
    {
        $this->output = $output;
        $this->location = $location;
        $this->exit = $exit;
    }

    /** @return void */
    public function addScript(string $basename)
    {
        if (is_file("$basename.min.js")) {
            $filename = "$basename.min.js";
        } else {
            $filename = "$basename.js";
        }
        $this->hjs .= "<script type=\"module\" src=\"$filename\"></script>";
    }

    /** @return string|never */
    public function fire()
    {
        global $hjs;

        if ($this->location !== null) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header("Location: {$this->location}", true, 303);
            exit;
        }
        if ($this->exit) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            echo $this->output;
            exit;
        }
        $hjs .= $this->hjs;
        return $this->output;
    }

    public function output(): string
    {
        return $this->output;
    }

    /** @return string|null */
    public function location()
    {
        return $this->location;
    }

    public function exit(): bool
    {
        return $this->exit;
    }
}
