<?php

/**
 * Copyright (c) Christoph M. Becker
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

namespace Forum\Logic;

use function XH_hsc;

class BbCode
{
    private const PATTERN = '/\[(i|b|u|s|url|img|iframe|size|list|quote|code)(=.*?)?](.*?)\[\/\1]/su';

    /** @var array<string,string> */
    private $text;

    /** @var list<string> */
    private $context;

    /** @var string */
    private $emoticonFolder;

    /** @param array<string,string> $text */
    public function __construct(array $text, string $emoticonFolder)
    {
        $this->context = [];
        $this->text = $text;
        $this->emoticonFolder = $emoticonFolder;
    }

    public function convert(string $text): string
    {
        $text = XH_hsc($text);
        $this->context = [];
        $text = $this->doConvert([$text, "", "", $text]);
        $text = $this->convertEmoticons($text);
        $text = (string) preg_replace('/\r\n|\r|\n/', "<br>", $text);
        $text = str_replace("\x0B", "\n", $text);
        return $text;
    }

    /** @param array<int,string> $matches */
    private function doConvert(array $matches): string
    {
        $inlines = ["i", "b", "u", "s", "url", "img", "iframe", "size"];
        array_push($this->context, in_array($matches[1], $inlines, true) ? "inline" : $matches[1]);
        $matches[3] = trim($matches[3]);
        $result = $this->convertElement($matches);
        array_pop($this->context);
        return $result;
    }

    /** @param array<int,string> $matches */
    private function convertElement(array $matches): string
    {
        switch ($matches[1]) {
            case "":
                return (string) preg_replace_callback(self::PATTERN, [$this, "doConvert"], $matches[3]);
            case "url":
                return $this->convertUrl($matches);
            case "img":
                return $this->convertImg($matches);
            case "iframe":
                return $this->convertIframe($matches);
            case "size":
                return $this->convertSize($matches);
            case "list":
                return $this->convertList($matches);
            case "quote":
                return $this->convertQuote($matches);
            case "code":
                return $this->convertCode($matches);
            default:
                return $this->convertOther($matches);
        }
    }

    /** @param array<int,string> $matches */
    private function convertUrl(array $matches): string
    {
        if (empty($matches[2])) {
            $url = $matches[3];
            $inner = $matches[3];
        } else {
            $url = substr($matches[2], 1);
            $inner = preg_replace_callback(self::PATTERN, [$this, "doConvert"], $matches[3]);
        }
        if (!preg_match('/^http(s)?:/', $url)) {
            return $matches[0];
        }
        return "<a class=\"forum_link\" href=\"$url\" rel=\"nofollow\">" . $inner . "</a>";
    }

    /** @param array<int,string> $matches */
    private function convertImg(array $matches): string
    {
        $url = $matches[3];
        if (!preg_match('/^http(s)?:/', $url)) {
            return $matches[0];
        }
        return "<img src=\"$url\" alt=\"" . pathinfo($url, PATHINFO_FILENAME) . "\">";
    }

    /** @param array<int,string> $matches */
    private function convertIframe(array $matches): string
    {
        $url = $matches[3];
        if (!preg_match('/^http(s)?:/', $url)) {
            return $matches[0];
        }
        $title = XH_hsc($this->text["title_iframe"]);
        return "<div class=\"iframe_container\"><iframe src=\"$url\" title=\"$title\"></iframe></div>";
    }

    /** @param array<int,string> $matches */
    private function convertSize(array $matches): string
    {
        $size = substr($matches[2], 1);
        return "<span style=\"font-size: $size%; line-height: $size%\">"
            . preg_replace_callback(self::PATTERN, [$this, "doConvert"], $matches[3]) . "</span>";
    }

    /** @param array<int,string> $matches */
    private function convertList(array $matches): string
    {
        if (in_array("inline", $this->context, true)) {
            return $matches[0];
        }
        if (empty($matches[2])) {
            $start = "<ul>";
            $end = "</ul>";
        } else {
            $start = "<ol start=\"" . substr($matches[2], 1) . "\">";
            $end = "</ol>";
        }
        $items = array_map("trim", explode("[*]", $matches[3]));
        if (array_shift($items) !== "") {
            return $matches[0];
        }
        $inner = implode("", array_map(function ($item) {
            return "<li>" . preg_replace_callback(self::PATTERN, [$this, "doConvert"], $item) . "</li>";
        }, $items));
        return $start . $inner . $end;
    }

    /** @param array<int,string> $matches */
    private function convertQuote(array $matches): string
    {
        if (in_array("inline", $this->context, true)) {
            return $matches[0];
        }
        return "<blockquote class=\"forum_quote\">"
            . preg_replace_callback(self::PATTERN, [$this, "doConvert"], $matches[3]) . "</blockquote>";
    }

    /** @param array<int,string> $matches */
    private function convertCode(array $matches): string
    {
        if (in_array("inline", $this->context, true)) {
            return $matches[0];
        }
        return "<pre class=\"forum_code\">" . preg_replace('/\r\n|\r|\n/', "\x0B", $matches[3]) . "</pre>";
    }

    /** @param array<int,string> $matches */
    private function convertOther(array $matches): string
    {
        return "<$matches[1]>" . preg_replace_callback(self::PATTERN, [$this, "doConvert"], $matches[3])
            . "</$matches[1]>";
    }

    private function convertEmoticons(string $text): string
    {
        $images = array_map(function (string $emotion) {
            $src = $this->emoticonFolder . "emoticon_$emotion.png";
            $alt = $this->text["lbl_$emotion"];
            return "<img src=\"$src\" alt=\"$alt\">";
        }, ["happy", "smile", "wink", "grin", "tongue", "surprised", "unhappy"]);
        return str_replace([":))", ":)", ";)", ":D", ":P", ":o", ":("], $images, $text);
    }
}
