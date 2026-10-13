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

// logs.php
// Displays the blog's logs.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

if (!checkPerm(PERM_MANAGE_BLOG)) {
    $messages[] = error("error.noviewpage");
    render_page("", array(), $title);
    exit();
}

$title = "View logs";

$logVars = array(
 "entries" => "",
 "count" => "",
 "pagination" => ""
);

$itemsPerPage = 25;
$logcount = $db->query("SELECT COUNT(*) FROM `logs`");
$lc = $logcount->fetch_assoc()["COUNT(*)"];
$pages = ceil($lc/$itemsPerPage);

// Figure out what page we're on.
if (isset($url[2])) {
    $page = clamp((float)$url[2], 1, $pages);
}
// Default to first page.
else {
    $page = 1;
}

// Calculate the offset.
$offset = ($page - 1) * $itemsPerPage;

$logsQuery = preparedQuery("SELECT * FROM `logs` ORDER BY `timestamp` DESC LIMIT ? OFFSET ?", array($itemsPerPage, $offset));
$logVars["count"] = $lc;
// If there were no log entries.
if ($logVars["count"] < 1) {
    $logVars["entries"] = info("No log entries to display yet.");
}
if ($logVars["count"] == 1) {
    $logVars["count"] .= " log entry";
}
else {
    $logVars["count"] .= " log entries";
}

while ($log = $logsQuery->fetch_assoc()) {
    $entry = "<div class='logEntry'>%s</div>";
    
    $date = date("F jS, Y", $log["timestamp"]);
    $time = date("g:i:sa", $log["timestamp"]);
    $ip = htmlspecialchars($log["ip"], ENT_NOQUOTES);
    $ua = parseUserAgent($log["useragent"]);
    $useragent = "<span class='date' title='" . htmlspecialchars($log["useragent"]) . "'>{$ua["os"]}, {$ua["browser"]}</span>";

    switch ($log["logtype"]) {
        // Instances in which the targetid is a username.
        // Fallthrough intentional.
        case "login_fail":
        case "login_success":
        case "registration":
            $usernameQuery = preparedQuery("SELECT `username` FROM `accounts` WHERE `id`=?", array($log["targetid"]));
            if ($usernameQuery->num_rows < 1) {
                $username = "Deleted user";
            }
            else {
                $username = htmlspecialchars($usernameQuery->fetch_assoc()["username"], ENT_NOQUOTES);
            }
            $username = "<a href='" . makeURL("profile/{$log["targetid"]}") . "'>{$username}</a>";
            break;
        // Instances in which perpid is a username.
        // Fallthrough intentional.
        case "panel_change_password":
        case "panel_change_username":
        case "panel_blogconfig":
            $usernameQuery = preparedQuery("SELECT `username` FROM `accounts` WHERE `id`=?", array($log["perpid"]));
            if ($usernameQuery->num_rows < 1) {
                $username = "Deleted user";
            }
            else {
                $username = htmlspecialchars($usernameQuery->fetch_assoc()["username"], ENT_NOQUOTES);
            }
            $username = "<a href='" . makeURL("profile/{$log["perpid"]}") . "'>{$username}</a>";
            break;
        // Instances in which perpid is a username and content is an image filepath.
        // Fallthrough intentional.
        case "image_delete":
        case "image_upload":
            $usernameQuery = preparedQuery("SELECT `username` FROM `accounts` WHERE `id`=?", array($log["perpid"]));
            if ($usernameQuery->num_rows < 1) {
                $username = "Deleted user";
            }
            else {
                $username = htmlspecialchars($usernameQuery->fetch_assoc()["username"], ENT_NOQUOTES);
            }
            $username = "<a href='" . makeURL("profile/{$log["perpid"]}") . "'>{$username}</a>";
            $content = htmlspecialchars($log["content"], ENT_NOQUOTES);
            break;
        // Instances in which the targetid is a post, perpid is a user, content is post title.
        case "post_delete":
            $usernameQuery = preparedQuery("SELECT `username` FROM `accounts` WHERE `id`=?", array($log["perpid"]));
            if ($usernameQuery->num_rows < 1) {
                $username = "Deleted user";
            }
            else {
                $username = htmlspecialchars($usernameQuery->fetch_assoc()["username"], ENT_NOQUOTES);
            }
            $username = "<a href='" . makeURL("profile/{$log["perpid"]}") . "'>{$username}</a>";
            $postid = $log["targetid"];
            $posttitle = htmlspecialchars($log["content"], ENT_NOQUOTES);
            $posttitle = $posttitle ?: "Untitled post";
            $post = "<a href='" . makeURL("post/{$postid}") . "'>{$posttitle}</a>";
            break;
        // Instances in which the targetid is a post, perpid is a user, content is post title.
        // Fallthrough intentional.
        case "comment_edit":
        case "comment_delete":
            $usernameQuery = preparedQuery("SELECT `username` FROM `accounts` WHERE `id`=?", array($log["perpid"]));
            if ($usernameQuery->num_rows < 1) {
                $username = "Deleted user";
            }
            else {
                $username = htmlspecialchars($usernameQuery->fetch_assoc()["username"], ENT_NOQUOTES);
            }
            $username = "<a href='" . makeURL("profile/{$log["perpid"]}") . "'>{$username}</a>";
            $commentid = $log["targetid"];
            break;
        // Instances in which the targetid is a post, perpid is a user, and we need to get the post title.
        // Fallthrough intentional.
        case "post_edit":
        case "post_togglestar":
            $usernameQuery = preparedQuery("SELECT `username` FROM `accounts` WHERE `id`=?", array($log["perpid"]));
            if ($usernameQuery->num_rows < 1) {
                $username = "Deleted user";
            }
            else {
                $username = htmlspecialchars($usernameQuery->fetch_assoc()["username"], ENT_NOQUOTES);
            }
            $username = "<a href='" . makeURL("profile/{$log["perpid"]}") . "'>{$username}</a>";
            $postid = $log["targetid"];
            $titleQuery = preparedQuery("SELECT `title` FROM `posts` WHERE `id`=?", array($postid));
            if ($titleQuery->num_rows < 1) {
                $title = "Deleted post";
            }
            else {
                $title = htmlspecialchars($titleQuery->fetch_assoc()["title"], ENT_NOQUOTES);
            }
            $post = "<a href='" . makeURL("post/{$postid}") . "'>{$title}</a>";
            break;
        // Instances in which target and perp are users.
        case "change_role":
            $perpQuery = preparedQuery("SELECT `username` FROM `accounts` WHERE `id`=?", array($log["perpid"]));
            $targetQuery = preparedQuery("SELECT `username` FROM `accounts` WHERE `id`=?", array($log["targetid"]));
            if ($perpQuery->num_rows < 1) {
                $perp = "Deleted user";
            }
            else {
                $perp = htmlspecialchars($perpQuery->fetch_assoc()["username"], ENT_NOQUOTES);
            }
            $perp = "<a href='" . makeURL("profile/{$log["perpid"]}") . "'>{$perp}</a>";
            if ($targetQuery->num_rows < 1) {
                $target = "Deleted user";
            }
            else {
                $target = htmlspecialchars($targetQuery->fetch_assoc()["username"], ENT_NOQUOTES);
            }
            $target = "<a href='" . makeURL("profile/{$log["targetid"]}") . "'>{$target}</a>";
            break;
    }
    switch ($log["logtype"]) {
        case "login_fail":
            $entry = str_replace("%s", "<span class='loginFail'>Failed login</span> <b>for</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "login_success":
            $entry = str_replace("%s", "<span class='loginSuccess'>Successful login</span> <b>for</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "registration":
            $entry = str_replace("%s", "<span>Account registered</span> <b>named</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "panel_change_password":
            $entry = str_replace("%s", "<span>Password changed</span> <b>for</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "panel_change_username":
            $entry = str_replace("%s", "<span>Username changed</span> <b>for</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "panel_blogconfig":
            $entry = str_replace("%s", "<span>Blog configuration changed</span> <b>by</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "image_delete":
            $entry = str_replace("%s", "<span>Image '{$content}' deleted</span> <b>by</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "image_upload":
            $entry = str_replace("%s", "<span>Image '{$content}' uploaded</span> <b>by</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "post_delete":
            $entry = str_replace("%s", "<span>Post '{$post}' deleted</span> <b>by</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "comment_edit":
            $entry = str_replace("%s", "<span>Comment edited</span> <b>by</b> {$username} <b>with id</b> {$commentid} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "comment_delete":
            $entry = str_replace("%s", "<span>Comment deleted</span> <b>by</b> {$username} <b>with id</b> {$commentid} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "post_edit":
            $entry = str_replace("%s", "<span>Post '{$post}' edited</span> <b>by</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "post_togglestar":
            $entry = str_replace("%s", "<span>Post '{$post}' star toggled</span> <b>by</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "change_role":
            $rolechange = explode("|", $log["content"]);
            // @ signs here in case the array key doesn't exist.
            $from = htmlspecialchars(@$rolechange[0] ?: "Unknown", ENT_NOQUOTES);
            $to = htmlspecialchars(@$rolechange[1] ?: "Unknown", ENT_NOQUOTES);
            $entry = str_replace("%s", "<span>Role changed</span> <b>for</b> {$target} <b>by</b> {$perp} <b>to</b> {$to} <b>from</b> {$from} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        default:
            $entry = str_replace("%s", "Type: {$log["logtype"]}, Targetid: {$log["targetid"]}, Perpetratorid: {$log["perpid"]}, Content: " . htmlspecialchars($log["content"], ENT_NOQUOTES) . ", IP: {$ip}, Useragent: {$useragent}, Timestamp: {$log["timestamp"]}", $entry);
    }

    $logVars["entries"] .= $entry;
}

// Generate the pagination.
$logVars["pagination"] = generatePagination($pages, $page, "panel/logs");

render_page("panel/logs.html", $logVars, $title);

?>
