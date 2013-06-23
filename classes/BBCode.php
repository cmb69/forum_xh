<?php

/**
 * BBCode to (X)HTML conversion.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Forum
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2013 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
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
 * @since    1beta2
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
     * Returns a list item converted to (X)HTML.
     *
     * @param string $item The content of the list item.
     *
     * @return (X)HTML.
     */
    protected function listItemsToHtml($item)
    {
        return '<li>'
            . preg_replace_callback(
                $this->pattern, array($this, 'toHtmlRec'), $item
            )
            . '</li>';
    }

    /**
     * Returns BBCode converted to (X)HTML.
     *
     * @param array $matches Matches of a previous preg_match().
     *
     * @return string (X)HTML
     */
    protected function toHtmlRec($matches)
    {
        $inlines = array('i', 'b', 'u', 's', 'url', 'img', 'size');
        array_push(
            $this->context,
            in_array($matches[1], $inlines) ? 'inline' : $matches[1]
        );
        $ok = true;
        $matches[3] = trim($matches[3]);
        switch ($matches[1]) {
        case '':
            $start = $end = '';
            $inner = preg_replace_callback(
                $this->pattern, array($this, 'toHtmlRec'), $matches[3]
            );
            break;
        case 'url':
            if (empty($matches[2])) {
                $url = $matches[3];
                $inner = $matches[3];
            } else {
                $url = substr($matches[2], 1);
                $inner = preg_replace_callback(
                    $this->pattern, array($this, 'toHtmlRec'), $matches[3]
                );
            }
            if (strpos($url, 'http:') !== 0) {
                $ok = false;
                break;
            }
            $start = '<a href="' . $url . '">';
            $end = '</a>';
            break;
        case 'img':
            $url = $matches[3];
            if (strpos($url, 'http:') !== 0) {
                $ok = false;
                break;
            }
            $start = tag('img src="' . $url . '" alt="' . basename($url) . '"');
            $end = $inner = '';
            break;
        case 'size':
            $size = substr($matches[2], 1);
            $inner = preg_replace_callback(
                $this->pattern, array($this, 'toHtmlRec'), $matches[3]
            );
            $start = '<span style="font-size: ' . $size . '%; line-height: '
                . $size . '%">';
            $end = '</span>';
            break;
        case 'list':
            if (in_array('inline', $this->context)) {
                $ok = false;
                break;
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
                $ok = false;
                break;
            }
            $inner = implode(
                '', array_map(array($this, 'listItemsToHtml'), $items)
            );
            break;
        case 'quote':
            if (in_array('inline', $this->context)) {
                $ok = false;
                break;
            }
            $start = '<blockquote>';
            $end = '</blockquote>';
            $inner = preg_replace_callback(
                $this->pattern, array($this, 'toHtmlRec'), $matches[3]
            );
            break;
        case 'code':
            if (in_array('inline', $this->context)) {
                $ok = false;
                break;
            }
            $start = '<pre>';
            $end = '</pre>';
            $inner = $matches[3];
            break;
        default:
            $start = '<' . $matches[1] . '>';
            $inner = preg_replace_callback(
                $this->pattern, array($this, 'toHtmlRec'), $matches[3]
            );
            $end = '</' . $matches[1] . '>';
        }
        array_pop($this->context);
        return $ok ? $start . $inner . $end : $matches[0];
    }

    /**
     * Returns the text with all emoticons replaced with images.
     *
     * @param string $text A text.
     *
     * @return string (X)HTML.
     */
    protected function emoticonsToHtml($text)
    {
        global $plugin_tx;

        $ptx = $plugin_tx['forum'];
        $emotions = array(
            'happy', 'smile', 'wink', 'grin', 'tongue', 'surprised', 'unhappy'
        );
        $emoticons = array(':))', ':)', ';)', ':D', ':P', ':o', ':(');
        $images = array();
        foreach ($emotions as $emotion) {
            $src = $this->emoticonDir . 'emoticon_' . $emotion;
            $alt = $ptx['lbl_' . $emotion];
            $images[] = tag('img src="' . $src . '" alt="' . $alt . '"');
        }
        return str_replace($emoticons, $images, $text);
    }

    /**
     * Returns BBCode converted to (X)HTML.
     *
     * @param string $text A BBCode formatted text.
     *
     * @return string (X)HTML.
     */
    public function toHtml($text)
    {
        $text = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
        $this->context = array();
        $text = $this->toHtmlRec(array($text, '', '', $text));
        $text = $this->emoticonsToHtml($text);
        $text = preg_replace('/\r\n|\r|\n/', tag('br'), $text);
        return $text;
    }

}

?>
