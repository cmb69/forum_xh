<?php

/**
 * Testing the Contents.
 *
 * PHP version 5
 *
 * @category  Testing
 * @package   Forum
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2013-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Forum_XH
 */

require_once './vendor/autoload.php';
require_once './classes/Contents.php';

use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;

/**
 * Testing the contents.
 *
 * @category Testing
 * @package  Forum
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Forum_XH
 */
class ContentsTest extends PHPUnit_Framework_TestCase
{
    /**
     * The forum name.
     *
     * @var string
     */
    protected $forum;

    /**
     * The test subject.
     *
     * @var object
     */
    protected $contents;

    /**
     * Sets up the test fixture.
     *
     * @return void
     */
    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('test'));
        $this->forum = 'test';
        $this->contents = new Forum_Contents(vfsStream::url('test'));
    }

    /**
     * Tests cleanID().
     *
     * @return void
     */
    public function testCleanId()
    {
        $id = $this->contents->getId();
        $this->assertTrue((bool) $this->contents->cleanId($id));
    }

    /**
     * Gets a comment.
     *
     * @param string $forum A forum name.
     * @param string $tid   A topic ID.
     * @param string $cid   A comment ID.
     *
     * @return array
     */
    protected function getComment($forum, $tid, $cid)
    {
        $topic = $this->contents->getTopic($forum, $tid);
        return isset($topic[$cid]) ? $topic[$cid] : array();
    }

    /**
     * Tests creating and deleting a comment.
     *
     * @return void
     */
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
