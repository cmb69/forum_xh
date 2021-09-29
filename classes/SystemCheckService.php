<?php

/**
 * Copyright 2017-2021 Christoph M. Becker
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

use const CMSIMPLE_XH_VERSION;

class SystemCheckService
{
    /**
     * @var string
     */
    private $pluginsFolder;

    /**
     * @var string
     */
    private $pluginFolder;

    /**
     * @var string
     */
    private $contentFolder;

    /**
     * @var array<string,string>
     */
    private $lang;

    /**
     * @param string $pluginsFolder
     * @param string $contentFolder
     * @param array<string,string> $lang
     */
    public function __construct($pluginsFolder, $contentFolder, array $lang)
    {
        $this->pluginsFolder = $pluginsFolder;
        $this->pluginFolder = "{$pluginsFolder}forum/";
        $this->contentFolder = $contentFolder;
        $this->lang = $lang;
    }

    /**
     * @return array<int,array>string,mixed>>
     */
    public function getChecks()
    {
        return array(
            $this->checkPhpVersion('5.6.0'),
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

    /**
     * @param string $version
     * @return array<string,mixed>
     */
    private function checkPhpVersion($version)
    {
        $state = version_compare(PHP_VERSION, $version, 'ge') ? 'success' : 'fail';
        $label = sprintf($this->lang['syscheck_phpversion'], $version);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /**
     * @param string $extension
     * @param bool $isMandatory
     * @return array<string,mixed>
     */
    private function checkExtension($extension, $isMandatory = true)
    {
        $state = extension_loaded($extension) ? 'success' : ($isMandatory ? 'fail' : 'warning');
        $label = sprintf($this->lang['syscheck_extension'], $extension);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /**
     * @param string $version
     * @return array<string,mixed>
     */
    private function checkXhVersion($version)
    {
        $state = version_compare(CMSIMPLE_XH_VERSION, "CMSimple_XH $version", 'ge') ? 'success' : 'fail';
        $label = sprintf($this->lang['syscheck_xhversion'], $version);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /**
     * @param string $plugin
     * @return array<string,mixed>
     */
    private function checkPlugin($plugin)
    {
        $state = is_dir($this->pluginsFolder) ? 'success' : 'fail';
        $label = sprintf($this->lang['syscheck_plugin'], $plugin);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /**
     * @param string $folder
     * @return array<string,mixed>
     */
    private function checkWritability($folder)
    {
        $state = is_writable($folder) ? 'success' : 'warning';
        $label = sprintf($this->lang['syscheck_writable'], $folder);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }
}
