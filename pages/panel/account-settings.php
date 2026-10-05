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
$accountInfo = preparedQuery("SELECT `username`, `email`, `password` FROM `accounts` WHERE `id`=?", array($_SESSION["id"]));

$a = $accountInfo->fetch_assoc();

// Handle requests.
if (validateCSRFToken()) {
    $errors = array();
        
    if (isset($_POST["username"])) {
        // Make sure the password is correct.
        if (!password_verify($_POST["password"], $a["password"])) {
            $errors[] = "Incorrect password.";
        }
        // Validate their username.
        elseif ($_POST["username"] != $a["username"]) {
            $errors = array_merge($errors, validateUsername($_POST["username"] ?? ""));
        }
        
        if (count($errors) !== 0) {
            foreach ($errors as $e) {
                $messages[] = error($e);
            }
        }
        else {
            if ($_POST["username"] == $a["username"]) {
                $messages[] = info("Nothing to change.");
            }
            else {
                preparedQuery("UPDATE `accounts` SET `username`=? WHERE `id`=?", array($_POST["username"], $_SESSION["id"]));
                $messages[] = success("Successfully changed username.");
                // Log the event.
                preparedQuery("INSERT INTO `logs` (`logtype`, `perpid`, `ip`, `useragent`, `timestamp`) VALUES ('panel_change_username', ?, ?, ?, ?)", array($_SESSION["id"], $_SERVER["REMOTE_ADDR"], substr($_SERVER["HTTP_USER_AGENT"], 0, 256), time()));
            }
        }
    }
    elseif (isset($_POST["email"])) {
        // Make sure the password is correct.
        if (!password_verify($_POST["password"], $a["password"])) {
            $errors[] = "Incorrect password.";
        }
        // Validate their email.
        elseif ($_POST["email"] != $a["email"]) {
            $errors = array_merge($errors, validateEmail($_POST["email"] ?? "", true));
        }

        if (count($errors) !== 0) {
            foreach ($errors as $e) {
                $messages[] = error($e);
            }
        }
        else {
            if ($_POST["email"] == $a["email"]) {
                $messages[] = info("Nothing to change.");
            }
            else {
                preparedQuery("UPDATE `accounts` SET `email`=? WHERE `id`=?", array($_POST["email"], $_SESSION["id"]));
                $messages[] = success("Successfully changed email address.");
                // Log the event.
                preparedQuery("INSERT INTO `logs` (`logtype`, `perpid`, `ip`, `useragent`, `timestamp`) VALUES ('panel_change_email', ?, ?, ?, ?)", array($_SESSION["id"], $_SERVER["REMOTE_ADDR"], substr($_SERVER["HTTP_USER_AGENT"], 0, 256), time()));
            }
        }
    }
    elseif (isset($_POST["newpassword"]) or isset($_POST["repeatpassword"])) {
        // Make sure the password is correct.
        if (!password_verify($_POST["password"], $a["password"])) {
            $errors[] = "Incorrect password.";
        }
        elseif ($_POST["newpassword"] != $_POST["repeatpassword"]) {
            $errors[] = "Your passwords don't match. Please try again.";
        }
        elseif ($_POST["password"] == $_POST["newpassword"]) {
            $errors[] = "Your new password can't be the same as your old password.";
        }
        $errors = array_merge($errors, validatePassword($_POST["newpassword"]));
        if (count($errors) !== 0) {
            foreach ($errors as $e) {
                $messages[] = error($e);
            }
        }
        else {
            // Change the password and invalidate the login cookie.
            preparedQuery("UPDATE `accounts` SET `password`=?, `cookie`=NULL, `cookietime`=0 WHERE `id`=?", array(password_hash($_POST["newpassword"], PASSWORD_DEFAULT), $_SESSION["id"]));
            clearLoginCookie();
            // Prevent possible session fixation attacks.
            session_regenerate_id(true);
            // Generate a new CSRF token.
            generateCSRFToken();
            $messages[] = success("Successfully changed password.");
            // Log the event.
            preparedQuery("INSERT INTO `logs` (`logtype`, `perpid`, `ip`, `useragent`, `timestamp`) VALUES ('panel_change_password', ?, ?, ?, ?)", array($_SESSION["id"], $_SERVER["REMOTE_ADDR"], substr($_SERVER["HTTP_USER_AGENT"], 0, 256), time()));
        }
    }
}

$settingsVars = array(
 "token" => $_SESSION["csrf_token"],
 "username" => $_POST["username"] ?? $a["username"],
 "email" => $_POST["email"] ?? $a["email"],
 "minlength" => MIN_PASSWORD_LENGTH
);

render_page("panel/account-settings.html", $settingsVars, $title);

?>
