<?php

/**
 * Copyright (c) Christoph M. Becker
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

use Forum\Model\Forum;
use Forum\Model\Topic;
use Plib\DocumentStore;
use Plib\Request;
use Plib\Response;
use Plib\SystemChecker;
use Plib\Url;
use Plib\View;

class ShowInfo
{
    /** @var string */
    private $pluginFolder;

    /** @var SystemChecker */
    private $systemChecker;

    /** @var DocumentStore */
    private $store;

    /** @var View */
    private $view;

    public function __construct(
        string $pluginFolder,
        SystemChecker $systemChecker,
        DocumentStore $store,
        View $view
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->systemChecker = $systemChecker;
        $this->store = $store;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        switch ($this->action($request)) {
            default:
                return $this->info($request);
            case "do_migrate":
                return $this->migrate($request);
        }
    }

    private function action(Request $request): string
    {
        $action = $request->get("forum_action");
        if (!is_string($action)) {
            return "";
        }
        if (!strncmp($action, "do_", strlen("do_"))) {
            return "";
        }
        if ($request->post("forum_do") !== null) {
            return "do_$action";
        }
        return $action;
    }

    private function info(Request $request): Response
    {
        return Response::create($this->renderInfo($request->url()));
    }

    private function migrate(Request $request): Response
    {
        $forum = $this->id($request->get("forum_forum"));
        if ($forum === null) {
            return Response::create($this->renderInfo($request->url(), [["error_id_missing"]]));
        }
        $result = $this->migrateForum($forum);
        if (!$result) {
            return Response::create($this->renderInfo($request->url(), [["error_migration"]]));
        }
        return Response::redirect($request->url()->without("forum_action")->without("forum_forum")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderInfo(Url $url, array $errors = []): string
    {
        return $this->view->render("info", [
            "version" => FORUM_VERSION,
            "checks" => $this->getChecks(),
            "forums" => $this->forumRecords($url->with("forum_action", "migrate")),
            "errors" => $errors,
        ]);
    }

    /** @return list<object{class:string,key:string,arg:string,statekey:string}> */
    private function getChecks(): array
    {
        return array(
            $this->checkPhpVersion("7.1.0"),
            $this->checkXhVersion("1.7.0"),
            $this->checkPlib("1.6"),
            $this->checkWritability($this->pluginFolder . "css/"),
            $this->checkWritability($this->pluginFolder . "config"),
            $this->checkWritability($this->pluginFolder . "languages/"),
            $this->checkWritability($this->store->folder())
        );
    }

    /** @return object{class:string,key:string,arg:string,statekey:string} */
    private function checkPhpVersion(string $version): object
    {
        $state = $this->systemChecker->checkVersion(PHP_VERSION, $version) ? "success" : "fail";
        return (object) [
            "class" => "xh_$state",
            "key" => "syscheck_phpversion",
            "arg" => $version,
            "statekey" => "syscheck_$state",
        ];
    }

    /** @return object{class:string,key:string,arg:string,statekey:string} */
    private function checkXhVersion(string $version): object
    {
        $state = $this->systemChecker->checkVersion(CMSIMPLE_XH_VERSION, "CMSimple_XH $version") ? "success" : "fail";
        return (object) [
            "class" => "xh_$state",
            "key" => "syscheck_xhversion",
            "arg" => $version,
            "statekey" => "syscheck_$state",
        ];
    }

    /** @return object{class:string,key:string,arg:string,statekey:string} */
    private function checkPlib(string $version): object
    {
        $state = $this->systemChecker->checkPlugin("plib", $version) ? "success" : "fail";
        return (object) [
            "class" => "xh_$state",
            "key" => "syscheck_plibversion",
            "arg" => $version,
            "statekey" => "syscheck_$state",
        ];
    }

    /** @return object{class:string,key:string,arg:string,statekey:string} */
    private function checkWritability(string $folder): object
    {
        $state = $this->systemChecker->checkWritability($folder) ? "success" : "warning";
        return (object) [
            "class" => "xh_$state",
            "key" => "syscheck_writable",
            "arg" => $folder,
            "statekey" => "syscheck_$state",
        ];
    }

    /** @return list<object{name:string,url:string}> */
    private function forumRecords(Url $url): array
    {
        return array_map(function (string $forumname) use ($url) {
            return (object) [
                "name" => dirname($forumname),
                "url" => $url->with("forum_forum", dirname($forumname))->relative(),
            ];
        }, $this->store->find('/topics\.dat$/'));
    }

    private function migrateForum(string $forumname): bool
    {
        $oldForum = $this->store->retrieve($forumname . "/topics.dat", Forum::class);
        assert($oldForum instanceof Forum);
        $newForum = Forum::update($forumname, $this->store);
        $newForum->copy($oldForum);
        foreach ($newForum->topics() as $baseTopic) {
            $tid = $baseTopic->id();
            $oldTopic = $this->store->retrieve($forumname . "/$tid.dat", Topic::class);
            assert($oldTopic instanceof Topic);
            $newTopic = $newForum->updateTopic($tid, $this->store);
            assert($newTopic instanceof Topic);
            $newTopic->copy($oldTopic, $baseTopic->title());
            $this->store->delete($forumname . "/$tid.dat");
        }
        if (!$this->store->commit()) {
            return false;
        }
        $this->store->delete($forumname . "/topics.dat");
        return true;
    }

    private function id(?string $id): ?string
    {
        if ($id === null) {
            return null;
        }
        return preg_match('/^[a-z0-9\-]+$/u', $id) ? $id : null;
    }
}
