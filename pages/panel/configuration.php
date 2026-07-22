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

if (!checkPerm(PERM_MANAGE_BLOG)) {
    $messages[] = error("You don't have permission to do this.");
    render_page("", array(), $title);
    exit();
}

$title = "Blog Configuration";

// Timezone stuff.
$timezones = array("America/Anchorage", "America/Los_Angeles", "America/Phoenix", "America/Denver", "America/Chicago", "America/New_York");
$timezonesHTML = "";
// Use the user-supplied timezone if it's valid, otherwise default to the config.
if (isset($_POST["timezone"]) and in_array($_POST["timezone"], $timezones)) {
    $currentTimezone = $_POST["timezone"];
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

// Language stuff.
$languageHTML = "";
// Use the user-supplied language if it's valid, otherwise default to the config.
if (isset($_POST["clanguage"]) and in_array($_POST["clanguage"], $languages)) {
    $currentLanguage = $_POST["clanguage"];
}
else {
    $currentLanguage = $language;
}
foreach ($languages as $l) {
    if ($l == $currentLanguage) {
        $s = " selected";
    }
    else {
        $s = "";
    }
    $languageHTML .= "<option value='$l'$s>$l</option>";
}

// Theme stuff.
$themeHTML = "";
// Use the user-supplied language if it's valid, otherwise default to the config.
if (isset($_POST["theme"]) and in_array($_POST["theme"], $themes)) {
    $currentTheme = $_POST["theme"];
}
else {
    $currentTheme = $config["theme"];
}
foreach ($themes as $t) {
    if ($t == $currentTheme) {
        $s = " selected";
    }
    else {
        $s = "";
    }
    $themeHTML .= "<option value='$t'$s>$t</option>";
}

// Registration mode stuff.
$registerHTML = "";
$modes = array("approval", "open");
// Use the user-supplied mode if it's valid, otherwise default to the config.
if (isset($_POST["registrationMode"]) and in_array($_POST["registrationMode"], $modes)) {
    $currentMode = $_POST["registrationMode"];
}
else {
    $currentMode = $config["registrationMode"];
}
foreach ($modes as $m) {
    if ($m == $currentMode) {
        $s = " selected";
    }
    else {
        $s = "";
    }
    $registerHTML .= "<option value='$m'$s>$m</option>";
}

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
        if (isset($_POST["clanguage"])) {
            if (!in_array($_POST["clanguage"], $languages)) {
                $errors[] = "Invalid language.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["clanguage"] != $config["language"]) {
                $config["language"] = $_POST["clanguage"];
                $language = $_POST["clanguage"];
                updateLang();
                $changes++;
            }
        }
        if (isset($_POST["theme"])) {
            if (!in_array($_POST["theme"], $themes)) {
                $errors[] = "Invalid theme.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["theme"] != $config["theme"]) {
                $config["theme"] = $_POST["theme"];
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
        if (isset($_POST["registrationMode"])) {
            if (!in_array($_POST["registrationMode"], $modes)) {
                $errors[] = "Invalid registration mode.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["registrationMode"] != $config["registrationMode"]) {
                $config["registrationMode"] = $_POST["registrationMode"];
                $changes++;
            }
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
                $messages[] = error($e);
            }
        }
        // Display a message if we changed the config.
        if ($changes > 0) {
            flushConfig();
            $messages[] = success("Successfully updated the blog configuration.");
        }
        else {
            $messages[] = success("Successfully did nothing.");
        }
    }
}

// Display the config form.
$configvars = array("token" => $_SESSION["csrf_token"],
"title" => $_POST["ctitle"] ?? $config["title"],
"description" => $_POST["cdescription"] ?? $config["description"],
"footer" => $_POST["cfooter"] ?? $config["footer"],
"timezone" => $timezonesHTML,
"language" => $languageHTML,
"theme" => $themeHTML,
"allowregistration" => $config["allowRegistration"] ? " checked" : "",
"registrationmode" => $registerHTML,
"loginsperhour" => $_POST["logins"] ?? $config["loginsPerHour"],
"accountsperip" => $_POST["accounts"] ?? $config["accountsPerIP"],
"accountcooldown" => $_POST["accountcooldown"] ?? $config["accountCooldown"],
"postdelay" => $_POST["postdelay"] ?? $config["postDelay"],
"editdelay" => $_POST["editdelay"] ?? $config["editDelay"]);

render_page("panel/configuration.html", $configvars, $title);

?>
