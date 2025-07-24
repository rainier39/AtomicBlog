<?php
// login.php
// Allow account logins.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// If the user is already logged in, don't let them into the page.
if (isset($_SESSION["logged_in"]) && ($_SESSION["logged_in"] == true)) {
    echo("You're already logged in.");
}
// Otherwise, proceed as normal.
else {
    // Handle requests.
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // First find the account they want to log into.
        $result = $db->query("SELECT * FROM `accounts` WHERE username='" . $db->real_escape_string($_POST["username"]) . "'");

        // If the login failed, let them know.
        if ($result->num_rows < 1) {
            echo("Failed to find requested account.");
        }
        // If the login didn't immediately fail, keep going.
        else {
            while ($r = $result->fetch_assoc()) {
                // If the entered password doesn't match with the hash in the database, inform the user.
                if (!password_verify($_POST["password"], $r["password"])) {
                    echo("The password you entered was incorrect.");
                }
                // Otherwise, proceed.
                else {
                    // Check if the user is unapproved. If so, don't let them log in.
                    if ($r["role"] == "Unapproved") {
                        echo("Your account must be approved before you can log in.");
                    }
                    // Otherwise, log the user in.
                    else {
                        $_SESSION["logged_in"] = true;
                        $_SESSION["id"] = $r["id"];
                        $_SESSION["username"] = $r["username"];
                        $_SESSION["role"] = $r["role"];

                        echo("Successfully logged in. Welcome, " . htmlspecialchars($_SESSION["username"]) . ".");
                    }
                }
            }
        }
    }
    // Display the login form.
    else {
        echo("
        <div class='loginForm'>
            <h2>Log in</h2>
            <form method='post'>
            <label>Username: </label><input type='text' name='username' autocomplete='username' maxlength='32' required></input></br>
            <label>Password: </label><input type='password' name='password' autocomplete='current-password' required></input></br>
            <input type='submit' value='Log in' id='button'></input>
            </form>
        </div>
        ");
    }
}

?>

