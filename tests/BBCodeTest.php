<?php

require_once '../classes/BBCode.php';

function tag($s)
{
    return '<' . $s . '>';
}

class BBCodeTest extends PHPUnit_Framework_TestCase
{
    protected $bbcode;

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
                '[img]http://example.com/image.jpg[/img]',
                '<img src="http://example.com/image.jpg" alt="image.jpg">'
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
                '<img src="./emoticon_happy" alt="happy">'
                . ' <img src="./emoticon_smile" alt="smile">'
                . ' <img src="./emoticon_wink" alt="wink">'
                . ' <img src="./emoticon_grin" alt="grin">'
                . ' <img src="./emoticon_tongue" alt="tongue">'
                . ' <img src="./emoticon_surprised" alt="surprised">'
                . ' <img src="./emoticon_unhappy" alt="unhappy">'
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
            array('[list]foo[*][/list]', '[list]foo[*][/list]')
        );
    }

    /**
     * @dataProvider dataForToHtml
     */
    public function testToHtml($text, $expected)
    {
        $actual = $this->bbcode->toHtml($text);
        $this->assertEquals($expected, $actual);
    }
}

?>
