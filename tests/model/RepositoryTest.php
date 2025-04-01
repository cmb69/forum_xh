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

    public function testFindsTopics(): void
    {
        mkdir("vfs://root/forum/test", 0777, true);
        file_put_contents("vfs://root/forum/test/DHQPWSV5E8G78TBMDHJG.txt", $this->contents());
        file_put_contents(
            "vfs://root/forum/test/AHM6A83HENMP6TS0C9S6YXVE41K6YY0.txt",
            <<<EOT
            Id: 17
            User: foxy
            Date: Fri, 30 Mar 2023 12:30:52 +0000
    
            The fox was here!
            EOT
        );
        $sut = new Repository("vfs://root/forum/");
        $result = $sut->findForum("test");
        $this->assertEquals(new Forum([
            new Topic("DHQPWSV5E8G78TBMDHJG", "", 2, "other", 1680352252),
            new Topic("AHM6A83HENMP6TS0C9S6YXVE41K6YY0", "", 1, "foxy", 1680265852),
        ]), $result);
    }

    public function testFindsTopicsFromCache(): void
    {
        mkdir("vfs://root/forum/test", 0777, true);
        file_put_contents("vfs://root/forum/test/DHQPWSV5E8G78TBMDHJG.txt", $this->contents());
        file_put_contents(
            "vfs://root/forum/test/AHM6A83HENMP6TS0C9S6YXVE41K6YY0.txt",
            <<<EOT
            Id: 17
            User: foxy
            Date: Fri, 30 Mar 2023 12:30:52 +0000
    
            The fox was here!
            EOT
        );
        $contents = serialize([
            new Topic("DHQPWSV5E8G78TBMDHJG", "", 2, "other", 1680352252),
            new Topic("AHM6A83HENMP6TS0C9S6YXVE41K6YY0", "", 1, "foxy", 1680265852),
        ]);
        file_put_contents("vfs://root/forum/test/topics.dat", $contents);
        $sut = new Repository("vfs://root/forum/");
        $result = $sut->findForum("test");
        $this->assertEquals(new Forum([
            new Topic("DHQPWSV5E8G78TBMDHJG", "", 2, "other", 1680352252),
            new Topic("AHM6A83HENMP6TS0C9S6YXVE41K6YY0", "", 1, "foxy", 1680265852),
        ]), $result);
    }

    public function testDoesNotFindTopicsFromCacheIfOneFileIsStale(): void
    {
        mkdir("vfs://root/forum/test", 0777, true);
        file_put_contents("vfs://root/forum/test/DHQPWSV5E8G78TBMDHJG.txt", $this->contents());
        file_put_contents(
            "vfs://root/forum/test/AHM6A83HENMP6TS0C9S6YXVE41K6YY0.txt",
            <<<EOT
            Id: 17
            User: foxy
            Date: Fri, 30 Mar 2023 12:30:52 +0000
    
            The fox was here!
            EOT
        );
        $contents = serialize([
            new Topic("DHQPWSV5E8G78TBMDHJG", "", 2, "other", 1680352252),
            new Topic("AHM6A83HENMP6TS0C9S6YXVE41K6YY0", "", 1, "foxy", 1680265852),
        ]);
        file_put_contents("vfs://root/forum/test/topics.dat", $contents);
        touch(
            "vfs://root/forum/test/AHM6A83HENMP6TS0C9S6YXVE41K6YY0.txt",
            filemtime("vfs://root/forum/test/topics.dat") + 2
        );
        $sut = new Repository("vfs://root/forum/");
        $result = $sut->findForum("test");
        $this->assertEquals(new Forum([
            new Topic("DHQPWSV5E8G78TBMDHJG", "", 2, "other", 1680352252),
            new Topic("AHM6A83HENMP6TS0C9S6YXVE41K6YY0", "", 1, "foxy", 1680265852),
        ]), $result);
    }

    public function testReportsIfTopicDoesNotExist(): void
    {
        $sut = new Repository("vfs://root/forum/");
        $result = $sut->hasTopic("test", "DHQPWSV5E8G78TBMDHJG");
        $this->assertFalse($result);
    }

    public function testFindsTopic(): void
    {
        mkdir("vfs://root/forum/test", 0777, true);
        file_put_contents("vfs://root/forum/test/DHQPWSV5E8G78TBMDHJG.txt", $this->contents());
        $sut = new Repository("vfs://root/forum/");
        [$topic, ] = $sut->findTopic("test", "DHQPWSV5E8G78TBMDHJG");
        $this->assertEquals(new Topic("DHQPWSV5E8G78TBMDHJG", "", 2, "other", 1680352252), $topic);
    }

    public function testFindsTopicFromCache(): void
    {
        mkdir("vfs://root/forum/test", 0777, true);
        file_put_contents("vfs://root/forum/test/DHQPWSV5E8G78TBMDHJG.txt", $this->contents());
        $contents = serialize([new Topic("DHQPWSV5E8G78TBMDHJG", "", 2, "other", 1680352252), $this->comments()]);
        file_put_contents("vfs://root/forum/test/DHQPWSV5E8G78TBMDHJG.dat", $contents);
        $sut = new Repository("vfs://root/forum/");
        [$topic, ] = $sut->findTopic("test", "DHQPWSV5E8G78TBMDHJG");
        $this->assertEquals(new Topic("DHQPWSV5E8G78TBMDHJG", "", 2, "other", 1680352252), $topic);
    }

    public function testFindsNullIfTopicDoesNotExist(): void
    {
        $sut = new Repository("vfs://root/forum/");
        [$topic, ] = $sut->findTopic("test", "DHQPWSV5E8G78TBMDHJG");
        $this->assertNull($topic);
    }

    public function testFindsCommentsOfEmptyTopic(): void
    {
        $sut = new Repository("vfs://root/forum/");
        [, $comments] = $sut->findTopic("test", "DHQPWSV5E8G78TBMDHJG");
        $this->assertEquals([], $comments);
    }

    public function testFindsCommentsOfNonEmptyTopic(): void
    {
        mkdir("vfs://root/forum/test", 0777, true);
        file_put_contents("vfs://root/forum/test/DHQPWSV5E8G78TBMDHJG.txt", $this->contents());
        $sut = new Repository("vfs://root/forum/");
        [, $comments] = $sut->findTopic("test", "DHQPWSV5E8G78TBMDHJG");
        $this->assertEquals($this->comments(), $comments);
    }

    public function testFindsComment(): void
    {
        mkdir("vfs://root/forum/test", 0777, true);
        file_put_contents("vfs://root/forum/test/DHQPWSV5E8G78TBMDHJG.txt", $this->contents());
        $sut = new Repository("vfs://root/forum/");
        $result = $sut->findComment("test", "DHQPWSV5E8G78TBMDHJG", "2");
        $this->assertEquals($this->comments()[1], $result);
    }

    public function testDoesNotFindsCommentInNonExitentTopic(): void
    {
        $sut = new Repository("vfs://root/forum/");
        $result = $sut->findComment("test", "DHQPWSV5E8G78TBMDHJG", "1");
        $this->assertNull($result);
    }

    public function testSavesComment(): void
    {
        $sut = new Repository("vfs://root/forum/");
        $comments = $this->comments();
        $result = $sut->save("test", "DHQPWSV5E8G78TBMDHJG", $comments[0]);
        $this->assertTrue($result);
        $result = $sut->save("test", "DHQPWSV5E8G78TBMDHJG", $comments[1]);
        $this->assertTrue($result);
        $this->assertStringEqualsFile("vfs://root/forum/test/DHQPWSV5E8G78TBMDHJG.txt", $this->contents());
    }

    public function testUpdatesComment(): void
    {
        mkdir("vfs://root/forum/test", 0777, true);
        file_put_contents("vfs://root/forum/test/DHQPWSV5E8G78TBMDHJG.txt", $this->contents());
        $sut = new Repository("vfs://root/forum/");
        $comment = $this->comments()[0]->with("Topic title", "Updated comment.");
        $result = $sut->save("test", "DHQPWSV5E8G78TBMDHJG", $comment);
        $this->assertTrue($result);
        $this->assertStringEqualsFile("vfs://root/forum/test/DHQPWSV5E8G78TBMDHJG.txt", <<<EOT
        Id: 1
        Title: Topic title
        User: cmb
        Date: Sat, 01 Apr 2023 12:22:00 +0000

        Updated comment.
        %%
        Id: 2
        User: other
        Date: Sat, 01 Apr 2023 12:30:52 +0000

        A
        multiline
        comment.
        EOT);
    }

    public function testReportsIfCommentCannotBeSaved(): void
    {
        mkdir("vfs://root/forum/test", 0777, true);
        chmod("vfs://root/forum/test", 0444);
        $sut = new Repository("vfs://root/forum/");
        $result = $sut->save("test", "DHQPWSV5E8G78TBMDHJG", $this->comments()[0]);
        $this->assertFalse($result);
    }

    public function testDeletesComment(): void
    {
        mkdir("vfs://root/forum/test", 0777, true);
        file_put_contents("vfs://root/forum/test/DHQPWSV5E8G78TBMDHJG.txt", $this->contents());
        $sut = new Repository("vfs://root/forum/");
        $result = $sut->delete("test", "DHQPWSV5E8G78TBMDHJG", "1");
        $this->assertTrue($result);
        $this->assertStringEqualsFile("vfs://root/forum/test/DHQPWSV5E8G78TBMDHJG.txt", <<<EOT
        Id: 2
        User: other
        Date: Sat, 01 Apr 2023 12:30:52 +0000

        A
        multiline
        comment.
        EOT);
    }

    public function testReportsIfCommentCannotBeDeleted(): void
    {
        mkdir("vfs://root/forum/test", 0777, true);
        chmod("vfs://root/forum/test", 0444);
        $sut = new Repository("vfs://root/forum/");
        $result = $sut->delete("test", "DHQPWSV5E8G78TBMDHJG", "1");
        $this->assertFalse($result);
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
