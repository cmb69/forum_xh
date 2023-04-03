<?php

/**
 * Copyright 2023 Christoph M. Becker
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

use PHPUnit\Framework\TestCase;

class Base32Test extends TestCase
{
    /** @dataProvider encodeData */
    public function testEncode(string $input, string $expected): void
    {
        $actual = Base32::encode($input);
        $this->assertEquals($expected, $actual);
    }

    public function encodeData(): array
    {
        return [
            ["", ""],
            ["title", "EHMQ8V35"],
            ["an even longer title", "C5Q20SBPCNQ20V3FDSKPAWH0EHMQ8V35"],
            ["t", "EG"],
            ["ti", "EHMG"],
            ["tit", "EHMQ8"],
            ["titl", "EHMQ8V0"],
            ["a longer title", "C4G6RVVECXJQ483MD5T6RS8"],
            ["longer title", "DHQPWSV5E8G78TBMDHJG"],
        ];
    }

    /** @dataProvider decodeData */
    public function testDecode(string $input, string $expected): void
    {
        $actual = Base32::decode($input);
        $this->assertEquals($expected, $actual);
    }

    public function decodeData(): array
    {
        return [
            ["", ""],
            ["EHMQ8V35", "title"],
            ["C5Q20SBPCNQ20V3FDSKPAWH0EHMQ8V35", "an even longer title"],
            ["EG", "t"],
            ["EHMG", "ti"],
            ["EHMQ8", "tit"],
            ["EHMQ8V0", "titl"],
            ["C4G6RVVECXJQ483MD5T6RS8", "a longer title"],
            ["DHQPWSV5E8G78TBMDHJG", "longer title"],
        ];
    }
}
