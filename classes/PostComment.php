<?php

/**
 * Copyright 2012-2023 Christoph M. Becker
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
use Forum\Infra\Request;
use Forum\Infra\Response;
use Forum\Infra\Url;
use Forum\Value\Comment;

class PostComment
{
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
        array $config,
        array $lang,
        Contents $contents,
        CSRFProtection $csrfProtector,
        Mailer $mailer,
        DateFormatter $dateFormatter,
        Authorizer $authorizer
    ) {
        $this->config = $config;
        $this->lang = $lang;
        $this->contents = $contents;
        $this->csrfProtector = $csrfProtector;
        $this->mailer = $mailer;
        $this->dateFormatter = $dateFormatter;
        $this->authorizer = $authorizer;
    }

    public function __invoke(string $forum, Request $request): Response
    {
        $this->csrfProtector->check();
        $forumtopic = $request->post("forum_topic");
        if (!empty($request->post("forum_comment"))) {
            $tid = $this->postComment($forum, $forumtopic, $request->post("forum_comment"), $request);
        } else {
            $tid = $this->postComment($forum, $forumtopic, null, $request);
        }
        $url = $tid !== null ? $request->url()->replace(["forum_topic" => $tid]) : $request->url();
        if ($request->get("forum_ajax") !== null) {
            $url = $url->replace(['forum_ajax' => ""]);
        }
        return new Response("", $url->absolute());
    }

    private function postComment(string $forum, ?string $tid, ?string $cid, Request $request): ?string
    {
        if (!isset($tid) && empty($request->post("forum_title"))
            || $this->authorizer->isVisitor() || empty($request->post("forum_text"))
        ) {
            return null;
        }
        $tid = isset($tid)
            ? $this->contents->cleanId($tid)
            : $this->contents->getId();
        if ($tid === null) {
            return null;
        }

        $comment = new Comment($this->authorizer->username(), time(), $request->post("forum_text"));
        if (!isset($cid)) {
            $cid = $this->contents->getId();
            $title = $request->post("forum_title");
            $this->contents->createComment($forum, $tid, $title, $cid, $comment);
            $subject = $this->lang['mail_subject_new'];
        } else {
            $this->contents->updateComment($forum, $tid, $cid, $comment);
            $subject = $this->lang['mail_subject_edit'];
        }

        if (!$this->authorizer->isAdmin() && $this->config['mail_address']) {
            $url = $request->url()->replace(["forum_topic" => $tid])->absolute();
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
