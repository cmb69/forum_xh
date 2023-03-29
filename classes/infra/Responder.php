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

namespace Forum\Infra;

use Forum\Value\Response;

class Responder
{
    /** @return string|never */
    public static function respond(Response $response)
    {
        global $hjs;

        if ($response->location() !== null) {
            self::purgeOutputBuffers();
            header("Location: " . $response->location(), true, 303);
            exit;
        }
        if ($response->exit()) {
            self::purgeOutputBuffers();
            echo $response->output();
            exit;
        }
        $hjs .= $response->hjs();
        return $response->output();
    }

    /** @return void */
    private static function purgeOutputBuffers()
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }
}
