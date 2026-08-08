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

// profile.php
// Display's a given user's profile.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$title = "";
$uid = $url[1] ?? "";

if (!checkPerm(PERM_VIEW_PROFILE)) {
    $messages[] = error("You don't have permission to view profiles.");
    render_page("", array(), $title);
    exit();
}

$profileinfo = $db->query("SELECT `name`, `color`, `bio`, `email`, `lastactive`, `namevisible`, `emailvisible` FROM `accounts` WHERE `id`='" . $db->real_escape_string($uid) . "'");
$p = $profileinfo->fetch_assoc();

if ($profileinfo->num_rows < 1) {
    $messages[] = error("Profile does not exist.");
    render_page("", array(), $title);
    exit();
}

if ($p["namevisible"]) {
    $name = $p["name"];
}
else {
    $name = "Anonymous";
}

if ($p["emailvisible"]) {
    $email = $p["email"];
}
else {
    $email = "***@***";
}

$lastactiveHTML = "<small><span class='date' title='" . date("g:i:sa", $p["lastactive"]) . "'>" . date("F jS, Y", $p["lastactive"]) . "</span></small>";

$title = htmlspecialchars($name) . "'s Profile";

$profilevars = array("color" => $p["color"],
"name" => $name,
"bio" => $p["bio"],
"lastactive" => $lastactiveHTML,
"email" => $email);

render_page("profile.html", $profilevars, $title);

?>
