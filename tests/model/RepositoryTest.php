<?php

namespace Forum\Model;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    public function setUp(): void
    {
        vfsStream::setup("root");
    }

    public function testCreatesFolder(): void
    {
        $sut = new Repository("vfs://root/forum/");
        $folder = $sut->folder();
        $this->assertEquals("vfs://root/forum/", $folder);
        $this->assertFileExists($folder);
    }

    public function testCreatesSubFolder(): void
    {
        $sut = new Repository("vfs://root/forum/");
        $folder = $sut->folder("test");
        $this->assertEquals("vfs://root/forum/test/", $folder);
        $this->assertFileExists($folder);
    }

    public function testFindsForumsToMigrate(): void
    {
        mkdir("vfs://root/forum/test", 0777, true);
        file_put_contents("vfs://root/forum/test/topics.dat", 'a:1:{s:13:"6425e91f94c00";a:4:{s:5:"title"'
            . ';s:11:"a new topic";s:8:"comments";i:1;s:4:"user";s:3:"cmb";s:4:"time";i:1680206111;}}');
        $sut = new Repository("vfs://root/forum/");
        $result = $sut->findForumsToMigrate();
        $this->assertEquals(["test"], $result);
    }

    public function testMigratesFromOldFormat(): void
    {
        mkdir("vfs://root/forum/test", 0777, true);
        file_put_contents("vfs://root/forum/test/topics.dat", 'a:1:{s:13:"6425e91f94c00";a:4:{s:5:"title"'
            . ';s:11:"a new topic";s:8:"comments";i:1;s:4:"user";s:3:"cmb";s:4:"time";i:1680206111;}}');
        file_put_contents("vfs://root/forum/test/6425e91f94c00.dat", 'a:2:{s:13:"63e90fce8abc4";a:3:{s:4:"user"'
            . ';s:3:"cmb";s:4:"time";i:1676218318;s:7:"comment";s:3:"neu";}s:13:"63e90fd3ac50d";a:3:{s:4:"user"'
            . ';s:3:"cmb";s:4:"time";i:1676218323;s:7:"comment";s:9:"antwort!e";}}');
        $sut = new Repository("vfs://root/forum/");
        $result = $sut->migrate("test");
        $this->assertTrue($result);
        $this->assertStringEqualsFile("vfs://root/forum/test/6425e91f94c00.txt", <<<'EOT'
        Id: 63e90fce8abc4
        Title: a new topic
        User: cmb
        Date: Sun, 12 Feb 2023 16:11:58 +0000

        neu
        %%
        Id: 63e90fd3ac50d
        Title: a new topic
        User: cmb
        Date: Sun, 12 Feb 2023 16:12:03 +0000

        antwort!e
        EOT);
    }

    private function contents(): string
    {
        return <<<EOT
        Id: 1
        User: cmb
        Date: Sat, 01 Apr 2023 12:22:00 +0000

        My first comment!
        %%
        Id: 2
        User: other
        Date: Sat, 01 Apr 2023 12:30:52 +0000

        A
        multiline
        comment.
        EOT;
    }

    private function comments(): array
    {
        return [
            new Comment("1", null, "cmb", 1680351720, "My first comment!"),
            new Comment("2", null, "other", 1680352252, "A\nmultiline\ncomment."),
        ];
    }
}
