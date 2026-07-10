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

// posts.php
// Displays the blog posts.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$title = lang("global.posts");

$postsvars = array("posts" => "");

if (!checkPerm(PERM_VIEW_POSTS)) {
    $messages[] = error("You don't have permission to view posts.");
    render_page("", $postsvars, $title);
    exit();
}

$id = $_SESSION["id"] ?? 0;

// Get all of the blog posts.
$posts = $db->query("SELECT `id`, `title`, `account` FROM `posts` WHERE (published='1' OR (published='0' AND account='" . $id . "')) ORDER BY `id` DESC");

// If there are posts, display them.
if ($posts->num_rows > 0) {
    $postsvars["posts"] .= "<table class='postsTable'><tbody>";
    $counter = 0;
    $total = 0;
    // Display the posts.
    while ($p = $posts->fetch_assoc()) {
        if ($counter == 0) {
            $postsvars["posts"] .= "<tr>";
        }
        if ($counter == 5) {
            $postsvars["posts"] .= "</tr><tr>";
            $counter = 0;
        }
        $postsvars["posts"] .= displayPost($p["id"], $p["title"], $p["account"]);
        $counter++;
        $total++;
    }
    while (($total > 0) and ($total % 5)) {
        $postsvars["posts"] .= "<td class='dummyTile'></td>";
        $total++;
        if (!$total % 5) {
            $postsvars["posts"] .= "</tr>";
        }
    }
    $postsvars["posts"] .= "</tbody></table>";
}
// Otherwise print a message.
else {
    $postsvars["posts"] .= info("No posts yet.");
}

render_page("posts.html", $postsvars, $title);

?>

