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

// Header content.
$hcontentend = $hcontent ?? "";
$hcontent = "";

$hcontent .= "<!DOCTYPE html>
<html lang='en'>
<meta charset='UTF-8'>
<title>" . htmlspecialchars($htitle) . "</title>
<meta name='viewport' content='width=device-width,initial-scale=1'>
<link rel='stylesheet' href='" . makeURL("themes/" . htmlspecialchars($config["theme"]) . "/theme.css", true) . "'>
<link rel='icon' type='image/x-icon' href='" . makeURL("themes/" . htmlspecialchars($config["theme"]) . "/icon.png", true) . "'>
<body>";

$hcontent .= "<div class='header'><b>" . htmlspecialchars($config["title"]) . "</b></br><small>" . htmlspecialchars($config["description"]) . "</small></div>";

if ($config["installed"]) {
    $hcontent .= "<div class='navbar'><a href='" . makeURL("") . "'>Home</a> <a href='" . makeURL("posts") . "'>Posts</a>";
    if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"]) {
        $hcontent .= " <a href='" . makeURL("panel") . "'>Panel</a> <a href='" . makeURL("logout") . "'>Logout</a>";
    }
    else {
        $hcontent .= " <a href='" . makeURL("login") . "'>Login</a>";
        if ($config["allowRegistration"]) {
            $hcontent .= " <a href='" . makeURL("register") . "'>Register</a>";
        }
    }
    $hcontent .= "</div>";
}

$hcontent .= "<div class='content'>";

$hcontent .= $hcontentend;

echo($hcontent);

?>

