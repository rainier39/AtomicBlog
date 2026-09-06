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

// database.php
// Defines global database-related functions used throughout the software.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// Wrapper function for making prepared statements.
// Don't pass anything other than a 1 dimensional array in as the $params.
// I.E. none of the items in params should be anything other than an int, float, or string. Unless the item is really a blob, which this software never uses in the DB.
// Also don't pass nothing to $params, because you don't need to use this function.
function preparedQuery(string $query, array $params) {
    global $db;
    
    // Figure out what type each parameter is.
    $types = "";
    foreach ($params as $param) {
        if (is_numeric($param)) {
            // Case: integer.
            if ((int)$param == $param) {
                $types .= "i";
            }
            // Otherwise it must be a float.
            else {
                $types .= "d";
            }
        }
        // String.
        elseif (is_string($param)) {
            $types .= "s";
        }
        // If all else fails assume it's a blob.
        else {
            $types .= "b";
        }
    }
    
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

?>
