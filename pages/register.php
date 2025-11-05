<?php
// register.php
// Allow a user to sign up for an account.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$content = "";

// If the user is already logged in, don't let them into the page.
if (isset($_SESSION["logged_in"]) && ($_SESSION["logged_in"] === true)) {
    $content .= "<div class='error'>You're already logged in.</div>";
}
// If registration is disabled, don't let them in.
elseif (!$config["allowRegistration"]) {
    $content .= "<div class='error'>Registration is disabled.</div>";
}
// Otherwise, proceed as normal.
else {
    // Keep track of whether or not the user successfully registered.
    $registerSuccess = false;

    // Handle requests.
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // If the CSRF token is sent and valid.
        if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
            // Generate a new token.
            generateCSRFToken();
            
            $errors = array();
            
            // Make a database query for accounts with the supplied username.
            $usernameCheck = $db->query("SELECT 1 FROM `accounts` WHERE username='" . $db->real_escape_string($_POST["username"]) . "'");
            // Make a database query for accounts with the supplied email address.
            $emailCheck = $db->query("SELECT 1 FROM `accounts` WHERE email='" . $db->real_escape_string($_POST["email"]) . "'");
            
            // Make sure their username isn't too short.
            if (strlen($_POST["username"]) < 1) {
                $errors[] = "Your username is too short. Make sure your username is at least 1 character in length.";
            }
            // Make sure their username isn't too long.
            elseif (strlen($_POST["username"]) > 32) {
                $errors[] = "Your username is too long. Make sure your username is no more than 32 characters in length.";
            }
            // Make sure their username isn't taken.
            if ($usernameCheck->num_rows > 0) {
                $errors[] = "Your username is already taken. Try entering another one.";
            }
            // Make sure their email isn't too short.
            if (strlen($_POST["email"]) < 1) {
                $errors[] = "Your email address is too short. Make sure your email address is at least 1 character in length.";
            }
            // Make sure their email isn't too long.
            elseif (strlen($_POST["email"]) > 64) {
                $errors[] = "Your email address is too long. Make sure your email address is no more than 64 characters in length.";
            }
            // Make sure their email is valid.
            if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Your email address is invalid. Please try entering a valid email address.";
            }
            // Make sure their email isn't taken.
            if ($emailCheck->num_rows > 0) {
                $errors[] = "Your email address is already taken. Try entering another one.";
            }
            // Make sure their password is long enough.
            if (strlen($_POST["password"]) < 8) {
                $errors[] = "Your password isn't long enough. Make sure your password is at least 8 characters in length.";
            }
            // Make sure their password entries match.
            if ($_POST["password"] != $_POST["repeatpassword"]) {
                $errors[] = "Your passwords don't match. Please try again.";
            }
            
            // If everything checks out, make the account.
            if (count($errors) === 0) {
                // Insert the account into the database.
                $db->query("INSERT INTO `accounts` (username, email, password, name, ip, jointime, lastactive) VALUES ('" . $db->real_escape_string($_POST["username"]) . "', '" . $db->real_escape_string($_POST["email"]) . "', '" . $db->real_escape_string(password_hash($_POST["password"], PASSWORD_DEFAULT)) . "', 'Anonymous', '" . $db->real_escape_string(ip2long($_SERVER["REMOTE_ADDR"])) . "', '" . $db->real_escape_string(time()) . "', '" . $db->real_escape_string(time()) . "')");

                // Inform the user that they've successfully registered.
                $content .= "You've successfully registered for an account. Note that it must be approved before it's usable.";
                $registerSuccess = true;
            }
            // Otherwise, display the errors.
            else {
                foreach ($errors as $e) {
                    $content .= "<div class='error'>" . $e . "</div>";
                }
            }
        }
    }
    // Display the registration form if the user didn't successfully register.
    if (!$registerSuccess) {
        $content .= "<div class='registerForm'>
            <h2>Register</h2>
            <form method='post'>
                <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
                <label>Username: </label><input type='text' name='username' autocomplete='username' maxlength='32' value='" . (isset($_POST["username"]) ? htmlspecialchars($_POST["username"]) : "") . "' required></input></br>
                <label>Email Address: </label><input type='email' name='email' autocomplete='email' maxlength='64' value='" . (isset($_POST["email"]) ? htmlspecialchars($_POST["email"]) : "") . "' required></input></br>
                <label>Password: </label><input type='password' name='password' autocomplete='new-password' value='" . (isset($_POST["password"]) ? htmlspecialchars($_POST["password"]) : "") . "' required></input></br>
                <label>Repeat password: </label><input type='password' name='repeatpassword' value='" . (isset($_POST["repeatpassword"]) ? htmlspecialchars($_POST["repeatpassword"]) : "") . "' required></input></br>
                <br><input type='submit' value='Register' id='buttonRegister'></input>
            </form>
        </div>";
    }
}

render($content);

?>

