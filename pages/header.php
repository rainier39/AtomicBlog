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

$headervars = array("pagetitle" => $htitle,
"head" => "",
"theme" => makeURL("themes/" . htmlspecialchars($config["theme"]) . "/theme.css", true),
"icon" => makeURL("themes/" . htmlspecialchars($config["theme"]) . "/icon.png", true),
"blogtitle" => $config["title"],
"description" => $config["description"],
"installed" => $config["installed"],
"navbar" => "",
"messages" => implode($messages));

if (strlen($config["customCSS"]) > 0) {
    // *** This may be dangerous security-wise. ***
    $headervars["head"] .= "<style>" . htmlspecialchars($config["customCSS"], ENT_NOQUOTES) . "</style>";
}

// Generate the navbar appropriately.
if ($_SESSION["logged_in"]) {
    $headervars["navbar"] .= "<a class='navbarButton' href='" . makeURL("panel") . "'>" . lang("global.panel") . "</a>";
    $headervars["navbar"] .= "<a class='navbarButton' href='" . makeURL("profile/" . $_SESSION["id"]) . "'>Profile</a>";
    $headervars["navbar"] .= render_template("logout.html", array("token" => $_SESSION["csrf_token"]), false);
}
else {
    $headervars["navbar"] .= "<a class='navbarButton' href='" . makeURL("login") . "'>" . lang("global.login") . "</a>";
    if ($config["allowRegistration"]) {
        $headervars["navbar"] .= "<a class='navbarButton' href='" . makeURL("register") . "'>" . lang("global.register") . "</a>";
    }
}

render_template("header.html", $headervars);

?>
