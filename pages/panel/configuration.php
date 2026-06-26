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
if (isset($_POST["timezone"])) {
    // Use the user-supplied timezone if it's valid, otherwise default to the config.
    $currentTimezone = (in_array($_POST["timezone"], $timezones) ? $_POST["timezone"] : null) ?? $config["timezone"];
}
else {
    $currentTimezone = $config["timezone"];
}
foreach ($timezones as $t) {
    if ($t == $currentTimezone) {
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
        
        $errors = array();
        $changes = 0;
        
        if (isset($_POST["ctitle"])) {
            if (strlen($_POST["ctitle"]) < 1) {
                $errors[] = "Title cannot be blank.";
            }
            elseif (strlen($_POST["ctitle"]) > 32) {
                $errors[] = "Title cannot be longer than 32 characters.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["ctitle"] != $config["title"]) {
                $config["title"] = $_POST["ctitle"];
                $changes++;
            }
        }
        if (isset($_POST["cdescription"])) {
            if (strlen($_POST["cdescription"]) < 1) {
                $errors[] = "Description cannot be blank.";
            }
            elseif (strlen($_POST["cdescription"]) > 128) {
                $errors[] = "Description cannot be longer than 128 characters.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["cdescription"] != $config["description"]) {
                $config["description"] = $_POST["cdescription"];
                $changes++;
            }
        }
        if (isset($_POST["cfooter"])) {
            if (strlen($_POST["cfooter"]) < 1) {
                $errors[] = "Footer cannot be blank.";
            }
            elseif (strlen($_POST["cfooter"]) > 512) {
                $errors[] = "Footer cannot be longer than 512 characters.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["cfooter"] != $config["footer"]) {
                $config["footer"] = $_POST["cfooter"];
                $changes++;
            }
        }
        if (isset($_POST["timezone"])) {
            if (!in_array($_POST["timezone"], $timezones)) {
                $errors[] = "Invalid timezone.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["timezone"] != $config["timezone"]) {
                $config["timezone"] = $_POST["timezone"];
                $changes++;
            }
        }
        if (isset($_POST["registration"]) and ($_POST["registration"] == "on")) {
            $tz = true;
        }
        else {
            $tz = false;
        }
        // Only write to the config if the value is actually being changed.
        if ($tz != $config["allowRegistration"]) {
            $config["allowRegistration"] = $tz;
            $changes++;
        }
        if (isset($_POST["logins"])) {
            $logins = (int)$_POST["logins"];
            if ($logins < 1) {
                $errors[] = "Logins per hour cannot be less than 1.";
            }
            elseif ($logins > 32767) {
                $errors[] = "Logins per hour cannot be greater than 32,767.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($logins != $config["loginsPerHour"]) {
                $config["loginsPerHour"] = $logins;
                $changes++;
            }
        }
        if (isset($_POST["accounts"])) {
            $accounts = (int)$_POST["accounts"];
            if ($accounts < 1) {
                $errors[] = "Accounts per IP cannot be less than 1.";
            }
            elseif ($accounts > 32767) {
                $errors[] = "Accounts per IP cannot be greater than 32,767.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($accounts != $config["accountsPerIP"]) {
                $config["accountsPerIP"] = $accounts;
                $changes++;
            }
        }
        if (isset($_POST["accountcooldown"])) {
            $accountcooldown = (int)$_POST["accountcooldown"];
            if ($accountcooldown < 1) {
                $errors[] = "Account cooldown cannot be less than 1.";
            }
            elseif ($accountcooldown > 32767) {
                $errors[] = "Account cooldown cannot be greater than 32,767.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($accountcooldown != $config["accountCooldown"]) {
                $config["accountCooldown"] = $accountcooldown;
                $changes++;
            }
        }
        if (isset($_POST["postdelay"])) {
            $postdelay = (int)$_POST["postdelay"];
            if ($postdelay < 1) {
                $errors[] = "Post delay cannot be less than 1.";
            }
            elseif ($postdelay > 32767) {
                $errors[] = "Post delay cannot be greater than 32,767.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($postdelay != $config["postDelay"]) {
                $config["postDelay"] = $postdelay;
                $changes++;
            }
        }
        if (isset($_POST["editdelay"])) {
            $editdelay = (int)$_POST["editdelay"];
            if ($editdelay < 1) {
                $errors[] = "Edit delay cannot be less than 1.";
            }
            elseif ($editdelay > 32767) {
                $errors[] = "Edit delay cannot be greater than 32,767.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($editdelay != $config["editDelay"]) {
                $config["editDelay"] = $editdelay;
                $changes++;
            }
        }
        
        // If there are errors, display them.
        if (count($errors) > 0) {
            foreach ($errors as $e) {
                $content .= error($e);
            }
        }
        // Display a message if we changed the config.
        if ($changes > 0) {
            flushConfig();
            $content .= success("Successfully updated the blog configuration.");
        }
        else {
            $content .= success("Successfully did nothing.");
        }
    }
}

// Display the config form.
$content .=
    "<div class='form configForm'>
        <h1>Blog Configuration</h1>
        <form method='post'>
            <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
            <label for='ctitle'>Blog Title:</label>
            <input type='text' name='ctitle' id='ctitle' value='" . htmlspecialchars($_POST["ctitle"] ?? $config["title"]) . "'><br>
            <label for='cdescription'>Blog Description:</label>
            <textarea id='cdescription' name='cdescription'>" . htmlspecialchars($_POST["cdescription"] ?? $config["description"]) . "</textarea><br>
            <label for='cfooter'>Footer:</label>
            <textarea id='cfooter' name='cfooter'>" . htmlspecialchars($_POST["cfooter"] ?? $config["footer"]) . "</textarea><br>
            <label for='timezone'>Server Timezone:</label>
            <select name='timezone' id='timezone'>
            " . $timezonesHTML . "
            </select>
            <h2>User Management</h2>
            <label for='registration' title='Whether or not people can create new accounts.'>Allow Registration:</label>
            <input type='checkbox' id='registration' name='registration'" . ($config["allowRegistration"] ? "checked" : "") . "><br>
            <h2>Rate Limits</h2>
            <label for='logins' title='Allowed logins/login attempts per hour from an IP.'>Logins Per Hour:</label>
            <input type='text' id='logins' name='logins' value='" . htmlspecialchars($_POST["logins"] ?? $config["loginsPerHour"]) . "'></br>
            <label for='accounts' title='Maximum number of accounts that can be created per IP.'>Accounts Per IP:</label>
            <input type='text' id='accounts' name='accounts' value='" . htmlspecialchars($_POST["accounts"] ?? $config["accountsPerIP"]) . "'></br>
            <label for='accountcooldown' title='Time (in seconds) one must wait in between creating accounts.'>Registration Cooldown:</label>
            <input type='text' id='accountcooldown' name='accountcooldown' value='" . htmlspecialchars($_POST["accountcooldown"] ?? $config["accountCooldown"]) . "'></br>
            <label for='postdelay' title='Time (in seconds) one must wait in between making blog posts.'>Post Delay:</label>
            <input type='text' id='postdelay' name='postdelay' value='" . htmlspecialchars($_POST["postdelay"] ?? $config["postDelay"]) . "'></br>
            <label for='editdelay' title='Time (in seconds) one must wait in between making edits to blog posts.'>Edit Delay:</label>
            <input type='text' id='editdelay' name='editdelay' value='" . htmlspecialchars($_POST["editdelay"] ?? $config["editDelay"]) . "'></br>
            <br><input type='submit' value='Save changes' class='button'>
        </form>
    </div>";

?>
