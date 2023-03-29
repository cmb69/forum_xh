<?php

/**
 * Copyright 2023 Christoph M. Becker
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

use Exception;
use XH\CSRFProtection;

class CsrfProtector
{
    /** @return array{name:string,value:string} */
    public function token(): array
    {
        $html = $this->csrfFProtection()->tokenInput();
        if (preg_match('/name="([^"]+)" value="([0-9a-f]+)"/', $html, $matches)) {
            return ["name" => $matches[1], "value" => $matches[2]];
        }
        // @codeCoverageIgnoreStart
        throw new Exception("CSRF protection is broken");
        // @codeCoverageIgnoreEnd
    }

    /** @return void */
    public function check()
    {
        $this->csrfFProtection()->check();
    }

    /** @return void */
    public function store()
    {
        $this->csrfFProtection()->store();
    }

    /** @codeCoverageIgnore */
    protected function csrfFProtection(): CSRFProtection
    {
        global $_XH_csrfProtection;
        return $_XH_csrfProtection ?? new CSRFProtection("forum_token");
    }
}
