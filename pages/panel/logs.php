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
 "entries" => ""
);

$logsQuery = $db->query("SELECT * FROM `logs` ORDER BY `timestamp` DESC");

// If there were no log entries.
if ($logsQuery->num_rows < 1) {
    $logVars["entries"] = info("No log entries to display yet.");
}

while ($log = $logsQuery->fetch_assoc()) {
    $entry = "<div class='loginLog'>%s</div>";
    
    $date = date("F jS, Y", $log["timestamp"]);
    $time = date("g:i:sa", $log["timestamp"]);
    $ip = htmlspecialchars($log["ip"], ENT_NOQUOTES);
    $ua = parseUserAgent($log["useragent"]);
    $useragent = "<span class='date' title='" . htmlspecialchars($log["useragent"]) . "'>{$ua["os"]}, {$ua["browser"]}</span>";

    switch ($log["logtype"]) {
        // Instances in which the victimid is a username.
        // Fallthrough intentional.
        case "login_fail":
        case "login_success":
            $usernameQuery = preparedQuery("SELECT `username` FROM `accounts` WHERE `id`=?", array($log["targetid"]));
            if ($usernameQuery->num_rows < 1) {
                $username = "Deleted user";
            }
            else {
                $username = htmlspecialchars($usernameQuery->fetch_assoc()["username"], ENT_NOQUOTES);
            }
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
            $postid = $log["targetid"];
            $posttitle = htmlspecialchars($log["content"], ENT_NOQUOTES);
            $posttitle = $posttitle ?: "Untitled post";
            $post = "<a href='" . makeURL("post/{$postid}") . "'>{$posttitle}</a>";
            break;
        // Instances in which the targetid is a post, perpid is a user, and we need to get the post title.
        case "post_edit":
        case "post_togglestar":
            $usernameQuery = preparedQuery("SELECT `username` FROM `accounts` WHERE `id`=?", array($log["perpid"]));
            if ($usernameQuery->num_rows < 1) {
                $username = "Deleted user";
            }
            else {
                $username = htmlspecialchars($usernameQuery->fetch_assoc()["username"], ENT_NOQUOTES);
            }
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
    }
    switch ($log["logtype"]) {
        case "login_fail":
            $entry = str_replace("%s", "<span class='loginFail'>Failed login</span> <b>for</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "login_success":
            $entry = str_replace("%s", "<span class='loginSuccess'>Successful login</span> <b>for</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "post_delete":
            $entry = str_replace("%s", "<span>Post '{$post}' deleted</span> <b>by</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "post_edit":
            $entry = str_replace("%s", "<span>Post '{$post}' edited</span> <b>by</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        case "post_togglestar":
            $entry = str_replace("%s", "<span>Post '{$post}' star toggled</span> <b>by</b> {$username} <b>on</b> <small>{$date}</small> <b>at</b> <small>{$time}</small> <b>from</b> {$ip}, {$useragent}", $entry);
            break;
        default:
            $entry = str_replace("%s", "Type: {$log["logtype"]}, Targetid: {$log["targetid"]}, Perpetratorid: {$log["perpid"]}, Content: " . htmlspecialchars($log["content"], ENT_NOQUOTES) . ", IP: {$ip}, Useragent: {$useragent}, Timestamp: {$log["timestamp"]}", $entry);
    }

    $logVars["entries"] .= $entry;
}

render_page("panel/logs.html", $logVars, $title);

?>
