<?php

/**
 * Copyright 2012-2021 Christoph M. Becker
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

use XH\CSRFProtection;

use Forum\Infra\Authorizer;
use Forum\Infra\Contents;
use Forum\Infra\DateFormatter;
use Forum\Infra\Mailer;
use Forum\Infra\Response;
use Forum\Infra\Url;
use Forum\Value\Comment;

class PostComment
{
    /** @var Url */
    private $url;

    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $lang;

    /** @var Contents */
    private $contents;

    /** @var CSRFProtection */
    private $csrfProtector;

    /** @var Mailer */
    private $mailer;

    /** @var DateFormatter */
    private $dateFormatter;

    /** @var Authorizer */
    private $authorizer;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $lang
     */
    public function __construct(
        Url $url,
        array $config,
        array $lang,
        Contents $contents,
        CSRFProtection $csrfProtector,
        Mailer $mailer,
        DateFormatter $dateFormatter,
        Authorizer $authorizer
    ) {
        $this->url = $url;
        $this->config = $config;
        $this->lang = $lang;
        $this->contents = $contents;
        $this->csrfProtector = $csrfProtector;
        $this->mailer = $mailer;
        $this->dateFormatter = $dateFormatter;
        $this->authorizer = $authorizer;
    }

    public function __invoke(string $forum): Response
    {
        $this->csrfProtector->check();
        $forumtopic = $_POST['forum_topic'] ?? null;
        if (!empty($_POST['forum_comment'])) {
            $tid = $this->postComment($forum, $forumtopic, $_POST['forum_comment']);
        } else {
            $tid = $this->postComment($forum, $forumtopic);
        }
        $url = $tid ? $this->url->replace(["forum_topic" => $tid]) : $this->url;
        if (isset($_GET['forum_ajax'])) {
            $url = $url->replace(['forum_ajax' => ""]);
        }
        return new Response("", $url->absolute());
    }

    /** @return string|false */
    private function postComment(string $forum, ?string $tid = null, ?string $cid = null)
    {
        if (!isset($tid) && empty($_POST['forum_title'])
            || $this->authorizer->isVisitor() || empty($_POST['forum_text'])
        ) {
            return false;
        }
        $tid = isset($tid)
            ? $this->contents->cleanId($tid)
            : $this->contents->getId();
        if ($tid === false) {
            return false;
        }

        $comment = new Comment($this->authorizer->username(), time(), $_POST['forum_text']);
        if (!isset($cid)) {
            $cid = $this->contents->getId();
            $title = $_POST['forum_title'] ?? null;
            $this->contents->createComment($forum, $tid, $title, $cid, $comment);
            $subject = $this->lang['mail_subject_new'];
        } else {
            $this->contents->updateComment($forum, $tid, $cid, $comment);
            $subject = $this->lang['mail_subject_edit'];
        }

        if (!$this->authorizer->isAdmin() && $this->config['mail_address']) {
            $url = $this->url->replace(["forum_topic" => $tid])->absolute();
            $date = $this->dateFormatter->format($comment->time());
            $attribution = sprintf($this->lang['mail_attribution'], $comment->user(), $date);
            $content = preg_replace('/\r\n|\r|\n/', "\n> ", $comment->comment());
            assert(is_string($content));
            $message = "$attribution\n\n> $content\n\n<$url>";
            $this->mailer->sendMail($subject, $message, $url);
        }

        return $tid;
    }
}
