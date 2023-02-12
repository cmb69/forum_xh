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
use Fa\RequireCommand as FaRequireCommand;
use Forum\Infra\Authorizer;
use Forum\Infra\Contents;
use Forum\Infra\Request;
use Forum\Infra\Response;
use Forum\Infra\Url;
use Forum\Infra\View;

class ShowEditor
{
    /** @var array<string,string> */
    private $lang;

    /** @var string */
    private $pluginFolder;

    /** @var Contents */
    private $contents;

    /** @var CSRFProtection */
    private $csrfProtector;

    /** @var View */
    private $view;

    /** @var FaRequireCommand */
    private $faRequireCommand;

    /** @var Authorizer */
    private $authorizer;

    /**
     * @param array<string,string> $lang
     */
    public function __construct(
        array $lang,
        string $pluginFolder,
        Contents $contents,
        CSRFProtection $csrfProtector,
        View $view,
        FaRequireCommand $faRequireCommand,
        Authorizer $authorizer
    ) {
        $this->lang = $lang;
        $this->pluginFolder = $pluginFolder;
        $this->contents = $contents;
        $this->csrfProtector = $csrfProtector;
        $this->view = $view;
        $this->faRequireCommand = $faRequireCommand;
        $this->authorizer = $authorizer;
    }

    public function __invoke(string $forum, Request $request): Response
    {
        $tid = $this->contents->cleanId($request->get("forum_topic") ?? "");
        $cid = $this->contents->cleanId($request->get("forum_comment") ?? "");
        $output = $this->renderCommentForm($forum, $tid, $cid, $request);
        $response = new Response($output, null, $request->get("forum_ajax") !== null);
        $response->addScript("{$this->pluginFolder}forum");
        return $response;
    }

    private function renderCommentForm(string $forum, ?string $tid, ?string $cid, Request $request): string
    {
        if ($this->authorizer->isVisitor()) {
            return "";
        }
        $this->faRequireCommand->execute();

        $comment = '';
        if ($tid !== null && $cid !== null) {
            $topics = $this->contents->getTopic($forum, $tid);
            if ($this->authorizer->mayModify($topics[$cid])) {
                $comment = $topics[$cid]->comment();
            }
            //$newTopic = true; // FIXME: hack to force overview link to be shown
        }
        $emotions = ['smile', 'wink', 'happy', 'grin', 'tongue', 'surprised', 'unhappy'];
        $emoticons = [];
        foreach ($emotions as $emotion) {
            $emoticons[$emotion] = "{$this->pluginFolder}images/emoticon_$emotion.png";
        }
        $output = $this->view->render('form', [
            'newTopic' => $tid === null,
            'tid' => $tid !== null ? $tid : "",
            'cid' => $cid !== null ? $cid : "",
            'action' => $request->url()->replace(["forum_actn" => "post"])->relative(),
            'previewUrl' => $request->url()->replace(["forum_actn" => "preview"])->relative(),
            'backUrl' => $tid === null
                ? $request->url()->relative()
                : $request->url()->replace(["forum_topic" => $tid])->relative(),
            'headingKey' => $tid === null ? 'msg_new_topic' : (isset($cid) ? 'msg_edit_comment' : 'msg_add_comment'),
            'comment' => $comment,
            'csrfTokenInput' => $this->csrfProtector->tokenInput(),
            'i18n' => json_encode($this->jsTexts()),
            'emoticons' => $emoticons,
        ]);
        $this->csrfProtector->store();
        return $output;
    }

    /** @return array<string,string> */
    private function jsTexts()
    {
        $keys = ['title_missing', 'comment_missing', 'enter_url'];
        $texts = array();
        foreach ($keys as $key) {
            $texts[strtoupper($key)] = $this->lang['msg_' . $key];
        }
        return $texts;
    }
}
