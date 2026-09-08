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

// register.php
// Allow a user to sign up for an account.

// TODO: allow resending email (with strict limits)

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$registervars = array();
$title = lang("global.register");

// If the user is already logged in, don't let them into the page.
if ($_SESSION["logged_in"]) {
    $messages[] = error("You're already logged in.");
    render_page("", $registervars, $title);
    exit();
}

// If registration is disabled, don't let them in.
if (!$config["allowRegistration"]) {
    $messages[] = error("Registration is disabled.");
    render_page("", $registervars, $title);
    exit();

}

if (isset($url[1]) and ($config["registrationMode"] == "email")) {
    // Look for valid email links issued within the past hour.
    $account = preparedQuery("SELECT `id` FROM `accounts` WHERE `cookie`=? AND `cookietime`>? AND `role`='Unapproved'", array(hash("sha256", $url[1]), (time()-3600)));
    
    if ($account->num_rows < 1) {
        $messages[] = error("Invalid account activation link.");
        render_page("", $registervars, $title);
        exit();
    }
    
    $a = $account->fetch_assoc();
    
    preparedQuery("UPDATE `accounts` SET `cookie`=NULL, `cookietime`=0, `role`='Member' WHERE `id`=?", array($a["id"]));

    $messages[] = unsafe_success("Successfully activated your account. You may now <a href='" . makeURL("login") . "'>log in</a>.");
    render_page("", $registervars, $title);
    exit();
}

// Keep track of whether or not the user successfully registered.
$registerSuccess = false;

// Handle requests.
if (validateCSRFToken()) {
    $errors = array();

    // Lock to avoid race conditions.
    $db->query("LOCK TABLE `accounts` WRITE");
    // Make sure there aren't too many accounts from this IP.
    $ipCheck = preparedQuery("SELECT `jointime` FROM `accounts` WHERE `ip`=? OR `joinip`=?", array($_SERVER["REMOTE_ADDR"], $_SERVER["REMOTE_ADDR"]));
    if ($ipCheck->num_rows >= $config["accountsPerIP"]) {
        $errors[] = "You've made too many accounts.";
    }
    // Enforce a time based rate limit.
    while ($r = $ipCheck->fetch_assoc()) {
        if ((time()-$r["jointime"]) <= $config["accountCooldown"]) {
            $errors[] = "You've made an account too recently. Wait a while and try again.";
        }
    }
            
    // Validate their name.
    $errors = array_merge($errors, validateName($_POST["name"] ?? ""));
           
    // Validate their username.
    $errors = array_merge($errors, validateUsername($_POST["username"] ?? ""));
            
    // Validate their email.
    $errors = array_merge($errors, validateEmail($_POST["email"] ?? "", true));
        
    // Validate their password.
    $errors = array_merge($errors, validatePassword($_POST["password"] ?? ""));
        
    // Make sure their password entries match.
    if ($_POST["password"] != $_POST["repeatpassword"]) {
        $errors[] = "Your passwords don't match. Please try again.";
    }
        
    // Make sure the CAPTCHA was filled out correctly.
    if (extension_loaded("gd") and $config["captchaEnabled"]) {
        if (strtolower($_POST["captcha"] ?? "") != strtolower($_SESSION["captcha"])) {
            $errors[] = "CAPTCHA was not filled out correctly.";
        }
    }
            
    // If everything checks out, make the account.
    if (count($errors) == 0) {
        $cookie = "";
        $cookiefordb = "NULL";
        $cookietime = 0;
        $now = time();
        // Decide what role to assign.
        switch ($config["registrationMode"]) {
            case "open":
                $role = "Member";
                break;
            // Fallthrough intentional.
            case "email":
                $cookie = bin2hex(random_bytes(32));
                $cookiefordb = hash("sha256", $cookie);
                $cookietime = $now;
            case "approval":
            // Default to approval.
            default:
                $role = "Unapproved";
        }
        preparedQuery("INSERT INTO `accounts` (`username`, `email`, `password`, `name`, `role`, `joinip`, `ip`, `jointime`, `lastactive`, `cookie`, `cookietime`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($_POST["username"], $_POST["email"], password_hash($_POST["password"], PASSWORD_DEFAULT), $_POST["name"], $role, $_SERVER["REMOTE_ADDR"], $_SERVER["REMOTE_ADDR"], $now, $now, $cookiefordb, $cookietime));

        // Inform the user that they've successfully registered.
        if ($role == "Unapproved") {
            switch ($config["registrationMode"]) {
                case "approval":
                    $messages[] = success("You've successfully registered for an account. Note that it must be approved before it's usable.");
                    break;
                case "email":
                    $emailSuccess = sendEmail($_POST["email"], "Activate your account", "Someone has registered for an account on " . $config["title"] . " with this email address.\n\nIf this wasn't you, this email can be ignored. If it was, click the link below to verify your email\n\n" . ($config["baseURL"] . makeURL("register/" . $cookie)));
                    if ($emailSuccess) {
                        $messages[] = success("You've successfully registered for an account. Click the link provided to the email you specified to activate your account.");
                    }
                    else {
                        $messages[] = error("Failed to send activation email. Please contact the blog owner/administrator(s).");
                    }
                    break;
            }
        }
        elseif ($role == "Member") {
            $messages[] = unsafe_success("You've successfully registered for an account. You may now <a href='" . makeURL("login") . "'>log in</a>.");
        }
        $accountid = $db->insert_id;
        $db->query("UNLOCK TABLES");
        $registerSuccess = true;
        // Log the registration.
        preparedQuery("INSERT INTO `logs` (`logtype`, `targetid`, `ip`, `useragent`, `timestamp`) VALUES ('registration', ?, ?, ?, ?)", array($accountid, $_SERVER["REMOTE_ADDR"], substr($_SERVER["HTTP_USER_AGENT"], 0, 256), time()));
    }
    // Otherwise, display the errors.
    else {
        $db->query("UNLOCK TABLES");
        foreach ($errors as $e) {
            $messages[] = error($e);
        }
    }
}
// Display the registration form if the user didn't successfully register.
if (!$registerSuccess) {
    $registervars = array("token" => $_SESSION["csrf_token"],
    "name" => $_POST["name"] ?? "",
    "username" => $_POST["username"] ?? "",
    "email" => $_POST["email"] ?? "",
    "minlength" => MIN_PASSWORD_LENGTH,
    "captcha" => "");
    
    // Add the CAPTCHA if we can and it's enabled.
    if (extension_loaded("gd") and $config["captchaEnabled"]) {
        $registervars["captcha"] .= "<br>" . "<img src='data:image/webp;base64," . generateCaptcha() . "' alt='CAPTCHA image'>";
        $registervars["captcha"] .= "<label for='captcha'>CAPTCHA:</label>
        <input id='captcha' name='captcha' type='text' required>";
    }
}
else {
    render_page("", $registervars, $title);
    exit();
}

render_page("register.html", $registervars, $title);

?>
