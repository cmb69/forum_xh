<?php

/**
 * Copyright 2013-2021 Christoph M. Becker
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

namespace Forum;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;
use Forum\Value\Comment;
use Forum\Value\Topic;

class ContentsTest extends TestCase
{
    /**
     * @var string
     */
    private $forum;

    /**
     * @var object
     */
    private $contents;

    protected function setUp(): void
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('test'));
        $this->forum = 'test';
        $this->contents = new Contents(vfsStream::url('test'));
    }

    public function testCleanId()
    {
        $id = $this->contents->getId();
        $this->assertTrue((bool) $this->contents->cleanId($id));
    }

    /**
     * @param string $forum
     * @param string $tid
     * @param string $cid
     * @return array
     */
    private function getComment($forum, $tid, $cid)
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
        $comment = new Comment($user, time(), 'foo bar baz');
        $this->contents->createComment($this->forum, $tid, $title, $cid, $comment);

        $actual = $this->getComment($this->forum, $tid, $cid);
        $this->assertEquals($comment, $actual);

        $this->contents->deleteComment($this->forum, $tid, $cid, $user);

        $actual = $this->getComment($this->forum, $tid, $cid);
        $this->assertEmpty($actual);
    }

    public function testDeleteFirstOfTwoCommentsProperlyUpdatesTopic()
    {
        $tid = $this->contents->getId();
        $title = 'hallo';

        $cid1 = $this->contents->getId();
        $user1 = 'cmb';
        $comment1 = new Comment($user1, 1, 'foo');
        $this->contents->createComment($this->forum, $tid, $title, $cid1, $comment1);

        $cid2 = $this->contents->getId();
        $user2 = 'admin';
        $comment2 = new Comment($user2, 2, 'bar');
        $this->contents->createComment($this->forum, $tid, $title, $cid2, $comment2);

        $this->contents->deleteComment($this->forum, $tid, $cid1, $user1);

        $expected = new Topic($title, 1, $user2, 2);
        $this->assertEquals($expected, $this->contents->getSortedTopics($this->forum)[$tid]);
    }
}
