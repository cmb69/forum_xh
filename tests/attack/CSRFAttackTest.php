<?php

/**
 * Copyright 2013-2014 The CMSimple_XH developers
 * Copyright 2014-2017 Christoph M. Becker
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

/*
 * The environment variable CMSIMPLEDIR has to be set to the installation folder
 * (e.g. / or /cmsimple_xh/), and there has to be a page "Forum" with a call to
 * forum().
 */

namespace Forum;

use PHPUnit_Framework_TestCase;

class CSRFAttackTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var resource
     */
    private $curlHandle;

    /**
     * @var string
     */
    private $cookieFile;

    protected function setUp()
    {
        $this->url = 'http://localhost' . getenv('CMSIMPLEDIR');
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'CC');

        $this->curlHandle = curl_init($this->url . '?&login=true&keycut=test');
        curl_setopt($this->curlHandle, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_exec($this->curlHandle);
        curl_close($this->curlHandle);
    }

    /**
     * @param array $fields
     */
    private function setCurlOptions($fields)
    {
        $options = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $this->cookieFile
        );
        curl_setopt_array($this->curlHandle, $options);
    }

    /**
     * @param array $fields
     * @param string $queryString
     * @dataProvider dataForAttack
     */
    public function testAttack($fields, $queryString = null)
    {
        $url = $this->url . (isset($queryString) ? '?' . $queryString : '');
        $this->curlHandle = curl_init($url);
        $this->setCurlOptions($fields);
        curl_exec($this->curlHandle);
        $actual = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
        curl_close($this->curlHandle);
        $this->assertEquals(403, $actual);
    }

    /**
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
