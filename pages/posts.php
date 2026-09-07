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

$tagQuery = "";

if (isset($_GET["tag"])) {
    $tagQuery = " AND EXISTS( SELECT * FROM `tags` WHERE `tags`.`post`=p.`id` AND `tag`='" . $db->real_escape_string($_GET["tag"]) . "')";
}

// Get all of the blog posts.
$posts = $db->query("SELECT p.`id`, p.`title`, p.`account`, p.`starred`, p.`published`,a.`namevisible`,a.`name` FROM `posts` AS p LEFT JOIN `accounts` AS a ON p.`account`=a.`id` WHERE (`published`='1' OR `account`='{$id}'){$tagQuery} ORDER BY `id` DESC");

// TODO: pagination and an actual post searching/filtering system

// Only do this ONCE.
$uploads = scandir("images/");

// If there are posts, display them.
if ($posts->num_rows > 0) {
    $postsvars["posts"] .= "<div class='postTiles'>";
    // Display the posts.
    while ($p = $posts->fetch_assoc()) {
        $postsvars["posts"] .= displayPost($p);
    }
    $postsvars["posts"] .= "</div>";
    // Display a tag cloud.
    // Only keep the top 100 tags.
    $tagQuery = $db->query("SELECT `tag`, COUNT(`post`) FROM `tags` LEFT JOIN `posts` AS p ON `tags`.`post`=p.`id` WHERE (p.published='1' OR p.account='{$id}') GROUP BY `tag` ORDER BY COUNT(`post`) DESC LIMIT 100");
    $tagCloud = "<div class='tagCloud'>";
    while ($tag = $tagQuery->fetch_assoc()) {
        $tagCloud .= "<a href='" . makeURL("posts/&tag=" . urlencode(htmlspecialchars($tag["tag"]))) . "' style='font-size:" . clamp($tag["COUNT(`post`)"]+14, 14, 50) . "px'>" . htmlspecialchars($tag["tag"]) . "</a>";
    }
    $tagCloud .= "</div>";
    $postsvars["posts"] = $tagCloud . $postsvars["posts"];
}
// Otherwise print a message.
else {
    $postsvars["posts"] .= info("No posts yet.");
}

render_page("posts.html", $postsvars, $title);

?>
