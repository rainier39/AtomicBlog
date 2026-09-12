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

// Only do this ONCE.
$uploads = scandir("images/");

// Get all of the starred blog posts.
$starred = preparedQuery("SELECT p.`id`, p.`title`, p.`account`, p.`starred`, p.`published`,a.`namevisible`,a.`name` FROM `posts` AS p LEFT JOIN `accounts` AS a ON p.`account`=a.`id` WHERE `starred`='1' AND (published='1' OR (published='0' AND account=?)) ORDER BY `id` DESC", array($id));

// Only display the posts if there are any.
if ($starred->num_rows > 0) {
    $homevars["starred"] .= "<div class='postTiles'>";
    // Display the starred posts themselves.
    while ($s = $starred->fetch_assoc()) {
        $homevars["starred"] .= displayPost($s);
    }
    // Add placeholder divs if needed to prevent one or two giant tiles.
    for ($extra = 0; $extra < 6-$starred->num_rows; $extra++) {
        $homevars["starred"] .= "<div class='postTile postTileUnpublished'></div>";
    }
    $homevars["starred"] .= "</div>";
}
// Otherwise print a message.
else {
    $homevars["starred"] .= info("No starred posts yet.");
}

// Get the 6 most recent posts.
$recent = preparedQuery("SELECT p.`id`, p.`title`, p.`account`, p.`starred`, p.`published`,a.`namevisible`,a.`name` FROM `posts` AS p LEFT JOIN `accounts` AS a ON p.`account`=a.`id` WHERE (published='1' OR (published='0' AND account=?)) ORDER BY `starttime` DESC LIMIT 6", array($id));

// Only try to display posts if there are any.
if ($recent->num_rows > 0) {
    $homevars["recent"] .= "<div class='postTiles'>";
    // Display the posts.
    while ($r = $recent->fetch_assoc()) {
        $homevars["recent"] .= displayPost($r);
    }
    // Add placeholder divs if needed to prevent one or two giant tiles.
    for ($extra = 0; $extra < 6-$recent->num_rows; $extra++) {
        $homevars["recent"] .= "<div class='postTile postTileUnpublished'></div>";
    }
    $homevars["recent"] .= "</div>";
}
// Otherwise print a message.
else {
    $homevars["recent"] .= info("No posts yet.");
}

// Get the top 6 most viewed posts.
$mostViewedPosts = preparedQuery("SELECT p.`id`,p.`title`,p.`account`,p.`starred`,p.`published`,a.`namevisible`,a.`name`,COUNT(`views`.`post`) FROM `posts` AS p LEFT JOIN `accounts` AS a ON a.`id`=p.`account` LEFT JOIN `views` ON `views`.`post`=p.`id` WHERE (published='1' OR (published='0' AND account=?)) GROUP BY p.`id` ORDER BY COUNT(`views`.`post`) DESC, p.`id` ASC LIMIT 6", array($id));

// Only try to display posts if there are any.
if ($mostViewedPosts->num_rows > 0) {
    $homevars["viewed"] .= "<div class='postTiles'>";
    // Display the posts.
    while ($m = $mostViewedPosts->fetch_assoc()) {
        $homevars["viewed"] .= displayPost($m);
    }
    // Add placeholder divs if needed to prevent one or two giant tiles.
    for ($extra = 0; $extra < 6-$mostViewedPosts->num_rows; $extra++) {
        $homevars["viewed"] .= "<div class='postTile postTileUnpublished'></div>";
    }
    $homevars["viewed"] .= "</div>";
}
// Otherwise print a message.
else {
    $homevars["viewed"] .= info("No posts yet.");
}

// Finally, render the page.
render_page("home.html", $homevars);

?>
