<?php

namespace Forum;

use PHPUnit\Framework\TestCase;

class DicTest extends TestCase
{
    public function setUp(): void
    {
        global $pth, $cf, $plugin_cf, $plugin_tx;
        $pth = ["folder" => ["base" => "", "content" => "", "plugins" => ""]];
        $cf = ["mailform" => ["lf_only" => ""]];
        $plugin_cf = ["forum" => []];
        $plugin_tx = ["forum" => ["title_iframe" => ""]];
    }

    public function testMakesForum(): void
    {
        $this->assertInstanceOf(Forum::class, Dic::makeForum());
    }

    public function testMakesShowInfo(): void
    {
        $this->assertInstanceOf(ShowInfo::class, Dic::makeShowInfo());
    }
}
