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

use Forum\Infra\Response;
use Forum\Infra\SystemChecker;
use Forum\Infra\View;

class ShowInfo
{
    /** @var string */
    private $pluginsFolder;

    /** @var string */
    private $pluginFolder;

    /** @var string */
    private $contentFolder;

    /** @var array<string,string> */
    private $lang;

    /** @var SystemChecker */
    private $systemChecker;

    /** @var View */
    private $view;

    /** @param array<string,string> $lang */
    public function __construct(
        string $pluginsFolder,
        string $contentFolder,
        array $lang,
        SystemChecker $systemChecker,
        View $view
    ) {
        $this->pluginsFolder = $pluginsFolder;
        $this->pluginFolder = "{$pluginsFolder}forum/";
        $this->contentFolder = $contentFolder;
        $this->lang = $lang;
        $this->systemChecker = $systemChecker;
        $this->view = $view;
    }

    public function __invoke(): Response
    {
        $output = $this->view->render('info', [
            'version' => FORUM_VERSION,
            'checks' => $this->getChecks(),
        ]);
        return new Response($output);
    }

    /** @return list<array{state:string,label:string,stateLabel:string}> */
    public function getChecks(): array
    {
        return array(
            $this->checkPhpVersion('7.1.0'),
            $this->checkExtension('json'),
            $this->checkExtension('session'),
            $this->checkXhVersion('1.7.0'),
            $this->checkPlugin('fa'),
            $this->checkWritability("{$this->pluginFolder}css/"),
            $this->checkWritability("{$this->pluginFolder}config"),
            $this->checkWritability("{$this->pluginFolder}languages/"),
            $this->checkWritability($this->contentFolder)
        );
    }

    /** @return array{state:string,label:string,stateLabel:string} */
    private function checkPhpVersion(string $version): array
    {
        $state = $this->systemChecker->checkPhpVersion($version) ? 'success' : 'fail';
        $label = sprintf($this->lang['syscheck_phpversion'], $version);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /** @return array{state:string,label:string,stateLabel:string} */
    private function checkExtension(string $extension): array
    {
        $state = $this->systemChecker->checkExtension($extension) ? 'success' : 'fail';
        $label = sprintf($this->lang['syscheck_extension'], $extension);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /** @return array{state:string,label:string,stateLabel:string} */
    private function checkXhVersion(string $version): array
    {
        $state = $this->systemChecker->checkXhVersion($version) ? 'success' : 'fail';
        $label = sprintf($this->lang['syscheck_xhversion'], $version);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /** @return array{state:string,label:string,stateLabel:string} */
    private function checkPlugin(string $plugin): array
    {
        $state = $this->systemChecker->checkPlugin($this->pluginsFolder) ? 'success' : 'fail';
        $label = sprintf($this->lang['syscheck_plugin'], $plugin);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /** @return array{state:string,label:string,stateLabel:string} */
    private function checkWritability(string $folder): array
    {
        $state = $this->systemChecker->checkWritability($folder) ? 'success' : 'warning';
        $label = sprintf($this->lang['syscheck_writable'], $folder);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }
}
