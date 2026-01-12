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
            
            // Make sure there aren't too many accounts from this IP.
            $ipCheck = $db->query("SELECT `jointime` FROM `accounts` WHERE `ip`='" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "' OR `joinip`='" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "'");
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
                $now = time();
                $db->query("INSERT INTO `accounts` (`username`, `email`, `password`, `name`, `role`, `joinip`, `ip`, `jointime`, `lastactive`) VALUES ('" . $db->real_escape_string($_POST["username"]) . "', '" . $db->real_escape_string($_POST["email"]) . "', '" . $db->real_escape_string(password_hash($_POST["password"], PASSWORD_DEFAULT)) . "', '" . $db->real_escape_string($_POST["name"]) . "', 'Unapproved', '" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "', '" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "', '" . $now . "', '" . $now . "')");

                // Inform the user that they've successfully registered.
                $content .= "You've successfully registered for an account. Note that it must be approved before it's usable.";
                $registerSuccess = true;
            }
            // Otherwise, display the errors.
            else {
                foreach ($errors as $e) {
                    $content .= "<div class='error'>" . htmlspecialchars($e) . "</div>";
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
                <label for='name'>Name: </label><input type='text' name='name' id='name' autocomplete='name' maxlength='64' value='" . (isset($_POST["name"]) ? htmlspecialchars($_POST["name"]) : "") . "' required></input></br>
                <label for='username'>Username: </label><input type='text' name='username' id='username' autocomplete='username' maxlength='32' value='" . (isset($_POST["username"]) ? htmlspecialchars($_POST["username"]) : "") . "' required></input></br>
                <label for='email'>Email Address: </label><input type='email' name='email' id='email' autocomplete='email' maxlength='64' value='" . (isset($_POST["email"]) ? htmlspecialchars($_POST["email"]) : "") . "' required></input></br>
                <label for='password'>Password: </label><input type='password' name='password' id='password' autocomplete='new-password' value='" . (isset($_POST["password"]) ? htmlspecialchars($_POST["password"]) : "") . "' required></input></br>
                <label for='repeatpassword'>Repeat password: </label><input type='password' name='repeatpassword' id='repeatpassword' value='" . (isset($_POST["repeatpassword"]) ? htmlspecialchars($_POST["repeatpassword"]) : "") . "' required></input></br>
                <br><input type='submit' value='Register' id='buttonRegister'></input>
            </form>
        </div>";
    }
}

render($content);

?>

