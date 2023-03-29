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

use XH\CSRFProtection;

class FakeCsrfProtector extends CsrfProtector
{
    private $checked = false;
    private $stored = false;

    public function checked(): bool
    {
        return $this->checked;
    }

    public function stored(): bool
    {
        return $this->stored;
    }

    public function check()
    {
        parent::check();
        $this->checked = true;
    }

    public function store()
    {
        parent::store();
        $this->stored = true;
    }

    protected function csrfFProtection(): CSRFProtection
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new class() extends CSRFProtection {
                public function __construct() {}
                public function tokenInput()
                {
                    return "<input type=\"hidden\" name=\"xh_csrf_token\" value=\"e3c1b42a6098b48a39f9f54ddb3388f7\">";
                }
                public function check() {}
                public function store() {}
            };
        }
        return $instance;
    }
}
