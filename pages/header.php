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

// header.php
// Serves the header.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$headervars = array("lang" => "code",
"pagetitle" => $htitle,
"theme" => makeURL("themes/" . htmlspecialchars($config["theme"]) . "/theme.css", true),
"icon" => makeURL("themes/" . htmlspecialchars($config["theme"]) . "/icon.png", true),
"blogtitle" => $config["title"],
"description" => $config["description"],
"navbar" => "",
"messages" => implode($messages));

// Generate the navbar appropriately.
if ($config["installed"]) {
    $headervars["navbar"] .= "<div class='navbar'><a href='" . makeURL("") . "'>" . lang("navbar.home") . "</a> <a href='" . makeURL("posts") . "'>" . lang("global.posts") . "</a>";
    if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"]) {
        $headervars["navbar"] .= " <a href='" . makeURL("panel") . "'>" . lang("global.panel") . "</a> <a href='" . makeURL("logout") . "'>" . lang("navbar.logout") . "</a>";
    }
    else {
        $headervars["navbar"] .= " <a href='" . makeURL("login") . "'>" . lang("global.login") . "</a>";
        if ($config["allowRegistration"]) {
            $headervars["navbar"] .= " <a href='" . makeURL("register") . "'>" . lang("global.register") . "</a>";
        }
    }
    $headervars["navbar"] .= "</div>";
}


render_template("header.html", $headervars);

?>

