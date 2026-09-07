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

// account-settings.php
// Allows a user to change some of their account information.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// No need for a permission check, any user who can log in should be able to perform all actions on this page.

$title = "Account Settings";

// Get the information for this account.
$accountInfo = preparedQuery("SELECT `name`, `username`, `email`, `password` FROM `accounts` WHERE `id`=?", array($_SESSION["id"]));

while ($a = $accountInfo->fetch_assoc()) {
    $name = $a["name"];
    $username = $a["username"];
    $email = $a["email"];
    $password = $a["password"];
}

// Handle requests.
if (validateCSRFToken()) {
    $errors = array();
        
    if (isset($_POST["name"]) or isset($_POST["username"]) or isset($_POST["email"])) {
        // Validate their name.
        if ($_POST["name"] != $name) {
            $errors = array_merge($errors, validateName($_POST["name"] ?? ""));
        }
        
        // Validate their username.
        if ($_POST["username"] != $username) {
            $errors = array_merge($errors, validateUsername($_POST["username"] ?? ""));
        }
        
        // Validate their email.
        if ($_POST["email"] != $email) {
            $errors = array_merge($errors, validateEmail($_POST["email"] ?? "", true));
        }
            
        // Make sure the password is correct.
        if (!password_verify($_POST["password1"], $password)) {
            $errors[] = "Incorrect password.";
        }
        
        if (count($errors) !== 0) {
            foreach ($errors as $e) {
                $messages[] = error($e);
            }
        }
        else {
            if (($_POST["name"] == $name) and ($_POST["username"] == $username) and ($_POST["email"] == $email)) {
                $messages[] = success("Successfully did nothing.");
            }
            else {
                preparedQuery("UPDATE `accounts` SET `name`=?, `username`=?, `email`=? WHERE `id`=?", array($_POST["name"], $_POST["username"], $_POST["email"], $_SESSION["id"]));
                $messages[] = success("Successfully changed account settings.");
                $_POST["password1"] = "";
            }
        }
    }
    elseif (isset($_POST["password2"]) or isset($_POST["newpassword"]) or isset($_POST["repeatpassword"])) {
        // Make sure the password is correct.
        if (!password_verify($_POST["password2"], $password)) {
            $errors[] = "Incorrect password.";
        }
        $errors = array_merge($errors, validatePassword($_POST["newpassword"]));
        if ($_POST["newpassword"] != $_POST["repeatpassword"]) {
            $errors[] = "Your passwords don't match. Please try again.";
        }
        if ($_POST["password2"] == $_POST["newpassword"]) {
            $errors[] = "Your new password can't be the same as your old password.";
        }
        if (count($errors) !== 0) {
            foreach ($errors as $e) {
                $messages[] = error($e);
            }
        }
        else {
            // Change the password and invalidate the login cookie.
            preparedQuery("UPDATE `accounts` SET `password`=?, `cookie`=NULL, `cookietime`=0 WHERE `id`=?", array(password_hash($_POST["newpassword"], PASSWORD_DEFAULT), $_SESSION["id"]));
            clearLoginCookie();
            $messages[] = success("Successfully changed password.");
            $_POST["password2"] = "";
            $_POST["newpassword"] = "";
            $_POST["repeatpassword"] = "";
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

$settingsVars = array("token" => $_SESSION["csrf_token"],
"name" => $_POST["name"] ?? $name,
"username" => $_POST["username"] ?? $username,
"email" => $_POST["email"] ?? $email,
"password1" => $_POST["password1"] ?? "",
"password2" => $_POST["password2"] ?? "",
"newpassword" => $_POST["newpassword"] ?? "",
"repeatpassword" => $_POST["repeatpassword"] ?? "",
"avatars" => "");

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

render_page("panel/account-settings.html", $settingsVars, $title);

?>
