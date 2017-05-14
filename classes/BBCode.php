<?php

/**
 * BBCode to (X)HTML conversion.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Forum
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2013-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Forum_XH
 */

/**
 * The BBCode to (X)HTML conversion class.
 *
 * @category CMSimple_XH
 * @package  Forum
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Forum_XH
 * @since    1.0beta2
 */
class Forum_BBCode
{
    /**
     * The BBCode regex pattern.
     *
     * @var string
     */
    protected $pattern;

    /**
     * The current context of the BBCode conversion.
     *
     * @var array
     */
    protected $context;

    /**
     * The path of the emoticon directory.
     *
     * @var string
     */
    protected $emoticonDir;

    /**
     * Constructs an instance.
     *
     * @param string $emoticonDir The path of the emoticon directory.
     */
    public function __construct($emoticonDir)
    {
        $this->pattern = '/\[(i|b|u|s|url|img|size|list|quote|code)(=.*?)?]'
            . '(.*?)\[\/\1]/su';
        $this->context = array();
        $this->emoticonDir = rtrim($emoticonDir, '/') . '/';
    }

    /**
     * Returns BBCode converted to (X)HTML.
     *
     * @param string $text A BBCode formatted text.
     *
     * @return string (X)HTML.
     */
    public function convert($text)
    {
        $text = XH_hsc($text);
        $this->context = array();
        $text = $this->doConvert(array($text, '', '', $text));
        $text = $this->convertEmoticons($text);
        $text = preg_replace('/\r\n|\r|\n/', tag('br'), $text);
        $text = str_replace("\x0B", "\n", $text);
        return $text;
    }

    /**
     * Returns BBCode converted to (X)HTML.
     *
     * @param array $matches Matches of a previous preg_match().
     *
     * @return string (X)HTML
     */
    protected function doConvert($matches)
    {
        $inlines = array('i', 'b', 'u', 's', 'url', 'img', 'size');
        array_push(
            $this->context,
            in_array($matches[1], $inlines) ? 'inline' : $matches[1]
        );
        $matches[3] = trim($matches[3]);
        switch ($matches[1]) {
        case '':
            $result = preg_replace_callback(
                $this->pattern, array($this, 'doConvert'), $matches[3]
            );
            break;
        case 'url':
            $result = $this->convertUrl($matches);
            break;
        case 'img':
            $result = $this->convertImg($matches);
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
     * Converts a BBCode `url` element to (X)HTML.
     *
     * @param array $matches Matches of the previous preg_match().
     *
     * @return string (X)HTML
     */
    protected function convertUrl($matches)
    {
        if (empty($matches[2])) {
            $url = $matches[3];
            $inner = $matches[3];
        } else {
            $url = substr($matches[2], 1);
            $inner = preg_replace_callback(
                $this->pattern, array($this, 'doConvert'), $matches[3]
            );
        }
        if (!preg_match('/^http(s)?:/', $url)) {
            return $matches[0];
        }
        $start = '<a href="' . $url . '" rel="nofollow">';
        $end = '</a>';
        return $start . $inner . $end;
    }
    
    /**
     * Converts a BBCode `img` element to (X)HTML.
     *
     * @param array $matches Matches of the previous preg_match().
     *
     * @return string (X)HTML
     */
    protected function convertImg($matches)
    {
        $url = $matches[3];
        if (!preg_match('/^http(s)?:/', $url)) {
            return $matches[0];
        }
        return tag('img src="' . $url . '" alt="' . basename($url) . '"');
    }
    
    /**
     * Converts a BBCode `size` element to (X)HTML.
     *
     * @param array $matches Matches of the previous preg_match().
     *
     * @return string (X)HTML
     */
    protected function convertSize($matches)
    {
        $size = substr($matches[2], 1);
        $inner = preg_replace_callback(
            $this->pattern, array($this, 'doConvert'), $matches[3]
        );
        $start = '<span style="font-size: ' . $size . '%; line-height: '
            . $size . '%">';
        $end = '</span>';
        return $start . $inner . $end;
    }
    
    /**
     * Converts a BBCode `list` element to (X)HTML.
     *
     * @param array $matches Matches of the previous preg_match().
     *
     * @return string (X)HTML
     */
    protected function convertList($matches)
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
        $inner = implode(
            '', array_map(array($this, 'convertListItem'), $items)
        );
        return $start . $inner . $end;
    }

    /**
     * Converts a list item to (X)HTML.
     *
     * @param string $item The content of the list item.
     *
     * @return (X)HTML.
     */
    protected function convertListItem($item)
    {
        return '<li>'
            . preg_replace_callback(
                $this->pattern, array($this, 'doConvert'), $item
            )
            . '</li>';
    }

    /**
     * Converts a BBCode `quote` element to (X)HTML.
     *
     * @param array $matches Matches of the previous preg_match().
     *
     * @return string (X)HTML
     */
    protected function convertQuote($matches)
    {
        if (in_array('inline', $this->context)) {
            return $matches[0];
        }
        $start = '<blockquote class="forum_quote">';
        $end = '</blockquote>';
        $inner = preg_replace_callback(
            $this->pattern, array($this, 'doConvert'), $matches[3]
        );
        return $start . $inner . $end;
    }
    
    /**
     * Converts a BBCode `code` element to (X)HTML.
     *
     * @param array $matches Matches of the previous preg_match().
     *
     * @return string (X)HTML
     */
    protected function convertCode($matches)
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
     * Converts another BBCode element to (X)HTML.
     *
     * @param array $matches Matches of the previous preg_match().
     *
     * @return string (X)HTML
     */
    protected function convertOther($matches)
    {
        $start = '<' . $matches[1] . '>';
        $inner = preg_replace_callback(
            $this->pattern, array($this, 'doConvert'), $matches[3]
        );
        $end = '</' . $matches[1] . '>';
        return $start . $inner . $end;
    }

    /**
     * Returns the text with all emoticons replaced with images.
     *
     * @param string $text A text.
     *
     * @return string (X)HTML.
     */
    protected function convertEmoticons($text)
    {
        global $plugin_tx;

        $ptx = $plugin_tx['forum'];
        $emotions = array(
            'happy', 'smile', 'wink', 'grin', 'tongue', 'surprised', 'unhappy'
        );
        $emoticons = array(':))', ':)', ';)', ':D', ':P', ':o', ':(');
        $images = array();
        foreach ($emotions as $emotion) {
            $src = $this->emoticonDir . 'emoticon_' . $emotion . '.png';
            $alt = $ptx['lbl_' . $emotion];
            $images[] = tag('img src="' . $src . '" alt="' . $alt . '"');
        }
        return str_replace($emoticons, $images, $text);
    }
}

?>
