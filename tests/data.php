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

use Forum\Infra\Repository;
use Forum\Value\Comment;

require_once "./classes/Infra/Repository.php";
require_once "./classes/Value/Comment.php";

if ($argc !== 4) {
    echo "usage: php $argv[0] <forum> <topic_count> <comment_count>\n";
    exit(1);
}

$forum = $argv[1];
$topicCount = (int) $argv[2];
$commentCount = (int) $argv[3];

$repository = new Repository("../../content/forum/");

$users = ["cmb", "frase", "lck", "olape"];
$message = "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.";

for ($i = 0; $i < $topicCount; $i++) {
    $tid = uniqid();
    $timestamps = array_map(function () {
        return mt_rand(strtotime("2022-01-01"), strtotime("2022-12-31"));
    }, range(0, $commentCount - 1));
    usort($timestamps, function (int $a, int $b) {
        return $a <=> $b;
    });
    for ($j = 0; $j < $commentCount; $j++) {
        $comment = new Comment(
            uniqid(),
            "Test $i",
            $users[mt_rand(0, count($users) - 1)],
            $timestamps[$j],
            $message
        );
        if (!$repository->save($forum, $tid, $comment)) {
            echo "Failed to generated requested data!\n";
            exit(1);
        }
        echo ".";
    }
}
