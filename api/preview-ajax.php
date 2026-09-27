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

// preview-ajax.php
// Formats given text, providing post preview functionality.

// This page can be loaded on its own.
// Pretend we are the index so we can load the formatter.
define("INDEX", "1");

require "../core/formatter.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["previewcontent"])) {
        echo(format($_POST["previewcontent"]));
    }
}

?>
