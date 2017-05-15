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

class BBCodeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var object
     */
    private $bbcode;

    protected function setUp()
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
        $this->bbcode = new BBCode('./');
    }

    /**
     * @param string $text
     * @param string $expected
     * @dataProvider dataForConversion
     */
    public function testConversion($text, $expected)
    {
        $actual = $this->bbcode->convert($text);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function dataForConversion()
    {
        return array(
            array('[i]foo[/i]', '<i>foo</i>'),
            array('[b]foo[/b]', '<b>foo</b>'),
            array('[u]foo[/u]', '<u>foo</u>'),
            array('[s]foo[/s]', '<s>foo</s>'),
            array(
                '[url]http://example.com/[/url]',
                '<a href="http://example.com/" rel="nofollow">http://example.com/'
                . '</a>'
            ),
            array(
                '[url=http://example.com/]example.com[/url]',
                '<a href="http://example.com/" rel="nofollow">example.com</a>'
            ),
            array(
                '[url=https://example.com/]example.com[/url]',
                '<a href="https://example.com/" rel="nofollow">example.com</a>'
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
                '<blockquote class="forum_quote">quoted text</blockquote>'
            ),
            array(
                '[code]monospaced text[/code]',
                '<pre class="forum_code">monospaced text</pre>'
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
                "<pre class=\"forum_code\">first line\nsecond line</pre>"
            )
        );
    }
}
