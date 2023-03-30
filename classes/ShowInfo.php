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

use Forum\Infra\SystemChecker;
use Forum\Infra\View;
use Forum\Value\Response;

class ShowInfo
{
    /** @var string */
    private $pluginFolder;

    /** @var string */
    private $contentFolder;

    /** @var SystemChecker */
    private $systemChecker;

    /** @var View */
    private $view;

    public function __construct(
        string $pluginFolder,
        string $contentFolder,
        SystemChecker $systemChecker,
        View $view
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->contentFolder = $contentFolder;
        $this->systemChecker = $systemChecker;
        $this->view = $view;
    }

    public function __invoke(): Response
    {
        $output = $this->view->render("info", [
            "version" => FORUM_VERSION,
            "checks" => $this->getChecks(),
        ]);
        return Response::create($output);
    }

    /** @return list<array{class:string,key:string,arg:string,statekey:string}> */
    public function getChecks(): array
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
            $this->checkWritability($this->contentFolder)
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
        $state = $this->systemChecker->checkPlugin($this->pluginFolder) ? "success" : "fail";
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
}
