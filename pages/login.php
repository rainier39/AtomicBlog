<?php
// login.php
// Allow account logins.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$content = "";
$success = false;

// If the user is already logged in, don't let them into the page.
if (isset($_SESSION["logged_in"]) && ($_SESSION["logged_in"] === true)) {
   $content .= "<div class='error'>You're already logged in.</div>";
}
// Otherwise, proceed as normal.
else {
    // Handle requests.
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // If the CSRF token is sent and valid.
        if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
            // Generate a new token.
            generateCSRFToken();
            // First find the account they want to log into.
            $result = $db->query("SELECT * FROM `accounts` WHERE username='" . $db->real_escape_string($_POST["username"]) . "'");

            // If the login failed, let them know.
            if ($result->num_rows < 1) {
                $content .= "<div class='error'>Failed to find requested account.</div>";
            }
            // If the login didn't immediately fail, keep going.
            else {
                while ($r = $result->fetch_assoc()) {
                    // If the entered password doesn't match with the hash in the database, inform the user.
                    if (!password_verify($_POST["password"], $r["password"])) {
                        $content .= "<div class='error'>The password you entered was incorrect.</div>";
                    }
                    // Otherwise, proceed.
                    else {
                        // Check if the user is unapproved. If so, don't let them log in.
                        if ($r["role"] == "Unapproved") {
                            $content .= "<div class='error'>Your account must be approved before you can log in.</div>";
                        }
                        // Otherwise, log the user in.
                        else {
                            $_SESSION["logged_in"] = true;
                            $_SESSION["id"] = $r["id"];
                            $_SESSION["username"] = $r["username"];
                            $_SESSION["role"] = $r["role"];

                            $content .= "Successfully logged in. Welcome, " . htmlspecialchars($_SESSION["username"]) . ".";
                            $success = true;
                            redirect("", 2);
                        }
                    }
                }
            }
        }
    }
    // Display the login form.
    if (!$success) {
        $content .= "
        <div class='loginForm'>
            <h2>Log in</h2>
            <form method='post'>
            <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
            <label for='username'>Username: </label><input type='text' name='username' id='username' autocomplete='username' maxlength='32' required></input></br>
            <label for='password'>Password: </label><input type='password' name='password' id='password' autocomplete='current-password' required></input></br>
            <input type='submit' value='Log in' id='button'></input>
            </form>
        </div>";
    }
}

render($content);

?>

