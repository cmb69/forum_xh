<?php

/**
 * Copyright (c) Christoph M. Becker
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

namespace Forum\Model;

class Repository
{
    /** @var string */
    private $folder;

    public function __construct(string $folder)
    {
        $this->folder = $folder;
    }

    public function folder(?string $forumname = null): string
    {
        $filename = $this->folder;
        if ($forumname !== null) {
            $filename .= "$forumname/";
        }
        if (!file_exists($filename)) {
            if (mkdir($filename, 0777, true)) {
                chmod($filename, 0777);
            }
        }
        return $filename;
    }

    private function file(string $forumname, string $tid): string
    {
        return $this->folder($forumname) . "$tid.txt";
    }

    /** @return list<string> */
    public function findForumsToMigrate(): array
    {
        $forums = [];
        if (($dir = opendir($this->folder()))) {
            while (($entry = readdir($dir)) !== false) {
                if ($entry[0] !== "." && file_exists($this->folder($entry) . "/topics.dat")) {
                    $contents = file_get_contents($this->folder($entry) . "/topics.dat");
                    if ($contents === false) {
                        continue;
                    }
                    $data = unserialize($contents);
                    if (!is_array($data) || !is_array(current($data))) {
                        continue;
                    }
                    $forums[] = $entry;
                }
            }
            closedir($dir);
        }
        return $forums;
    }

    public function migrate(string $forumname): bool
    {
        $contents = file_get_contents($this->folder($forumname) . "topics.dat");
        if ($contents === false) {
            return false;
        }
        $data = unserialize($contents);
        if (!is_array($data)) {
            return false;
        }
        foreach ($data as $tid => $record) {
            if (!$this->migrateTopic($forumname, $tid, $record["title"])) {
                return false;
            }
        }
        $result = unlink($this->folder($forumname) . "topics.dat");
        foreach (array_keys($data) as $tid) {
            unlink($this->folder($forumname) . "$tid.dat");
        }
        return $result;
    }

    private function migrateTopic(string $forumname, string $tid, string $title): bool
    {
        $contents = file_get_contents($this->folder($forumname) . "$tid.dat");
        if ($contents === false) {
            return false;
        }
        $data = unserialize($contents);
        if (!is_array($data)) {
            return false;
        }
        $comments = array_map(function ($id, $record) use ($title) {
            return new Comment($id, $title, $record["user"], $record["time"], $record["comment"]);
        }, array_keys($data), array_values($data));
        $stream = @fopen($this->file($forumname, $tid), "c+");
        if ($stream === false) {
            return false;
        }
        $result = $this->writeComments($stream, $comments);
        fclose($stream);
        return $result !== false;
    }

    /**
     * @param resource $stream
     * @param list<Comment> $comments
     * @return int<0,max>|false
     */
    private function writeComments($stream, array $comments)
    {
        $records = [];
        foreach ($comments as $comment) {
            $records[] = "Id: " . $comment->id() . "\n"
                . ($comment->title() !== null ? "Title: " . $comment->title() . "\n" : "")
                . "User: " . $comment->user() . "\n"
                . "Date: " . date("r", $comment->time()) . "\n"
                . "\n"
                . $comment->message();
        }
        return fwrite($stream, implode("\n%%\n", $records));
    }
}
