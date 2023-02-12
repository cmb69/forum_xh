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

use Forum\Value\Comment;

class Authorizer
{
    public function isAdmin(): bool
    {
        return defined('XH_ADM') && XH_ADM;
    }

    public function isUser(): bool
    {
        return $this->get('Name') || $this->get('username');
    }

    public function isVisitor(): bool
    {
        return !($this->isAdmin() || $this->isUser());
    }

    public function username(): string
    {
        if ($this->get('Name')) {
            return $this->get('Name');
        }
        if ($this->get('username')) {
            return $this->get('username');
        }
        return "";
    }

    public function mayModify(Comment $comment): bool
    {
        return $this->isAdmin() || $comment->user() === $this->username();
    }

    private function get(string $key): ?string
    {
        XH_startSession();
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }
}
