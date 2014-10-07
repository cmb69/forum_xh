<?php

/**
 * Testing the BBCode.
 *
 * PHP version 5
 *
 * @category  Testing
 * @package   Forum
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2013-2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Forum_XH
 */

require_once '../../cmsimple/functions.php';
require_once './classes/BBCode.php';

/**
 * Testing the BBCode.
 *
 * @category Testing
 * @package  Forum
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Forum_XH
 */
class BBCodeTest extends PHPUnit_Framework_TestCase
{
    /**
     * The test subject.
     *
     * @var object
     */
    protected $bbcode;

    /**
     * Sets up the test fixture.
     *
     * @return void
     *
     * @global array The localization of the plugin.
     */
    public function setUp()
    {
        global $plugin_tx;

        $plugin_tx['forum'] = array(
            'lbl_happy' => 'happy',
            'lbl_smile' => 'smile',
            'lbl_wink' => 'wink',
            'lbl_grin' => 'grin',
            'lbl_tongue' => 'tongue',
            'lbl_surprised' => 'surprised',
            'lbl_unhappy' => 'unhappy'
        );
        $this->bbcode = new Forum_BBCode('./');
    }

    /**
     * Tests the toHtml() conversion.
     *
     * @param string $text     A text.
     * @param string $expected An expected result.
     *
     * @return void
     *
     * @dataProvider dataForToHtml
     */
    public function testToHtml($text, $expected)
    {
        $actual = $this->bbcode->toHtml($text);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Supplies data for testToHtml().
     *
     * @return array
     */
    public function dataForToHtml()
    {
        return array(
            array('[i]foo[/i]', '<i>foo</i>'),
            array('[b]foo[/b]', '<b>foo</b>'),
            array('[u]foo[/u]', '<u>foo</u>'),
            array('[s]foo[/s]', '<s>foo</s>'),
            array(
                '[url]http://example.com/[/url]',
                '<a href="http://example.com/">http://example.com/</a>'
            ),
            array(
                '[url=http://example.com/]example.com[/url]',
                '<a href="http://example.com/">example.com</a>'
            ),
            array(
                '[url=https://example.com/]example.com[/url]',
                '<a href="https://example.com/">example.com</a>'
            ),
            array(
                '[img]http://example.com/image.jpg[/img]',
                '<img src="http://example.com/image.jpg" alt="image.jpg">'
            ),
            array(
                '[img]https://example.com/image.jpg[/img]',
                '<img src="https://example.com/image.jpg" alt="image.jpg">'
            ),
            array(
                '[size=150]large text[/size]',
                '<span style="font-size: 150%; line-height: 150%">'
                . 'large text</span>'
            ),
            array(
                '[list][*]One [*]Two[/list]',
                '<ul><li>One</li><li>Two</li></ul>'
            ),
            array(
                '[list=1][*]One [*]Two[/list]',
                '<ol start="1"><li>One</li><li>Two</li></ol>'
            ),
            array(
                '[quote]quoted text[/quote]',
                '<blockquote>quoted text</blockquote>'
            ),
            array(
                '[code]monospaced text[/code]',
                '<pre>monospaced text</pre>'
            ),
            array('<>&"\'', '&lt;&gt;&amp;&quot;\''),
            array(
                ':)) :) ;) :D :P :o :(',
                '<img src="./emoticon_happy.png" alt="happy">'
                . ' <img src="./emoticon_smile.png" alt="smile">'
                . ' <img src="./emoticon_wink.png" alt="wink">'
                . ' <img src="./emoticon_grin.png" alt="grin">'
                . ' <img src="./emoticon_tongue.png" alt="tongue">'
                . ' <img src="./emoticon_surprised.png" alt="surprised">'
                . ' <img src="./emoticon_unhappy.png" alt="unhappy">'
            ),
            array('[i][b]foo[/b][/i]', '<i><b>foo</b></i>'),
            array('[i][b]foo[/i][/b]', '<i>[b]foo</i>[/b]'),
            array('[url]foo[/url]', '[url]foo[/url]'),
            array('[img]foo[/img]', '[img]foo[/img]'),
            array('[i][list][*]item[/list][/i]', '<i>[list][*]item[/list]</i>'),
            array(
                '[i][quote]quoted text[/quote][/i]',
                '<i>[quote]quoted text[/quote]</i>'
            ),
            array(
                '[i][code]monospaced text[/code][/i]',
                '<i>[code]monospaced text[/code]</i>'
            ),
            array('[list]foo[*][/list]', '[list]foo[*][/list]'),
            array(
                "[code]first line\nsecond line[/code]",
                "<pre>first line\nsecond line</pre>"
            )
        );
    }
}

?>
