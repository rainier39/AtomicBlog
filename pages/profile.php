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

if (!checkPerm(PERM_VIEW_PROFILE)) {
    $messages[] = error("You don't have permission to view profiles.");
    render_page("", array(), $title);
    exit();
}

$profileinfo = preparedQuery("SELECT `id`, `name`, `color`, `bio`, `email`, `lastactive`, `namevisible`, `emailvisible`, `role` FROM `accounts` WHERE `id`=?", array($url[1] ?? ""));
$p = $profileinfo->fetch_assoc();

if ($profileinfo->num_rows < 1) {
    http_response_code(404);
    $messages[] = error("Profile not found.");
    render_page("", array(), "Profile not found");
    exit();
}

$comments = preparedQuery("SELECT 1 FROM `comments` WHERE `account`=?", array($p["id"]));
$posts = preparedQuery("SELECT 1 FROM `posts` WHERE `account`=?", array($p["id"]));

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

$lastactiveHTML = "<small><abbr class='date' title='" . date("g:i:sa", $p["lastactive"]) . "'>" . date("F jS, Y", $p["lastactive"]) . "</abbr></small>";

$title = htmlspecialchars($name) . "'s Profile";

$avatar = "";
$uploads = scandir("images/");
$avatars = array();
// Get all avatars.
foreach ($uploads as $u) {
    if (str_starts_with($u, "a_" . $p["id"] . ".")) {
        $avatars[] = $u;
    }
}
// Just use the first avatar we find.
if (count($avatars) > 0) {
    // Get the upload time to add as a URL parameter when showing the image to avoid an old cached version being displayed by the browser.
    $uploadTime = preparedQuery("SELECT `timestamp` FROM `logs` WHERE `content`=? ORDER BY `timestamp` DESC LIMIT 1", array("images/{$avatars[0]}"));
    if ($uploadTime->num_rows > 0) {
        $ut = $uploadTime->fetch_assoc()["timestamp"];
    }
    else {
        $ut = time();
    }
    $avatar = "<img src='" . makeURL("images/" . $avatars[0]) . "?{$ut}'>";
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
