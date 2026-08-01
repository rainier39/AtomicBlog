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

// stats.php
// Shows some statistics.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

if (!checkPerm(PERM_MANAGE_BLOG)) {
    $messages[] = error("You don't have permission to do this.");
    render_page("", array(), $title);
    exit();
}

$title = "Blog Statistics";

$totalposts = $db->query("SELECT 1 FROM `posts`");
$published = $db->query("SELECT 1 FROM `posts` WHERE `published`='1'");
$unpublished = $db->query("SELECT 1 FROM `posts` WHERE `published`='0'");
$totalaccounts = $db->query("SELECT 1 FROM `accounts`");
$totalcomments = $db->query("SELECT 1 FROM `comments`");
$totalviews = $db->query("SELECT 1 FROM `views`");
$uniqueviewers = $db->query("SELECT DISTINCT `ip` FROM `views`");

$statsVars = array("version" => $config["version"],
"totalposts" => $totalposts->num_rows,
"published" => $published->num_rows,
"unpublished" => $unpublished->num_rows,
"totalaccounts" => $totalaccounts->num_rows,
"totalcomments" => $totalcomments->num_rows,
"totalviews" => $totalviews->num_rows,
"uniqueviewers" => $uniqueviewers->num_rows);

render_page("panel/stats.html", $statsVars, $title);

?>
