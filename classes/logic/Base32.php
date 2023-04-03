<?php

/**
 * Copyright 2022-2023 Christoph M. Becker
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

/**
 * <https://www.crockford.com/base32.html> without padding
 */
class Base32
{
    private const ALPHABET = "0123456789ABCDEFGHJKMNPQRSTVWXYZ"; // Crockford

    public static function encode(string $bytes): string
    {
        $len = strlen($bytes);
        if ($len % 5 !== 0) {
            $bytes .= "\0";
        }
        $res = "";
        for ($i = 0; $i < $len; $i += 5) {
            $res .= self::ALPHABET[(((ord($bytes[$i + 0]) << 8) | ord($bytes[$i + 1])) >> 11) & 0b11111];
            $res .= self::ALPHABET[(((ord($bytes[$i + 0]) << 8) | ord($bytes[$i + 1])) >>  6) & 0b11111];
            if ($i + 1 === $len) {
                return $res;
            }
            $res .= self::ALPHABET[(((ord($bytes[$i + 0]) << 8) | ord($bytes[$i + 1])) >>  1) & 0b11111];
            $res .= self::ALPHABET[(((ord($bytes[$i + 1]) << 8) | ord($bytes[$i + 2])) >>  4) & 0b11111];
            if ($i + 2 === $len) {
                return $res;
            }
            $res .= self::ALPHABET[(((ord($bytes[$i + 2]) << 8) | ord($bytes[$i + 3])) >>  7) & 0b11111];
            if ($i + 3 === $len) {
                return $res;
            }
            $res .= self::ALPHABET[(((ord($bytes[$i + 2]) << 8) | ord($bytes[$i + 3])) >>  2) & 0b11111];
            $res .= self::ALPHABET[(((ord($bytes[$i + 3]) << 8) | ord($bytes[$i + 4])) >>  5) & 0b11111];
            if ($i + 4 === $len) {
                return $res;
            }
            $res .= self::ALPHABET[(((ord($bytes[$i + 3]) << 8) | ord($bytes[$i + 4])) >>  0) & 0b11111];
        }
        return $res;
    }
    public static function decode(string $string): string
    {
        $len = strlen($string);
        if ($len % 8 !== 0) {
            $string .= "==";
        }
        $res = "";
        $bits = [];
        for ($i = 0; $i < $len; $i += 8) {
            $bits[0] = strpos(self::ALPHABET, $string[$i + 0]);
            $bits[1] = strpos(self::ALPHABET, $string[$i + 1]);
            $res .= chr($bits[0] << 3 | $bits[1] >> 2);
            if ($i + 2 === $len) {
                return $res;
            }
            $bits[2] = strpos(self::ALPHABET, $string[$i + 2]);
            $bits[3] = strpos(self::ALPHABET, $string[$i + 3]);
            $res .= chr($bits[1] << 6 | $bits[2] << 1 | $bits[3] >> 4);
            if ($i + 4 === $len) {
                return $res;
            }
            $bits[4] = strpos(self::ALPHABET, $string[$i + 4]);
            $res .= chr($bits[3] << 4 | $bits[4] >> 1);
            if ($i + 5 === $len) {
                return $res;
            }
            $bits[5] = strpos(self::ALPHABET, $string[$i + 5]);
            $bits[6] = strpos(self::ALPHABET, $string[$i + 6]);
            $res .= chr($bits[4] << 7 | $bits[5] << 2 | $bits[6] >> 3);
            if ($i + 7 === $len) {
                return $res;
            }
            $bits[7] = strpos(self::ALPHABET, $string[$i + 7]);
            $res .= chr($bits[6] << 5 | $bits[7] >> 0);
        }
        return $res;
    }
}
