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

// panel.php
// Serves as an all-purpose utility for the blog administrator and users.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$title = "";
$panelActions = array("settings", "newpost", "configuration", "users");

// If the user isn't logged in, don't let them into the panel.
if (!isset($_SESSION["logged_in"]) or ($_SESSION["logged_in"] !== true)) {
    $messages[] = error("You must be logged in to access this page.");
    render_page("", array(), $title);
    exit();
}
// Display the default page.
elseif (!isset($url[1]) or $url[1] == "") {
    $title = "Panel";
    $panelvars = array("useractions" => "",
    "adminactions" => "");
    $panelvars["useractions"] .= "<p><a href='" . makeURL("panel/settings") . "'>Account settings</a></p>";
    if (checkPerm(PERM_NEW_POST)) {
        $panelvars["useractions"] .= "<p><a href='" . makeURL("panel/newpost") . "'>Create a new post</a></p>";
    }
    if (checkPerm(PERM_MANAGE_BLOG)) {
        $panelvars["adminactions"] .= "<p><a href='" . makeURL("panel/configuration") . "'>Configure blog</a></p>";
    }
    if (checkPerm(PERM_MANAGE_USERS)) {
        $panelvars["adminactions"] .= "<p><a href='" . makeURL("panel/users") . "'>Manage users</a></p>";
    }
    render_page("panel.html", $panelvars, $title);
}
// Direct the user to one of the panel pages if applicable.
elseif (in_array($url[1], $panelActions)) {
    require "panel/{$url[1]}.php";
}
// Display an error page.
else {
    $messages[] = error("The page you requested doesn't exist.");
    render_page("", array(), $title);
}

?>
