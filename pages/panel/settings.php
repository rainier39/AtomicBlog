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

// settings.php
// Allows a user to change some of their account information.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// No need for a permission check, any user who can log in should be able to perform all actions on this page.

$title = "Account Settings";

// Get the information for this account.
$accountInfo = $db->query("SELECT `name`, `username`, `email`, `password` FROM `accounts` WHERE `id`='" . $_SESSION["id"] . "'");

while ($a = $accountInfo->fetch_assoc()) {
    $name = $a["name"];
    $username = $a["username"];
    $email = $a["email"];
    $password = $a["password"];
}

// Handle requests.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // If the CSRF token is sent and valid.
    if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
        // Generate a new token.
        generateCSRFToken();
        
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
                    $db->query("UPDATE `accounts` SET `name`='" . $db->real_escape_string($_POST["name"]) . "', `username`='" . $db->real_escape_string($_POST["username"]) . "', `email`='" . $db->real_escape_string($_POST["email"]) . "' WHERE `id`='" . $_SESSION["id"] . "'");
                    $messages[] = success("Successfully changed account settings.");
                }
            }
        }
        elseif (isset($_POST["password"]) or isset($_POST["newpassword"]) or isset($_POST["repeatpassword"])) {
            // Make sure the password is correct.
            if (!password_verify($_POST["password"], $password)) {
                $errors[] = "Incorrect password.";
            }
            if (strlen($_POST["newpassword"]) < 8) {
                $errors[] = "Your password isn't long enough. Make sure your password is at least 8 characters in length.";
            }
            if ($_POST["newpassword"] != $_POST["repeatpassword"]) {
                $errors[] = "Your passwords don't match. Please try again.";
            }
            if ($_POST["password"] == $_POST["newpassword"]) {
                $errors[] = "Your new password can't be the same as your old password.";
            }
            if (count($errors) !== 0) {
                foreach ($errors as $e) {
                    $messages[] = error($e);
                }
            }
            else {
                $db->query("UPDATE `accounts` SET `password`='" . $db->real_escape_string(password_hash($_POST["newpassword"], PASSWORD_DEFAULT)) . "' WHERE `id`='" . $_SESSION["id"] . "'");
                $messages[] = success("Successfully changed password.");
                $_POST["password"] = "";
                $_POST["newpassword"] = "";
                $_POST["repeatpassword"] = "";
            }
        }
    }
}

$settingsVars = array("token" => $_SESSION["csrf_token"],
"name" => $_POST["name"] ?? $name,
"username" => $_POST["username"] ?? $username,
"email" => $_POST["email"] ?? $email,
"password" => $_POST["password"] ?? "",
"newpassword" => $_POST["newpassword"] ?? "",
"repeatpassword" => $_POST["repeatpassword"] ?? "");

render_page("panel/settings.html", $settingsVars, $title);

?>
