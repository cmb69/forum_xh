<?php

namespace Forum\Model;

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
