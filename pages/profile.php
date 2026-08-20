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
if ($uid != "") $uid = (int)$uid;

if (!checkPerm(PERM_VIEW_PROFILE)) {
    $messages[] = error("You don't have permission to view profiles.");
    render_page("", array(), $title);
    exit();
}

$profileinfo = $db->query("SELECT `name`, `color`, `bio`, `email`, `lastactive`, `namevisible`, `emailvisible`, `role` FROM `accounts` WHERE `id`='" . $db->real_escape_string($uid) . "'");
$p = $profileinfo->fetch_assoc();

if ($profileinfo->num_rows < 1) {
    $messages[] = error("Profile does not exist.");
    render_page("", array(), $title);
    exit();
}

$comments = $db->query("SELECT 1 FROM `comments` WHERE `account`='" . $db->real_escape_string($uid) . "'");
$posts = $db->query("SELECT 1 FROM `posts` WHERE `account`='" . $db->real_escape_string($uid) . "'");

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

if ($p["bio"]) {
    $bio = format($p["bio"]);
}
else {
    $bio = info("No bio to display yet.");
}

$lastactiveHTML = "<small><span class='date' title='" . date("g:i:sa", $p["lastactive"]) . "'>" . date("F jS, Y", $p["lastactive"]) . "</span></small>";

$title = htmlspecialchars($name) . "'s Profile";

$avatar = "";
$uploads = scandir("images/");
$avatars = array();
// Get all avatars.
foreach ($uploads as $u) {
    if (str_starts_with($u, "a_" . $uid . ".")) {
        $avatars[] = $u;
    }
}
// Just use the first avatar we find.
if (count($avatars) > 0) {
    $avatar = "<img src='" . makeURL("images/" . $avatars[0]) . "?" . time() . "'>";
}

$profilevars = array("color" => $p["color"],
"name" => $name,
"avatar" => $avatar,
"bio" => $bio,
"lastactive" => $lastactiveHTML,
"role" => $p["role"],
"email" => $email,
"comments" => $comments->num_rows,
"posts" => $posts->num_rows);

render_page("profile.html", $profilevars, $title);

?>
