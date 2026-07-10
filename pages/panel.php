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

// If the user isn't logged in, don't let them into the panel.
if (!isset($_SESSION["logged_in"]) or ($_SESSION["logged_in"] !== true)) {
    $messages[] = error("You must be logged in to access this page.");
}
// Display the default page.
elseif (!isset($url[1]) or $url[1] == "") {
    $title = "Panel";
    $panelvars = array("useractions" => "",
    "adminactions" => "");
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
// Direct the user to the "create a new post" page.
elseif ($url[1] == "newpost") {
    require "panel/newpost.php";
}
elseif ($url[1] == "configuration") {
    require "panel/configuration.php";
}
elseif ($url[1] == "users") {
    require "panel/users.php";
}
// Display an error page.
else {
    $messages[] = error("The page you requested doesn't exist.");
    render_page("", array(), $title);
}

?>

