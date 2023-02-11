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

use function XH_hsc;

class BBCode
{
    /** @var array<string,string> */
    private $lang;

    /**
     * @var string
     */
    private $pattern;

    /**
     * @var array<int,string>
     */
    private $context;

    /**
     * @var string
     */
    private $emoticonDir;

    /** @var string */
    private $iframeTitle;

    /**
     * @param array<string,string> $lang
     */
    public function __construct(array $lang, string $emoticonDir, string $iframeTitle)
    {
        $this->pattern = '/\[(i|b|u|s|url|img|iframe|size|list|quote|code)(=.*?)?]'
            . '(.*?)\[\/\1]/su';
        $this->context = array();
        $this->lang = $lang;
        $this->emoticonDir = rtrim($emoticonDir, '/') . '/';
        $this->iframeTitle = $iframeTitle;
    }

    public function convert(string $text): string
    {
        $text = XH_hsc($text);
        $this->context = array();
        $text = $this->doConvert(array($text, '', '', $text));
        $text = $this->convertEmoticons($text);
        $text = (string) preg_replace('/\r\n|\r|\n/', '<br>', $text);
        $text = str_replace("\x0B", "\n", $text);
        return $text;
    }

    /**
     * @param array<int,string> $matches
     */
    private function doConvert(array $matches): string
    {
        $inlines = array('i', 'b', 'u', 's', 'url', 'img', 'iframe', 'size');
        array_push(
            $this->context,
            in_array($matches[1], $inlines) ? 'inline' : $matches[1]
        );
        $matches[3] = trim($matches[3]);
        switch ($matches[1]) {
            case '':
                $result = (string) preg_replace_callback($this->pattern, array($this, 'doConvert'), $matches[3]);
                break;
            case 'url':
                $result = $this->convertUrl($matches);
                break;
            case 'img':
                $result = $this->convertImg($matches);
                break;
            case 'iframe':
                $result = $this->convertIframe($matches);
                break;
            case 'size':
                $result = $this->convertSize($matches);
                break;
            case 'list':
                $result = $this->convertList($matches);
                break;
            case 'quote':
                $result = $this->convertQuote($matches);
                break;
            case 'code':
                $result = $this->convertCode($matches);
                break;
            default:
                $result = $this->convertOther($matches);
        }
        array_pop($this->context);
        return $result;
    }
    
    /**
     * @param array<int,string> $matches
     */
    private function convertUrl(array $matches): string
    {
        if (empty($matches[2])) {
            $url = $matches[3];
            $inner = $matches[3];
        } else {
            $url = substr($matches[2], 1);
            $inner = preg_replace_callback($this->pattern, array($this, 'doConvert'), $matches[3]);
        }
        if (!preg_match('/^http(s)?:/', $url)) {
            return $matches[0];
        }
        $start = '<a class="forum_link" href="' . $url . '" rel="nofollow">';
        $end = '</a>';
        return $start . $inner . $end;
    }

    /**
     * @param array<int,string> $matches
     */
    private function convertImg(array $matches): string
    {
        $url = $matches[3];
        if (!preg_match('/^http(s)?:/', $url)) {
            return $matches[0];
        }
        return '<img src="' . $url . '" alt="' . pathinfo($url, PATHINFO_FILENAME) . '">';
    }

    /**
     * @param array<int,string> $matches
     */
    private function convertIframe(array $matches): string
    {
        $url = $matches[3];
        if (!preg_match('/^http(s)?:/', $url)) {
            return $matches[0];
        }
        return sprintf(
            '<div class="iframe_container"><iframe src="%s" title="%s"></iframe></div>',
            $url,
            XH_hsc($this->iframeTitle)
        );
    }

    /**
     * @param array<int,string> $matches
     */
    private function convertSize(array $matches): string
    {
        $size = substr($matches[2], 1);
        $inner = preg_replace_callback($this->pattern, array($this, 'doConvert'), $matches[3]);
        $start = '<span style="font-size: ' . $size . '%; line-height: '
            . $size . '%">';
        $end = '</span>';
        return $start . $inner . $end;
    }
    
    /**
     * @param array<int,string> $matches
     */
    private function convertList(array $matches): string
    {
        if (in_array('inline', $this->context)) {
            return $matches[0];
        }
        if (empty($matches[2])) {
            $start = '<ul>';
            $end = '</ul>';
        } else {
            $start = '<ol start="' . substr($matches[2], 1) . '">';
            $end = '</ol>';
        }
        $items = array_map('trim', explode('[*]', $matches[3]));
        if (array_shift($items) != '') {
            return $matches[0];
        }
        $inner = implode('', array_map(
            function ($item) {
                return '<li>'
                    . preg_replace_callback($this->pattern, array($this, 'doConvert'), $item)
                    . '</li>';
            },
            $items
        ));
        return $start . $inner . $end;
    }

    /**
     * @param array<int,string> $matches
     */
    private function convertQuote(array $matches): string
    {
        if (in_array('inline', $this->context)) {
            return $matches[0];
        }
        $start = '<blockquote class="forum_quote">';
        $end = '</blockquote>';
        $inner = preg_replace_callback($this->pattern, array($this, 'doConvert'), $matches[3]);
        return $start . $inner . $end;
    }
    
    /**
     * @param array<int,string> $matches
     */
    private function convertCode(array $matches): string
    {
        if (in_array('inline', $this->context)) {
            return $matches[0];
        }
        $start = '<pre class="forum_code">';
        $end = '</pre>';
        $inner = preg_replace('/\r\n|\r|\n/', "\x0B", $matches[3]);
        return $start . $inner . $end;
    }
    
    /**
     * @param array<int,string> $matches
     */
    private function convertOther(array $matches): string
    {
        $start = '<' . $matches[1] . '>';
        $inner = preg_replace_callback($this->pattern, array($this, 'doConvert'), $matches[3]);
        $end = '</' . $matches[1] . '>';
        return $start . $inner . $end;
    }

    private function convertEmoticons(string $text): string
    {
        $emotions = array(
            'happy', 'smile', 'wink', 'grin', 'tongue', 'surprised', 'unhappy'
        );
        $emoticons = array(':))', ':)', ';)', ':D', ':P', ':o', ':(');
        $images = array();
        foreach ($emotions as $emotion) {
            $src = $this->emoticonDir . 'emoticon_' . $emotion . '.png';
            $alt = $this->lang['lbl_' . $emotion];
            $images[] = '<img src="' . $src . '" alt="' . $alt . '">';
        }
        return str_replace($emoticons, $images, $text);
    }
}
