<?php

namespace Forum\Model;

use PHPUnit\Framework\TestCase;

class ForumTest extends TestCase
{
    public function testSurvivesRoundtrip(): void
    {
        $contents = <<<'EOS'
            {
                "61a269fd37f60": {
                    "id": "61a269fd37f60",
                    "title": "Hello World",
                    "commentCount": 1,
                    "user": "cmb",
                    "time": 1638033917
                }
            }
            EOS;
        $this->assertSame($contents, Forum::fromString($contents, "")->toString());
    }

    public function testSurvivesMigration(): void
    {
        $contents = 'a:1:{s:13:"61a269fd37f60";a:4:{s:5:"title";s:11:"Hello World";s:8:"comments";i:1;s:4:"user"'
            . ';s:3:"cmb";s:4:"time";i:1638033917;}}';
        $expected = <<<'EOS'
            {
                "61a269fd37f60": {
                    "id": "61a269fd37f60",
                    "title": "Hello World",
                    "commentCount": 1,
                    "user": "cmb",
                    "time": 1638033917
                }
            }
            EOS;
        $this->assertSame($expected, Forum::fromString($contents, "")->toString());
    }
}
