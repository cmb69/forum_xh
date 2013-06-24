<?php

require_once '../classes/Contents.php';

class ContentsTest extends PHPUnit_Framework_TestCase
{
    protected $forum;

    protected $contents;

    public function setUp()
    {
        $this->forum = 'test';
        $this->contents = new Forum_Contents('.');
        exec('rm -f-r ' . $this->forum);

    }

    public function testCleanId()
    {
        $id = $this->contents->getId();
        $this->assertTrue((bool) $this->contents->cleanId($id));
    }

    protected function getComment($forum, $tid, $cid)
    {
        $topic = $this->contents->getTopic($forum, $tid);
        return isset($topic[$cid]) ? $topic[$cid] : array();
    }

    public function testCreateAndDeleteComment()
    {
        $tid = $this->contents->getId();
        $title = 'hallo';
        $cid = $this->contents->getId();
        $user = 'cmb';
        $comment = array(
            'user' => $user, 'time' => time(), 'comment' => 'foo bar baz'
        );
        $this->contents->createComment($this->forum, $tid, $title, $cid, $comment);

        $actual = $this->getComment($this->forum, $tid, $cid);
        $this->assertEquals($comment, $actual);

        $this->contents->deleteComment($this->forum, $tid, $cid, $user);

        $actual = $this->getComment($this->forum, $tid, $cid);
        $this->assertEmpty($actual);
    }
}

?>
