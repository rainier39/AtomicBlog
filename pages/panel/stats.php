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

$totalposts = $db->query("SELECT COUNT(*) FROM `posts`")->fetch_assoc()["COUNT(*)"];
$published = $db->query("SELECT COUNT(*) FROM `posts` WHERE `published`='1'")->fetch_assoc()["COUNT(*)"];
$unpublished = $db->query("SELECT COUNT(*) FROM `posts` WHERE `published`='0'")->fetch_assoc()["COUNT(*)"];
$totalaccounts = $db->query("SELECT COUNT(*) FROM `accounts`")->fetch_assoc()["COUNT(*)"];
$totalcomments = $db->query("SELECT COUNT(*) FROM `comments`")->fetch_assoc()["COUNT(*)"];
$totalviews = $db->query("SELECT COUNT(*) FROM `views`")->fetch_assoc()["COUNT(*)"];
$uniqueviewers = $db->query("SELECT COUNT(DISTINCT `ip`) FROM `views`")->fetch_assoc()["COUNT(DISTINCT `ip`)"];
$logentries = $db->query("SELECT COUNT(*) FROM `logs`")->fetch_assoc()["COUNT(*)"];

$statsVars = array(
 "version" => $config["version"],
 "totalposts" => $totalposts,
 "published" => $published,
 "unpublished" => $unpublished,
 "totalaccounts" => $totalaccounts,
 "totalcomments" => $totalcomments,
 "totalviews" => $totalviews,
 "uniqueviewers" => $uniqueviewers,
 "logentries" => $logentries
);

render_page("panel/stats.html", $statsVars, $title);

?>
