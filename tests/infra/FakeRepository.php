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

class FakeRepository extends Repository
{
    private $options = [];
    private $lastMigration;

    public function options(array $options)
    {
        $this->options = $options + $this->options;
    }

    public function lastMigration(): array
    {
        return $this->lastMigration;
    }

    public function save(string $forum, string $tid, Comment $comment): bool
    {
        if (isset($this->options["save"]) && $this->options["save"] === false) {
            return false;
        }
        return parent::save($forum, $tid, $comment);
    }

    public function delete(string $forum, string $tid, string $cid): bool
    {
        if (isset($this->options["delete"]) && $this->options["delete"] === false) {
            return false;
        }
        return parent::delete($forum, $tid, $cid);
    }

    public function findForumsToMigrate(): array
    {
        return $this->options["findForumsToMigrate"] ?? [];
    }

    public function migrate(string $forum): bool
    {
        if (isset($this->options["migrate"]) && $this->options["migrate"] === false) {
            return false;
        }
        $this->lastMigration = func_get_args();
        return true;
    }
}
