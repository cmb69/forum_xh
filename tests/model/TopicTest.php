<?php

namespace Forum\Model;

use PHPUnit\Framework\TestCase;

class TopicTest extends TestCase
{
    public function testSurvivesRoundtrip(): void
    {
        $contents = <<<'EOS'
            Id: 9BL62402HCK782E6OII2NEIC
            Title: Topic Title
            User: cmb
            Date: Thu, 03 Apr 2025 13:28:47 +0000

            A message
            %%
            Id: 8BL62402HCK782E6OII2NEIC
            Title: 
            User: cmb
            Date: Thu, 03 Apr 2025 13:30:47 +0000

            A reply
            EOS;
        $this->assertSame($contents, Topic::fromString($contents, "")->toString());
    }

    public function testSurvivesMigration(): void
    {
        $contents = 'a:1:{s:13:"61a269fd38173";a:3:{s:4:"user";s:3:"cmb";s:4:"time";i:1638033917'
            . ';s:7:"comment";s:17:"Ich bin neu hier!";}}';
        $expected = <<<'EOS'
            Id: 61a269fd38173
            User: cmb
            Date: Sat, 27 Nov 2021 17:25:17 +0000

            Ich bin neu hier!
            EOS;
        $this->assertSame($expected, Topic::fromString($contents, "")->toString());
    }
}
