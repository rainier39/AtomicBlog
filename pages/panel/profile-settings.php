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
if (validateCSRFToken()) {
    if (isset($_POST["color"]) or isset($_POST["bio"]) or isset($_POST["namevisible"]) or isset($_POST["emailvisible"])) {
        // Get the information for this account.
        $accountInfo = preparedQuery("SELECT `name`, `color`, `bio`, `namevisible`, `emailvisible` FROM `accounts` WHERE `id`=?", array($_SESSION["id"]));

        $a = $accountInfo->fetch_assoc();
        
        $errors = array();
        
        $nameChanged = false;
        $color = "";
        $colorChanged = false;
        $bioChanged = false;
        
        if (isset($_POST["name"])) {
            // Validate their name.
            if ($_POST["name"] != $a["name"]) {
                $nameChanged = true;
                $errors = array_merge($errors, validateName($_POST["name"]));
            }
        }
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
            
            if (strlen($_POST["bio"]) > 4096) {
                $errors[] = error("Bio cannot be longer than 4,096 characters.");
            }
            // Don't change it if it's already the same.
            elseif ($_POST["bio"] == $a["bio"]) {
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
        elseif ($nameChanged or $bioChanged or $colorChanged or ($nv != $a["namevisible"]) or ($ev != $a["emailvisible"])) {
            preparedQuery("UPDATE `accounts` SET `name`=?, `bio`=?, `color`=?, `namevisible`=?, `emailvisible`=? WHERE `id`=?", array($_POST["name"], $_POST["bio"], $color, $nv, $ev, $_SESSION["id"]));
            $messages[] = success("Successfully updated profile settings.");
        }
        else {
            $messages[] = info("Nothing to change.");
        }
    }
    // Handle uploading an avatar.
    elseif (isset($_FILES["avatar"])) {
        $upload = upload("avatar", "a_" . $_SESSION["id"]);
        if ($upload == "") {
            $messages[] = success("Successfully uploaded avatar.");
        }
        else {
            $messages[] = error($upload);
        }
    }
    // Handle deleting an avatar.
    elseif (isset($_POST["deleteAvatar"]) and isset($_POST["davatar"])) {
        // Filepath sanitization.
        $target = basename($_POST["davatar"]);
        // Make sure that this avatar actually belongs to this user.
        if (!str_starts_with($target, "a_" . $_SESSION["id"])) {
            $messages[] = error("Nice try.");
        }
        // Make sure that the target attachment exists.
        elseif (!is_file("images/" . $target)) {
            $messages[] = error("Specified avatar doesn't exist.");
        }
        else {
            $size = filesize("images/" . $target);
            $deleted = unlink("images/" . $target);
            if ($deleted) {
                // Subtract from the quota, ensuring it never drops below zero.
                preparedQuery("UPDATE `accounts` SET `quota`=GREATEST(`quota`-?, 0) WHERE `id`=?", array($size, $_SESSION["id"]));
                // Log the deletion.
                preparedQuery("INSERT INTO `logs` (`logtype`,`perpid`,`content`,`ip`,`useragent`,`timestamp`) VALUES ('image_delete', ?, ?, ?, ?, ?)", array($_SESSION["id"], $target, $_SERVER["REMOTE_ADDR"], substr($_SERVER["HTTP_USER_AGENT"], 0, 256), time()));
                $messages[] = success("Successfully deleted avatar.");
            }
            else {
                $messages[] = error("Failed to delete avatar.");
            }
        }
    }
}

// Get the information for this account.
$accountInfo = preparedQuery("SELECT `name`, `color`, `bio`, `namevisible`, `emailvisible` FROM `accounts` WHERE `id`=?", array($_SESSION["id"]));

$a = $accountInfo->fetch_assoc();

$settingsVars = array(
 "token" => $_SESSION["csrf_token"],
 "name" => $_POST["name"] ?? $a["name"],
 "color" => $_POST["color"] ?? "#" . $a["color"],
 "bio" => $_POST["bio"] ?? $a["bio"],
 "namevisible" => $a["namevisible"] ? " checked" : "",
 "emailvisible" => $a["emailvisible"] ? " checked" : "",
 "avatars" => ""
);

$uploads = scandir("images/");
$avatars = array();
// Get all avatars.
foreach ($uploads as $u) {
    if (str_starts_with($u, "a_" . $_SESSION["id"] . ".")) {
        $avatars[] = $u;
    }
}
foreach ($avatars as $avatar) {
    // Get the upload time to add as a URL parameter when showing the image to avoid an old cached version being displayed by the browser.
    $uploadTime = preparedQuery("SELECT `timestamp` FROM `logs` WHERE `content`=? ORDER BY `timestamp` DESC LIMIT 1", array("images/{$avatar}"));
    if ($uploadTime->num_rows > 0) {
        $ut = $uploadTime->fetch_assoc()["timestamp"];
    }
    else {
        $ut = time();
    }
    $settingsVars["avatars"] .= "<div class='uploadTile'>
     <img src='" . makeURL("images/{$avatar}") . "?{$ut}'>
     <hr>
     <form method='post' onsubmit='return confirm(\"Are you sure you want to delete this avatar?\");'>
      <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
      <input type='hidden' value='{$avatar}' name='davatar'>
      <input type='submit' value='Delete' name='deleteAvatar' class='button'>
     </form>
    </div>";
}

render_page("panel/profile-settings.html", $settingsVars, $title);

?>
