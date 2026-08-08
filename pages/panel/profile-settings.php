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

// profile-settings.php
// Allows a user to edit their profile.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// No need for a permission check, any user who can log in should be able to perform all actions on this page.

$title = "Profile Settings";

// Handle requests.
if (isset($_POST["csrf_token"]) and ($_POST["csrf_token"] == $_SESSION["csrf_token"])) {
    // Generate a new token.
    generateCSRFToken();
    
    // Get the information for this account.
    $accountInfo = $db->query("SELECT `color`, `bio`, `namevisible`, `emailvisible` FROM `accounts` WHERE `id`='" . $_SESSION["id"] . "'");

    $a = $accountInfo->fetch_assoc();
    
    $errors = array();
    
    $color = "";
    $bio = "";
    $colorChanged = false;
    $bioChanged = false;
    
    if (isset($_POST["color"])) {
        $colorChanged = true;
        // Remove the hash sign.
        $color = substr($_POST["color"], 1);
        
        // Must be 6...
        if (strlen($color) != 6) {
            $errors[] = error("Invalid color.");
        }
        // Hex digits.
        elseif (!ctype_xdigit($color)) {
            $errors[] = error("Invalid color.");
        }
        // Don't change it if it's already the same.
        elseif ($color == $a["color"]) {
            $colorChanged = false;
        }
    }
    if (isset($_POST["bio"])) {
        $bioChanged = true;
        $bio = $_POST["bio"];
        
        if (strlen($bio) < 1) {
            $errors[] = error("Bio cannot be blank.");
        }
        elseif (strlen($bio) > 4096) {
            $errors[] = error("Bio cannot be longer than 4,096 characters.");
        }
        // Don't change it if it's already the same.
        elseif ($bio == $a["bio"]) {
            $bioChanged = false;
        }
    }
    if (isset($_POST["namevisible"]) and ($_POST["namevisible"] == "on")) {
        $nv = "1";
    }
    else {
        $nv = "0";
    }
    if (isset($_POST["emailvisible"]) and ($_POST["emailvisible"] == "on")) {
        $ev = "1";
    }
    else {
        $ev = "0";
    }
    
    if (count($errors) !== 0) {
        foreach ($errors as $error) {
            $messages[] = $error;
        }
    }
    // If something has actually changed...
    elseif ($bioChanged or $colorChanged or ($nv != $a["namevisible"]) or ($ev != $a["emailvisible"])) {
        $db->query("UPDATE `accounts` SET `bio`='" . $db->real_escape_string($bio) . "', `color`='" . $db->real_escape_string($color) . "', `namevisible`='" . $nv . "', `emailvisible`='" . $ev . "' WHERE `id`='" . $_SESSION["id"] . "'");
        $messages[] = success("Successfully updated profile settings.");
    }
    else {
        $messages[] = info("Nothing to change.");
    }
}

// Get the information for this account.
$accountInfo = $db->query("SELECT `color`, `bio`, `namevisible`, `emailvisible` FROM `accounts` WHERE `id`='" . $_SESSION["id"] . "'");

$a = $accountInfo->fetch_assoc();

$settingsVars = array("token" => $_SESSION["csrf_token"],
"color" => $_POST["color"] ?? "#" . $a["color"],
"bio" => $_POST["bio"] ?? $a["bio"],
"namevisible" => $a["namevisible"] ? " checked" : "",
"emailvisible" => $a["emailvisible"] ? " checked" : "");

render_page("panel/profile-settings.html", $settingsVars, $title);

?>
