<?php

namespace Forum\Logic;

use PHPUnit\Framework\TestCase;

class BbCodeTest extends TestCase
{
    /** @var BbCode */
    private $bbcode;

    protected function setUp(): void
    {
        $text = XH_includeVar("./languages/en.php", "plugin_tx")["forum"];
        $this->bbcode = new BbCode($text, "./");
    }

    /** @dataProvider dataForConversion */
    public function testConversion(string $text, string $expected): void
    {
        $actual = $this->bbcode->convert($text);
        $this->assertEquals($expected, $actual);
    }

    public function dataForConversion(): array
    {
        return [
            "italics" => ["[i]foo[/i]", "<i>foo</i>"],
            "bold" => ["[b]foo[/b]", "<b>foo</b>"],
            "underline" => ["[u]foo[/u]", "<u>foo</u>"],
            "strikethrough" => ["[s]foo[/s]", "<s>foo</s>"],
            "url" => [
                "[url]http://example.com/[/url]",
                "<a class=\"forum_link\" href=\"http://example.com/\" rel=\"nofollow\">http://example.com/</a>"
            ],
            "link" => [
                "[url=http://example.com/]example.com[/url]",
                "<a class=\"forum_link\" href=\"http://example.com/\" rel=\"nofollow\">example.com</a>"
            ],
            "https" => [
                "[url=https://example.com/]example.com[/url]",
                "<a class=\"forum_link\" href=\"https://example.com/\" rel=\"nofollow\">example.com</a>"
            ],
            "img" => [
                "[img]http://example.com/image.jpg[/img]",
                "<img src=\"http://example.com/image.jpg\" alt=\"image\">"
            ],
            "img https" => [
                "[img]https://example.com/image.jpg[/img]",
                "<img src=\"https://example.com/image.jpg\" alt=\"image\">"
            ],
            "img query" => [
                "[img]https://example.com/me&you.jpg[/img]",
                "<img src=\"https://example.com/me&amp;you.jpg\" alt=\"me&amp;you\">"
            ],
            "iframe" => [
                "[iframe]https://example.com/image.jpg[/iframe]",
                "<div class=\"iframe_container\"><iframe src=\"https://example.com/image.jpg\""
                . " title=\"External content\"></iframe></div>"
            ],
            "large" => [
                "[size=150]large text[/size]",
                "<span style=\"font-size: 150%; line-height: 150%\">large text</span>"
            ],
            "ul" => [
                "[list][*]One [*]Two[/list]",
                "<ul><li>One</li><li>Two</li></ul>"
            ],
            "ol" => [
                "[list=1][*]One [*]Two[/list]",
                "<ol start=\"1\"><li>One</li><li>Two</li></ol>"
            ],
            "quote" => [
                "[quote]quoted text[/quote]",
                "<blockquote class=\"forum_quote\">quoted text</blockquote>"
            ],
            "code" => [
                "[code]monospaced text[/code]",
                "<pre class=\"forum_code\">monospaced text</pre>"
            ],
            "special chars" => ["<>&\"'", "&lt;&gt;&amp;&quot;'"],
            "emoticons" => [
                ":)) :) ;) :D :P :o :(",
                "<img src=\"./emoticon_happy.png\" alt=\"Happy\">"
                . " <img src=\"./emoticon_smile.png\" alt=\"Smile\">"
                . " <img src=\"./emoticon_wink.png\" alt=\"Wink\">"
                . " <img src=\"./emoticon_grin.png\" alt=\"Grin\">"
                . " <img src=\"./emoticon_tongue.png\" alt=\"Tongue\">"
                . " <img src=\"./emoticon_surprised.png\" alt=\"Surprised\">"
                . " <img src=\"./emoticon_unhappy.png\" alt=\"Unhappy\">"
            ],
            "italics bold" => ["[i][b]foo[/b][/i]", "<i><b>foo</b></i>"],
            "italics bold bad" => ["[i][b]foo[/i][/b]", "<i>[b]foo</i>[/b]"],
            "bad url" => ["[url]foo[/url]", "[url]foo[/url]"],
            "bad img" => ["[img]foo[/img]", "[img]foo[/img]"],
            "italics list" => ["[i][list][*]item[/list][/i]", "<i>[list][*]item[/list]</i>"],
            "italics quote" => [
                "[i][quote]quoted text[/quote][/i]",
                "<i>[quote]quoted text[/quote]</i>"
            ],
            "italics code" => [
                "[i][code]monospaced text[/code][/i]",
                "<i>[code]monospaced text[/code]</i>"
            ],
            "bad list" => ["[list]foo[*][/list]", "[list]foo[*][/list]"],
            "multiline code" => [
                "[code]first line\nsecond line[/code]",
                "<pre class=\"forum_code\">first line\nsecond line</pre>"
            ],
        ];
    }
}
