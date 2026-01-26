<?php
// login.php
// Allow account logins.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$content = "";
$success = false;
$title = "Login";

function handleLogin() {
    global $db, $config, $content, $success;
    
    if (!isset($_POST["username"]) || !isset($_POST["password"]) || ($_POST["username"] == "") || ($_POST["password"] == "")) {
        $content .= "<div class='error'>Must supply a non-blank username and password.</div>";
        return;
    }
    // Make sure the CSRF token is sent and valid.
    if ((!isset($_POST["csrf_token"])) or ($_POST["csrf_token"] !== $_SESSION["csrf_token"])) {
        $content .= "<div class='error'>Token error. This is likely a CSRF attack.</div>";
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
        $content .= "<div class='error'>Too many logins or login attempts. Try again later.</div>";
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
        $content .= "<div class='error'>The username or password you entered was incorrect.</div>";
        return;
    }
    
    while ($r = $result->fetch_assoc()) {
        // Wrong password.
        if (!password_verify($_POST["password"], $r["password"])) {
            $content .= "<div class='error'>The username or password you entered was incorrect.</div>";
            return;
        }
        // Make sure the account is allowed to be logged into.
        if (!checkRolePerm(PERM_LOGIN, $r["role"])) {
            if ($r["role"] == "Unapproved") {
                $content .= "<div class='error'>Your account must be approved before you can log in.</div>";
            }
            else {
                $content .= "<div class='error'>This account cannot be logged into.</div>";
            }
            return;
        }
        // Finally, log the user in.
        $_SESSION["logged_in"] = true;
        $_SESSION["id"] = $r["id"];

        $content .= "<div class='success'>Successfully logged in. Welcome, " . htmlspecialchars($r["name"]) . ".</div>";
        $success = true;
                            
        // Update the user's lastactive time and IP.
        $db->query("UPDATE `accounts` SET `ip`='" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "', `lastactive`='" . time() . "' WHERE `id`='" . $r["id"] . "'");
                            
        redirect("", 2);
    }
}

// If the user is already logged in, don't let them into the page.
if (isset($_SESSION["logged_in"]) && ($_SESSION["logged_in"] === true)) {
   $content .= "<div class='error'>You're already logged in.</div>";
}
// Otherwise, proceed as normal.
else {
    // Handle requests.
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        handleLogin();
    }
    // Display the login form.
    if (!$success) {
        $content .= "
        <div class='loginForm'>
            <h1>Log in</h1>
            <form method='post'>
            <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
            <label for='username'>Username: </label><input type='text' name='username' id='username' autocomplete='username' maxlength='32' required" . (isset($_POST["username"]) ? " value='" . htmlspecialchars($_POST["username"]) . "'" : "") . "></input></br>
            <label for='password'>Password: </label><input type='password' name='password' id='password' autocomplete='current-password' required" . (isset($_POST["password"]) ? " value='" . htmlspecialchars($_POST["password"]) . "'" : "") . "></input></br>
            <input type='submit' value='Log in' id='button'></input>
            </form>
        </div>";
    }
}

render($content, $title);

?>

