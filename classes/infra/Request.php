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

    public function action(): string
    {
        $action = $this->url()->param("forum_action");
        if (!is_string($action)) {
            return "";
        }
        if (!strncmp($action, "do_", strlen("do_"))) {
            return "";
        }
        $post = $this->post();
        if (isset($post["forum_do"])) {
            return "do_$action";
        }
        return $action;
    }

    public function topic(): ?string
    {
        $topic = $this->url()->param("forum_topic");
        if (!is_string($topic)) {
            return null;
        }
        return preg_match('/^[a-f0-9]{13}+$/u', $topic) ? $topic : null;
    }

    public function comment(): ?string
    {
        $comment = $this->url()->param("forum_comment");
        if (!is_string($comment)) {
            return null;
        }
        return preg_match('/^[a-f0-9]{13}+$/u', $comment) ? $comment : null;
    }

    public function bbCode(): string
    {
        $bbCode = $this->url()->param("forum_bbcode");
        if (!is_string($bbCode)) {
            return "";
        }
        return $bbCode;
    }

    /** @return array{title:string,text:string} */
    public function commentPost(): array
    {
        return [
            "title" => $this->postString("forum_title"),
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
