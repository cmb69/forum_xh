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
    private $template;

    /**
     * @var array<string,mixed>
     */
    private $data;

    /**
     * @param string $key
     * @param mixed $args
     * @return string
     */
    protected function text($key, ...$args)
    {
        global $plugin_tx;

        return $this->esc(sprintf($plugin_tx['forum'][$key], ...$args));
    }

    /**
     * @param string $key
     * @param int $count
     * @param mixed $args
     * @return string
     */
    protected function plural($key, $count, ...$args)
    {
        global $plugin_tx;

        if ($count == 0) {
            $key .= '_0';
        } else {
            $key .= XH_numberSuffix($count);
        }
        return $this->esc(sprintf($plugin_tx['forum'][$key], $count, ...$args));
    }

    /**
     * @param string $template
     * @param array<string,mixed> $data
     * @return void
     */
    public function render($template, array $data)
    {
        global $pth;

        $this->template = "{$pth['folder']['plugins']}forum/views/{$template}.php";
        $this->data = $data;
        unset($template, $data, $pth);
        extract($this->data);
        include $this->template;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function esc($value)
    {
        if ($value instanceof HtmlString) {
            return $value;
        } else {
            return XH_hsc($value);
        }
    }
}
