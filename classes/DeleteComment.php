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
use Forum\Infra\Response;
use Forum\Infra\Url;

class DeleteComment
{
    /** @var Url */
    private $url;

    /** @var Contents */
    private $contents;

    /** @var CSRFProtection */
    private $csrfProtector;

    /** @var Authorizer */
    private $authorizer;

    public function __construct(
        Url $url,
        Contents $contents,
        CSRFProtection $csrfProtector,
        Authorizer $authorizer
    ) {
        $this->url = $url;
        $this->contents = $contents;
        $this->csrfProtector = $csrfProtector;
        $this->authorizer = $authorizer;
    }

    public function __invoke(string $forum): Response
    {
        $this->csrfProtector->check();
        $tid = $this->contents->cleanId($_POST['forum_topic']);
        $cid = $this->contents->cleanId($_POST['forum_comment']);
        $url = $tid && $cid && $this->contents->deleteComment($forum, $tid, $cid, $this->authorizer)
            ? $this->url->replace(["forum_topic" => $tid])
            : $this->url;
        if (isset($_GET['forum_ajax'])) {
            $url = $url->replace(['forum_ajax' => ""]);
        }
        return new Response("", $url->absolute());
    }
}
