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

$content = "";
$title = "Posts";

if (!checkPerm(PERM_VIEW_POSTS)) {
    $content .= "<div class='error'>You don't have permission to view posts.</div>";
    render($content, $title);
    exit();
}

// Get all of the blog posts.
$posts = $db->query("SELECT `id`, `icon`, `title`, `account` FROM `posts`");

// Display the allPosts fieldset.
$content .= "<fieldset class='posts'><legend>All Posts</legend>";

// If there are posts, display them.
if ($posts->num_rows > 0) {
    // Display the posts.
    while ($p = $posts->fetch_assoc()) {
        $content .= displayPost($p["id"], $p["icon"], $p["title"], $p["account"]);
    }
}
// Otherwise print a message.
else {
    $content .= "<div class='info'>No posts yet.</div>";
}

// End the fieldset.
$content .= "</fieldset>";

render($content, $title);

?>

