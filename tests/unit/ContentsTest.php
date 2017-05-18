<?php

/**
 * Copyright 2013-2017 Christoph M. Becker
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

use PHPUnit_Framework_TestCase;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;

class ContentsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $forum;

    /**
     * @var object
     */
    private $contents;

    protected function setUp()
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

    public function testDeleteFirstOfTwoCommentsProperlyUpdatesTopic()
    {
        $tid = $this->contents->getId();
        $title = 'hallo';

        $cid1 = $this->contents->getId();
        $user1 = 'cmb';
        $comment1 = array(
            'user' => $user1, 'time' => 1, 'comment' => 'foo'
        );
        $this->contents->createComment($this->forum, $tid, $title, $cid1, $comment1);

        $cid2 = $this->contents->getId();
        $user2 = 'admin';
        $comment2 = array(
            'user' => $user2, 'time' => 2, 'comment' => 'bar'
        );
        $this->contents->createComment($this->forum, $tid, $title, $cid2, $comment2);

        $this->contents->deleteComment($this->forum, $tid, $cid1, $user1);

        $expected = array(
            'title' => $title,
            'comments' => 1,
            'user' => $user2,
            'time' => 2
        );
        $this->assertEquals($expected, $this->contents->getSortedTopics($this->forum)[$tid]);
    }
}
