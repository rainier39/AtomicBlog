<?php
/*
 * Copyright © 2025 rainier39 <rainier39@proton.me>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

// otp.php
// Defines HOTP/TOTP related functions.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// Custom base32 implementation. (yes this was necessary)
// See: https://datatracker.ietf.org/doc/html/rfc4648#section-6
function base32_encode($string) {
    $result = "";
    $table = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "2", "3", "4", "5", "6", "7");
    // Process each 40-bit (5-byte) group.
    for ($i = 0; $i < strlen($string); $i += 5) {
        $groupasstr = substr($string, $i, 5);
        // Padding handling.
        switch (strlen($groupasstr)) {
            case 5:
                $parts = 8;
                break;
            case 4:
                $parts = 7;
                break;
            case 3:
                $parts = 5;
                break;
            case 2:
                $parts = 4;
                break;
            case 1:
                $parts = 2;
                break;
            default:
                $parts = 0;
        }
        while (strlen($groupasstr) < 5) {
            $groupasstr .= chr(0);
        }
        $group = unpack("Jint", str_pad($groupasstr, 8, chr(0), STR_PAD_LEFT))["int"];
        for ($j = 0; $j < $parts; $j++) {
            $idx = ($group & 0xf800000000) >> 35;
            $result .= $table[$idx];
            $group = $group << 5;
        }
        // Add padding if needed.
        for ($j = 0; $j < (8-$parts); $j++) {
            $result .= "=";
        }
    }
    return $result ?: null;
}

// Custom HOTP implementation.
// See: https://www.rfc-editor.org/info/rfc4226/
/*
 * key should be random_bytes(20), 20 bytes = 160 bits aka the recommended size.
 * counter should be 8 bytes long
 * length should be 6-10, 6-8 is recommended, 6 is default.
 */
function HOTP($key, $counter, $length=6) {
    $counter = str_pad(pack("J", $counter), 8, chr(0), STR_PAD_LEFT);
    $hs = hash_hmac("sha1", $counter, $key, true);
    // Get dynamic offset.
    $offset = ord($hs[strlen($hs)-1]) & 0xf;
    // Get 4 bytes, remove most significant bit.
    $p = ((ord($hs[$offset]) & 0x7f) << 24)
    | ((ord($hs[$offset+1]) & 0xff) << 16)
    | ((ord($hs[$offset+2]) & 0xff) << 8)
    | (ord($hs[$offset+3]) & 0xff);
    return ($p % pow(10, $length));
}

// Custom TOTP implementation.
// See: https://www.rfc-editor.org/info/rfc6238/
/*
 * key should be random_bytes(20), 20 bytes = 160 bits aka the recommended size.
 */
function TOTP($key) {
    $now = time();
    $codes = array();
    // Allow for OTP codes generated in the previous and next time-step.
    // This is as recommended in the RFC.
    for ($i = -1; $i <= 1; $i++) {
        $codes[] = HOTP($key, (int)($now/30)+$i);
    }
    return $codes;
}

?>
