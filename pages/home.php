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

// home.php
// Displays the homepage.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$homevars = array("starred" => "",
"recent" => "",
"viewed" => "");

if (!checkPerm(PERM_VIEW_POSTS)) {
    $messages[] = error("You don't have permission to view posts.");
    render_page("", array());
    exit();
}

$id = $_SESSION["id"] ?? 0;

// Get all of the starred blog posts.
$starred = $db->query("SELECT `id`, `title`, `account` FROM `posts` WHERE `starred`='1' AND (published='1' OR (published='0' AND account='" . $id . "')) ORDER BY `id` DESC");

// Only display the posts if there are any.
if ($starred->num_rows > 0) {
    $homevars["starred"] .= "<table class='postsTable'><tbody>";
    $counter = 0;
    $total = 0;
    // Display the starred posts themselves.
    while ($s = $starred->fetch_assoc()) {
        if ($counter == 0) {
            $homevars["starred"] .= "<tr>";
        }
        if ($counter == 5) {
            $homevars["starred"] .= "</tr><tr>";
            $counter = 0;
        }
        $homevars["starred"] .= displayPost($s["id"], $s["title"], $s["account"]);
        $counter++;
        $total++;
    }
    while (($total > 0) and ($total % 5)) {
        $homevars["starred"] .= "<td class='dummyTile'></td>";
        $total++;
        if (!$total % 5) {
            $homevars["starred"] .= "</tr>";
        }
    }
    $homevars["starred"] .= "</tbody></table>";
}
// Otherwise print a message.
else {
    $homevars["starred"] .= info("No starred posts yet.");
}

// Get the 5 most recent posts.
$recent = $db->query("SELECT `id`, `title`, `account` FROM `posts` WHERE (published='1' OR (published='0' AND account='" . $id . "')) ORDER BY `starttime` DESC LIMIT 5");

// Only try to display posts if there are any.
if ($recent->num_rows > 0) {
    $homevars["recent"] .= "<table class='postsTable'><tbody><tr>";
    $total = 0;
    // Display the posts.
    while ($r = $recent->fetch_assoc()) {
        $homevars["recent"] .= displayPost($r["id"], $r["title"], $r["account"]);
        $total++;
    }
    while (($total > 0) and ($total % 5)) {
        $homevars["recent"] .= "<td class='dummyTile'></td>";
        $total++;
        if (!$total % 5) {
            $homevars["recent"] .= "</tr>";
        }
    }
    $homevars["recent"] .= "</tr></tbody></table>";
}
// Otherwise print a message.
else {
    $homevars["recent"] .= info("No posts yet.");
}

// Create an array to store every postid and later how many views each one has.
$views = array();

// Get all of the views, and just get their post ids.
$posts = $db->query("SELECT `post` FROM `views` ORDER BY `post` DESC");

// Fill the array with the proper amount of views per post.
while ($p = $posts->fetch_assoc()) {
    if (array_key_exists($p["post"], $views)) {
        $views[$p["post"]] += 1;
    }
    else {
        $views[$p["post"]] = 1;
    }
}

// Make a second array consisting of the 5 highest viewed posts, namely their ids.
$mostViewed = array();

// Populate the array.
for ($i = 0; (($i < 5) and (count($views) != 0)); $i++) {
    // Find the highest viewed post.
    $value = max($views);

    // Get its key.
    $key = array_search($value, $views);

    // Remove it from the array.
    unset($views[$key]);

    // Add the postid to the new array.
    $mostViewed[$key] = $value;
}

// Only try to display posts if there are any.
if (count($mostViewed) > 0) {
    $homevars["viewed"] .= "<table class='postsTable'><tbody><tr>";
    $total = 0;
    foreach ($mostViewed as $mv=>$views) {
        // Get the posts we wish to display.
        $mostViewedPosts = $db->query("SELECT `id`, `title`, `account` FROM `posts` WHERE `id`='" . $db->real_escape_string($mv) . "'");

        // Display the posts.
        while ($m = $mostViewedPosts->fetch_assoc()) {
            $homevars["viewed"] .= displayPost($m["id"], $m["title"], $m["account"]);
            $total++;
        }
    }
    while (($total > 0) and ($total % 5)) {
        $homevars["viewed"] .= "<td class='dummyTile'></td>";
        $total++;
        if (!$total % 5) {
            $homevars["viewed"] .= "</tr>";
        }
    }
    $homevars["viewed"] .= "</tr></tbody></table>";
}
// Otherwise print a message.
else {
    $homevars["viewed"] .= info("No posts yet.");
}

// Finally, render the page.
render_page("home.html", $homevars);

?>
