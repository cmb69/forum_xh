<?php

use Forum\Model\Forum;
use Plib\DocumentStore;

require_once "../plib/classes/Document.php";
require_once "../plib/classes/DocumentStore.php";
require_once "./classes/model/Forum.php";
require_once "./classes/model/BaseTopic.php";
require_once "./classes/model/Topic.php";
require_once "./classes/model/Comment.php";

if ($argc !== 4) {
    echo "usage: php $argv[0] <forum> <topic_count> <comment_count>\n";
    exit(1);
}

$forumname = $argv[1];
$topicCount = (int) $argv[2];
$commentCount = (int) $argv[3];

$store = new DocumentStore("../../content/forum/");
$forum = Forum::update($forumname, $store);

$users = ["cmb", "frase", "lck", "olape"];
$message = <<<'EOS'
Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore
et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.
Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet,
consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat,
sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren,
no sea takimata sanctus est Lorem ipsum dolor sit amet.
EOS;
$message = str_replace("\n", " ", $message);

for ($i = 0; $i < $topicCount; $i++) {
    $tid = uniqid();
    $topic = $forum->openTopic($tid, $store);
    $timestamps = array_map(function () {
        return mt_rand(strtotime("2022-01-01"), strtotime("2022-12-31"));
    }, range(0, $commentCount - 1));
    usort($timestamps, function (int $a, int $b) {
        return $a <=> $b;
    });
    for ($j = 0; $j < $commentCount; $j++) {
        $comment = $topic->addComment(
            uniqid(),
            "Test $i",
            $users[mt_rand(0, count($users) - 1)],
            $timestamps[$j],
            $message
        );
        echo ".";
    }
}
if (!$store->commit()) {
    echo "Failed to generated requested data!\n";
    exit(1);
}
