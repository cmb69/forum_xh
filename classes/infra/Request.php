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

use Forum\Value\Url;

class Request
{
    /** @codeCoverageIgnore */
    public static function current(): self
    {
        return new self;
    }

    public function url(): Url
    {
        $rest = $this->query();
        if ($rest !== "") {
            $rest = "?" . $rest;
        }
        return Url::from(CMSIMPLE_URL . $rest);
    }

    /** @return array{string,string} */
    public function deletePost(): array
    {
        return [
            $this->postString("forum_topic"),
            $this->postString("forum_comment"),
        ];
    }

    /** @return array{title:string|null,topic:string|null,comment:string|null,text:string} */
    public function commentPost(): array
    {
        return [
            "title" => $this->postString("forum_title") ?: null,
            "topic" => $this->postString("forum_topic") ?: null,
            "comment" => $this->postString("forum_comment") ?: null,
            "text" => $this->postString("forum_text"),
        ];
    }

    private function postString(string $name): string
    {
        $post = $this->post();
        return isset($post[$name]) && is_string($post[$name]) ? $post[$name] : "";
    }

    /** @codeCoverageIgnore */
    protected function query(): string
    {
        return $_SERVER["QUERY_STRING"];
    }

    /**
     * @return array<string,string|array<string>>
     * @codeCoverageIgnore
     */
    protected function post()
    {
        return $_POST;
    }
}
