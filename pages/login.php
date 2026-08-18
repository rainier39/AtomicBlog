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

// If the user is already logged in, don't let them into the page.
if ($_SESSION["logged_in"]) {
   $messages[] = error("You're already logged in.");
   render_page("", array(), $title);
}
// Handle requests.
elseif (isset($_POST["username"]) and isset($_POST["password"])
        and (($_POST["csrf_token"] ?? "") == $_SESSION["csrf_token"])) {
    // Generate a new token.
    generateCSRFToken();
    
    $errors = array();
    
    if (strlen($_POST["username"]) < 1) {
        $errors[] = error("Username cannot be blank.");
    }
    if (strlen($_POST["password"]) < 1) {
        $errors[] = error("Password cannot be blank.");
    }

    // Now see how many login attempts there are from the past hour.
    $attempts = $db->query("SELECT 1 FROM `logs` WHERE (`logtype`='login_fail' OR `logtype`='login_success') AND `ip`='" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "' AND `timestamp`>" . (time()-3600));
    
    // Stop if they've logged in or tried to too many times.
    if ($attempts->num_rows >= $config["loginsPerHour"]) {
        $errors[] = error("Too many logins or login attempts. Try again later.");
    }
    
    if (count($errors) !== 0) {
        foreach ($errors as $e) {
            $messages[] = error($e);
        }
    }
    else {
        // First find the account they want to log into.
        $result = $db->query("SELECT `id`, `name`, `role`, `password` FROM `accounts` WHERE `username`='" . $db->real_escape_string($_POST["username"]) . "'");
        // If there's no account with that name, give them the generic failure message. This helps prevent username enumeration.
        if ($result->num_rows < 1) {
            // We don't log this because there's no actual account being logged into.
            $messages[] = error("The username or password you entered was incorrect.");
        }
        else {
            $r = $result->fetch_assoc();
            
            // Wrong password.
            if (!password_verify($_POST["password"], $r["password"])) {
                $messages[] = error("The username or password you entered was incorrect.");
            }
            // Make sure the account is allowed to be logged into.
            elseif (!checkRolePerm(PERM_LOGIN, $r["role"])) {
                if ($r["role"] == "Unapproved") {
                    $messages[] = error("Your account must be approved before you can log in.");
                }
                else {
                    $messages[] = error("This account cannot be logged into.");
                }
            }
            // Finally, log the user in.
            else {
                $_SESSION["logged_in"] = true;
                $_SESSION["id"] = $r["id"];

                $_SESSION["messages"][] = success("Successfully logged in. Welcome, " . $r["name"] . ".");
                $success = true;
                            
                if (isset($_POST["stayloggedin"]) and ($_POST["stayloggedin"] == "on") and ($ishttps == "on")) {
                    $cookie = hash("sha256", random_bytes(64));
                    // Also give the user their login cookie.
                    $cookieoptions = array(
                        // Expires in a week.
                        "expires" => time() + 60*60*24*7,
                        "secure" => true,
                        "httponly" => true,
                        "samesite" => "Strict"
                    );
                    setcookie($config["cookiePrefix"] . "login", $cookie, $cookieoptions);
                }
                else {
                    $cookie = "NULL";
                }
        
                // Update the user's lastactive time, IP, and login cookie.
                $db->query("UPDATE `accounts` SET `ip`='" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "', `lastactive`='" . time() . "', `cookie`='" . $cookie . "', `cookietime`='" . time() . "' WHERE `id`='" . $r["id"] . "'");
                // Log the successful login.
                $db->query("INSERT INTO `logs` (`logtype`, `targetid`, `ip`, `useragent`, `timestamp`) VALUES ('login_success', '" . $r["id"] . "', '" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "', '" . $db->real_escape_string(substr($_SERVER["HTTP_USER_AGENT"], 0, 256)) . "', '" . time() . "')");
            }
            if (!$success) {
                // Log the failed login attempt.
                $db->query("INSERT INTO `logs` (`logtype`, `targetid`, `ip`, `useragent`, `timestamp`) VALUES ('login_fail', '" . $r["id"] . "', '" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "', '" . $db->real_escape_string(substr($_SERVER["HTTP_USER_AGENT"], 0, 256)) . "', '" . time() . "')");
            }
        }
    }
}

// Display the login form.
if (!$success) {
    $loginvars = array("token" => $_SESSION["csrf_token"],
    "username" => $_POST["username"] ?? "",
    "password" => $_POST["password"] ?? "",
    "https" => ($ishttps == "on"),
    "test" => true);
    render_page("login.html", $loginvars, $title);
}
// Otherwise redirect the successfully logged-in user.
else {
    redirect("panel");
}

?>
