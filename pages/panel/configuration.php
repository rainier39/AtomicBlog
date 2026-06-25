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

// configuration.php
// Provides an interface for conveniently changing the blog's config.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$success = false;

$timezones = array("America/Anchorage", "America/Los_Angeles", "America/Phoenix", "America/Denver", "America/Chicago", "America/New_York");
$timezonesHTML = "";
foreach ($timezones as $t) {
    if ($t == $config["timezone"]) {
        $s = " selected";
    }
    else {
        $s = "";
    }
    $timezonesHTML .= "<option value='$t'$s>$t</option>";
}

if (!checkPerm(PERM_MANAGE_BLOG)) {
    $content .= error("You don't have permission to do this.");
    render($content, $title);
    exit();
}
$title = "Blog Configuration";
// Handle requests.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // If the CSRF token is sent and valid.
    if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
        // Generate a new token.
        generateCSRFToken();
    }
}

// TODO
$content .= "<b>NOT YET FUNCTIONAL TODO</b>";

// Display the config form.
if (!$success) {
    $content .=
    "<div class='form configForm'>
        <h1>Blog Configuration</h1>
        <form method='post'>
            <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
            <label for='ctitle'>Blog Title:</label>
            <input type='text' name='ctitle' id='ctitle' value='" . htmlspecialchars($config["title"]) . "'><br>
            <label for='cdescription'>Blog Description:</label>
            <textarea id='cdescription' name='cdescription'>" . htmlspecialchars($config["description"]) . "</textarea><br>
            <label for='cfooter'>Footer:</label>
            <textarea id='cfooter' name='cfooter'>" . htmlspecialchars($config["footer"]) . "</textarea><br>
            <label for='timezone'>Server Timezone:</label>
            <select name='timezone' id='timezone'>
            " . $timezonesHTML . "
            </select>
            <h2>User Management</h2>
            <label for='registration' title='Whether or not people can create new accounts.'>Allow Registration:</label>
            <input type='checkbox' id='registration' name='registration'" . ($config["allowRegistration"] ? "checked" : "") . "><br>
            <h2>Rate Limits</h2>
            <label for='logins'>Logins Per Hour:</label>
            <input type='text' id='logins' name='logins' value='" . htmlspecialchars($config["loginsPerHour"]) . "'></br>
            <label for='accounts'>Accounts Per IP:</label>
            <input type='text' id='accounts' name='accounts' value='" . htmlspecialchars($config["accountsPerIP"]) . "'></br>
            <label for='accountcooldown'>Registration Cooldown:</label>
            <input type='text' id='accountcooldown' name='accountcooldown' value='" . htmlspecialchars($config["accountCooldown"]) . "'></br>
            <label for='postdelay'>Post Delay:</label>
            <input type='text' id='postdelay' name='postdelay' value='" . htmlspecialchars($config["postDelay"]) . "'></br>
            <label for='editdelay'>Edit Delay:</label>
            <input type='text' id='editdelay' name='editdelay' value='" . htmlspecialchars($config["editDelay"]) . "'></br>
            <br><input type='submit' value='Save changes' class='button'>
        </form>
    </div>";
}

?>
