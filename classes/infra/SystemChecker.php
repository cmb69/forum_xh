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

class SystemChecker
{
    public function checkPhpVersion(string $version): bool
    {
        return version_compare(PHP_VERSION, $version, 'ge');
    }

    public function checkExtension(string $name): bool
    {
        return extension_loaded($name);
    }

    public function checkXhVersion(string $version): bool
    {
        return version_compare(CMSIMPLE_XH_VERSION, "CMSimple_XH $version", 'ge');
    }

    public function checkPlugin(string $folder): bool
    {
        return is_dir($folder);
    }

    public function checkWritability(string $folder): bool
    {
        return is_writable($folder);
    }
}
