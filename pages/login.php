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

// login.php
// Allow account logins.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$success = false;
$title = lang("global.login");
$loginvars = array("username" => "",
"password" => "");

function handleLogin() {
    global $db, $config, $success, $ishttps, $messages;
    
    if (!isset($_POST["username"]) || !isset($_POST["password"]) || ($_POST["username"] == "") || ($_POST["password"] == "")) {
        $messages[] = error("Must supply a non-blank username and password.");
        return;
    }
    // Make sure the CSRF token is sent and valid.
    if ((!isset($_POST["csrf_token"])) or ($_POST["csrf_token"] !== $_SESSION["csrf_token"])) {
        $messages[] = error("Token error. This is likely a CSRF attack.");
        return;
    }
    // Generate a new token.
    generateCSRFToken();

    // Delete login attempts older than an hour.
    $db->query("DELETE FROM `logins` WHERE `timestamp`<" . time() . "-3600");
    // Now see how many login attempts there are.
    $attempts = $db->query("SELECT 1 FROM `logins` WHERE `ip`='" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "'");
    
    // Stop if they've logged in or tried to too many times.
    if ($attempts->num_rows >= $config["loginsPerHour"]) {
        $messages[] = error("Too many logins or login attempts. Try again later.");
        return;
    }
    // Otherwise, record this as a login attempt.
    else {
        $db->query("INSERT INTO `logins` (`ip`, `timestamp`) VALUES ('" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "', '" . time() . "')");
    }

    // First find the account they want to log into.
    $result = $db->query("SELECT `id`, `name`, `role`, `password` FROM `accounts` WHERE `username`='" . $db->real_escape_string($_POST["username"]) . "'");
    // If there's no account with that name, give them the generic failure message. This helps prevent username enumeration.
    if ($result->num_rows < 1) {
        $messages[] = error("The username or password you entered was incorrect.");
        return;
    }
    
    while ($r = $result->fetch_assoc()) {
        // Wrong password.
        if (!password_verify($_POST["password"], $r["password"])) {
            $messages[] = error("The username or password you entered was incorrect.");
            return;
        }
        // Make sure the account is allowed to be logged into.
        if (!checkRolePerm(PERM_LOGIN, $r["role"])) {
            if ($r["role"] == "Unapproved") {
                $messages[] = error("Your account must be approved before you can log in.");
            }
            else {
                $messages[] = error("This account cannot be logged into.");
            }
            return;
        }
        // Finally, log the user in.
        $_SESSION["logged_in"] = true;
        $_SESSION["id"] = $r["id"];

        $messages[] = success("Successfully logged in. Welcome, " . $r["name"] . ".");
        $success = true;
                            
        if (isset($_POST["stayloggedin"]) and ($_POST["stayloggedin"] == "on")) {
            $cookie = hash("sha256", random_bytes(64));
            // Also give the user their login cookie.
            $cookieoptions = array(
                // Expires in a week.
                "expires" => time() + 60*60*24*7,
                "secure" => (($ishttps == "on") ? true : false),
                "httponly" => true,
                "samesite" => "Strict"
            );
            setcookie("AtomicBlog_login", $cookie, $cookieoptions);
        }
        else {
            $cookie = "NULL";
        }
        
        // Update the user's lastactive time, IP, and login cookie.
        $db->query("UPDATE `accounts` SET `ip`='" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "', `lastactive`='" . time() . "', `cookie`='" . $cookie . "', `cookietime`='" . time() . "' WHERE `id`='" . $r["id"] . "'");
                            
        redirect("", 2);
    }
}

// If the user is already logged in, don't let them into the page.
if (isset($_SESSION["logged_in"]) && ($_SESSION["logged_in"] === true)) {
   $messages[] = error("You're already logged in.");
   render_page("", $loginvars, $title);
}
// Otherwise, proceed as normal.
else {
    // Handle requests.
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        handleLogin();
    }
    // Display the login form.
    if (!$success) {
        $loginvars = array("token" => $_SESSION["csrf_token"],
        "username" => $_POST["username"] ?? "",
        "password" => $_POST["password"] ?? "");
        render_page("login.html", $loginvars, $title);
    }
    else {
        render_page("", $loginvars, $title);
    }
}

?>

