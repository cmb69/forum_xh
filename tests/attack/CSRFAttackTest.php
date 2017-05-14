<?php

/**
 * Testing the CSRF protection.
 *
 * The environment variable CMSIMPLEDIR has to be set to the installation folder
 * (e.g. / or /cmsimple_xh/), and there has to be a page "Forum" with a call to
 * forum().
 *
 * PHP version 5
 *
 * @category  Testing
 * @package   Forum
 * @author    The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2013-2014 The CMSimple_XH developers <http://cmsimple-xh.org/?The_Team>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Forum_XH
 */

/**
 * A test case to actually check the CSRF protection.
 *
 * @category Testing
 * @package  Forum
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Forum_XH
 */
class CSRFAttackTest extends PHPUnit_Framework_TestCase
{
    /**
     * The URL of the CMSimple installation.
     *
     * @var string
     */
    private $_url;

    /**
     * The cURL handle.
     *
     * @var resource
     */
    private $_curlHandle;

    /**
     * The filename of the cookie file.
     *
     * @var string
     */
    private $_cookieFile;

    /**
     * Sets up the test fixture.
     *
     * Logs in to back-end and stores cookies in a temp file.
     *
     * @return void
     */
    public function setUp()
    {
        $this->_url = 'http://localhost' . getenv('CMSIMPLEDIR');
        $this->_cookieFile = tempnam(sys_get_temp_dir(), 'CC');

        $this->_curlHandle = curl_init($this->_url . '?&login=true&keycut=test');
        curl_setopt($this->_curlHandle, CURLOPT_COOKIEJAR, $this->_cookieFile);
        curl_setopt($this->_curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_exec($this->_curlHandle);
        curl_close($this->_curlHandle);
    }

    /**
     * Sets the cURL options.
     *
     * @param array $fields A map of post fields.
     *
     * @return void
     */
    private function _setCurlOptions($fields)
    {
        $options = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $this->_cookieFile
        );
        curl_setopt_array($this->_curlHandle, $options);
    }

    /**
     * Tests an attack.
     *
     * @param array  $fields      A map of post fields.
     * @param string $queryString A query string.
     *
     * @return void
     *
     * @dataProvider dataForAttack
     */
    public function testAttack($fields, $queryString = null)
    {
        $url = $this->_url . (isset($queryString) ? '?' . $queryString : '');
        $this->_curlHandle = curl_init($url);
        $this->_setCurlOptions($fields);
        curl_exec($this->_curlHandle);
        $actual = curl_getinfo($this->_curlHandle, CURLINFO_HTTP_CODE);
        curl_close($this->_curlHandle);
        $this->assertEquals(403, $actual);
    }

    /**
     * Provides data for testAttack().
     *
     * @return array
     */
    public function dataForAttack()
    {
        return array(
            array(
                array(
                    'forum_topic' => '1234567890',
                    'forum_comment' => '0987654321',
                    'forum_text' => 'hacked'
                ),
                'Forum&forum_actn=post&normal'
            ),
            array(
                array(
                    'selected' => 'Forum',
                    'forum_actn' => 'delete',
                    'forum_topic'=> '1234567890',
                    'forum_comment' => '0987654321'
                ),
                '&normal'
            )
        );
    }
}

?>
