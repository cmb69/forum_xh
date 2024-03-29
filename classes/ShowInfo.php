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

use Forum\Infra\Repository;
use Forum\Infra\Request;
use Forum\Infra\SystemChecker;
use Forum\Infra\View;
use Forum\Value\Response;
use Forum\Value\Url;

class ShowInfo
{
    /** @var string */
    private $pluginFolder;

    /** @var SystemChecker */
    private $systemChecker;

    /** @var Repository */
    private $repository;

    /** @var View */
    private $view;

    public function __construct(
        string $pluginFolder,
        SystemChecker $systemChecker,
        Repository $repository,
        View $view
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->systemChecker = $systemChecker;
        $this->repository = $repository;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        switch ($request->action()) {
            default:
                return $this->info($request);
            case "do_migrate":
                return $this->migrate($request);
        }
    }

    private function info(Request $request): Response
    {
        return Response::create($this->renderInfo($request->url()));
    }

    private function migrate(Request $request): Response
    {
        $forum = $request->forum();
        if ($forum === null) {
            return Response::create($this->renderInfo($request->url(), [["error_id_missing"]]));
        }
        $result = $this->repository->migrate($forum);
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

    /** @return list<array{class:string,key:string,arg:string,statekey:string}> */
    private function getChecks(): array
    {
        return array(
            $this->checkPhpVersion("7.1.0"),
            $this->checkExtension("json"),
            $this->checkExtension("session"),
            $this->checkXhVersion("1.7.0"),
            $this->checkPlugin("fa"),
            $this->checkWritability($this->pluginFolder . "css/"),
            $this->checkWritability($this->pluginFolder . "config"),
            $this->checkWritability($this->pluginFolder . "languages/"),
            $this->checkWritability($this->repository->folder())
        );
    }

    /** @return array{class:string,key:string,arg:string,statekey:string} */
    private function checkPhpVersion(string $version): array
    {
        $state = $this->systemChecker->checkPhpVersion($version) ? "success" : "fail";
        return [
            "class" => "xh_$state",
            "key" => "syscheck_phpversion",
            "arg" => $version,
            "statekey" => "syscheck_$state",
        ];
    }

    /** @return array{class:string,key:string,arg:string,statekey:string} */
    private function checkExtension(string $extension): array
    {
        $state = $this->systemChecker->checkExtension($extension) ? "success" : "fail";
        return [
            "class" => "xh_$state",
            "key" => "syscheck_extension",
            "arg" => $extension,
            "statekey" => "syscheck_$state",
        ];
    }

    /** @return array{class:string,key:string,arg:string,statekey:string} */
    private function checkXhVersion(string $version): array
    {
        $state = $this->systemChecker->checkXhVersion($version) ? "success" : "fail";
        return [
            "class" => "xh_$state",
            "key" => "syscheck_xhversion",
            "arg" => $version,
            "statekey" => "syscheck_$state",
        ];
    }

    /** @return array{class:string,key:string,arg:string,statekey:string} */
    private function checkPlugin(string $plugin): array
    {
        $folder = dirname($this->pluginFolder) . "/$plugin";
        $state = $this->systemChecker->checkPlugin($folder) ? "success" : "fail";
        return [
            "class" => "xh_$state",
            "key" => "syscheck_plugin",
            "arg" => $plugin,
            "statekey" => "syscheck_$state",
        ];
    }

    /** @return array{class:string,key:string,arg:string,statekey:string} */
    private function checkWritability(string $folder): array
    {
        $state = $this->systemChecker->checkWritability($folder) ? "success" : "warning";
        return [
            "class" => "xh_$state",
            "key" => "syscheck_writable",
            "arg" => $folder,
            "statekey" => "syscheck_$state",
        ];
    }

    /** @return list<array{name:string,url:string}> */
    private function forumRecords(Url $url): array
    {
        return array_map(function (string $forum) use ($url) {
            return [
                "name" => $forum,
                "url" => $url->with("forum_forum", $forum)->relative(),
            ];
        }, $this->repository->findForumsToMigrate());
    }
}
