<?php

/**
 * Copyright 2017-2023 Christoph M. Becker
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

use Forum\Value\Comment;

class Mailer
{
    /** @var array<string,string> */
    private $config;

    /** @var DateFormatter */
    private $dateFormatter;

    /** @var View */
    private $view;

    /** @param array<string,string> $config */
    public function __construct(array $config, DateFormatter $dateFormatter, View $view)
    {
        $this->config = $config;
        $this->dateFormatter = $dateFormatter;
        $this->view = $view;
    }

    public function sendMail(string $subject, Comment $comment, ?string $baseUrl = null): bool
    {
        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            "From: {$this->config['mail_address']}"
        );
        if (isset($baseUrl)) {
            $headers[] = "Content-Base: $baseUrl";
        }
        $sep = $this->config['mail_fix_headers'] ? "\n" : "\r\n";
        return $this->mail(
            $this->config['mail_address'],
            '=?UTF-8?B?' . base64_encode($subject) . '?=',
            (string) preg_replace('/\r\n|\n|\r/', $sep, $this->renderMessage($comment, $baseUrl)),
            implode($sep, $headers)
        );
    }

    private function renderMessage(Comment $comment, string $url): string
    {
        $date = $this->dateFormatter->format($comment->time());
        $attribution = $this->view->plain("mail_attribution", $comment->user(), $date);
        $content = preg_replace('/\r\n|\r|\n/', "\n> ", $comment->comment());
        assert(is_string($content));
        return "$attribution\n\n> $content\n\n<$url>";
    }

    /** @codeCoverageIgnore */
    protected function mail(string $to, string $subject, string $message, string $headers): bool
    {
        return mail($to, $subject, $message, $headers);
    }
}
