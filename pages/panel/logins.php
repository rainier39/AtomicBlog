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

// logins.php
// Shows recent logins and login attempts.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// No permission check needed.

$title = "Login Logs";

$loginsVars = array("logins" => "");

$attempts = $db->query("SELECT `logtype`, `ip`, `useragent`, `timestamp` FROM `logs` WHERE (`logtype`='login_fail' OR `logtype`='login_success') AND `targetid`='" . $_SESSION["id"] . "' ORDER BY `timestamp` DESC");

while ($a = $attempts->fetch_assoc()) {
    if ($a["logtype"] == "login_success") {
        $type = "<span class='loginSuccess'>Successful login</span>";
    }
    else {
        $type = "<span class='loginFail'>Failed login</span>";
    }
    $date = date("F jS, Y", $a["timestamp"]);
    $time = date("g:i:sa", $a["timestamp"]);
    $ip = htmlspecialchars(redactIP($a["ip"]));
    $ua = parseUserAgent($a["useragent"]);
    $ua = "<span class='date' title='" . htmlspecialchars($a["useragent"]) . "'>{$ua["os"]}, {$ua["browser"]}</span>";
    $loginsVars["logins"] .= "<div class='loginLog'>$type on <small>$date</small> at <small>$time</small> from <b>$ip</b>, <i>$ua</i></div>";
}

// If there are no login attempts.
if ($loginsVars["logins"] == "") {
    $loginsVars["logins"] = info("No logins to display.");
}

render_page("panel/logins.html", $loginsVars, $title);

?>
