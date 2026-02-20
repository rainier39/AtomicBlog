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

// install.php
// Installs the software.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$content = "";
$title = "Installer";

// If the blog is being installed in a folder, take note of that fact.
$dir = explode("/", $_SERVER["REQUEST_URI"]);
if (end($dir) && str_starts_with(end($dir), "index.php")) {
    array_pop($dir);
}
if (count($dir) > 0) {
    $config["dir"] = trim(implode("/", $dir), "/");
}

// If mod rewrite is enabled, we can have pretty URLs.
if (function_exists("apache_get_modules") && in_array("mod_rewrite", apache_get_modules())) {
    $config["prettyURLs"] = true;
}

// Handle requests.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hard stop if this is a CSRF attack.
    if ((!isset($_POST["csrf_token"])) or ($_POST["csrf_token"] !== $_SESSION["csrf_token"])) {
        exit();
    }
    // If not, generate a fresh new token for additional security.
    else {
        generateCSRFToken();
    }
    
    $errors = array();
    
    // Stop if the core directory isn't writable. We need this for the config.
    if (!is_writable("./core/")) {
        $errors[] = "Cannot install, core directory isn't writable.";
    }
    // Stop if any of the SQL details are blank.
    if ((!isset($_POST["SQLServer"])) or ($_POST["SQLServer"] == "")) {
        $errors[] = "Cannot install, SQLServer field cannot be blank.";
    }
    if ((!isset($_POST["SQLUsername"])) or ($_POST["SQLUsername"] == "")) {
        $errors[] = "Cannot install, SQLUsername field cannot be blank.";
    }
    if ((!isset($_POST["SQLPassword"])) or ($_POST["SQLPassword"] == "")) {
        $errors[] = "Cannot install, SQLPassword field cannot be blank.";
    }
    if ((!isset($_POST["SQLDatabase"])) or ($_POST["SQLDatabase"] == "")) {
        $errors[] = "Cannot install, SQLDatabase field cannot be blank.";
    }
    // Stop if the title is too long or too short.
    if ((!isset($_POST["title"])) or (strlen($_POST["title"]) < 1)) {
        $errors[] = "Cannot install, title must be at least 1 character long.";
    }
    elseif (strlen($_POST["title"]) > 32) {
        $errors[] = "Cannot install, title cannot be longer than 32 characters.";
    }
    // Stop if the description is too long or too short.
    if ((!isset($_POST["description"])) or (strlen($_POST["description"]) < 1)) {
        $errors[] = "Cannot install, description must be at least 1 character long.";
    }
    elseif (strlen($_POST["description"]) > 128) {
        $errors[] = "Cannot install, description cannot be longer than 128 characters.";
    }
    // Stop if the name is invalid.
    $errors = array_merge($errors, validateName($_POST["name"] ?? ""));
    // Stop if the username is invalid.
    $errors = array_merge($errors, validateUsername($_POST["username"] ?? ""));
    // Stop if the email is invalid.
    $errors = array_merge($errors, validateEmail($_POST["email"] ?? ""));
    // Stop if the password(s) is/are too short.
    if ((!isset($_POST["password"])) or (strlen($_POST["password"]) < 8)) {
        $errors[] = "Cannot install, password must be at least 8 characters long.";
    }
    // Stop if the password(s) don't match.
    if ((!isset($_POST["repeatpassword"])) or ($_POST["password"] !== $_POST["repeatpassword"])) {
        $errors[] = "Cannot install, passwords do not match.";
    }
    // TODO: enforce more advanced, stringent password requirements?
    
    // Connect to the database with the given credentials.
    try {
        $db = mysqli_connect($_POST["SQLServer"], $_POST["SQLUsername"], $_POST["SQLPassword"], $_POST["SQLDatabase"]);
    }
    catch (Exception $e) {
        $errors[] = "Database Connection Error: " . $e->getMessage();
    }
    
    // If there are no errors, install.
    if (count($errors) === 0) {
        if (isset($_POST["overwrite"]) && ($_POST["overwrite"] == "on")) {
            $db->query("DROP TABLE IF EXISTS `accounts`");
            $db->query("DROP TABLE IF EXISTS `posts`");
            $db->query("DROP TABLE IF EXISTS `comments`");
            $db->query("DROP TABLE IF EXISTS `views`");
            $db->query("DROP TABLE IF EXISTS `logins`");
        }

        // Write the database.
        // We store IP addresses as varchar(45) for IPv6 support (IP addresses are stored as strings).
        // We store UNIX timestamps as bigint (64 bit signed integer) to prevent Y2038 problem.
        // All IDs are int unsigned.
        $db->query("CREATE TABLE IF NOT EXISTS `accounts` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `username` varchar(32) NOT NULL,
            `email` varchar(64) NOT NULL,
            `password` varchar(128) NOT NULL,
            `name` varchar(64) NOT NULL,
            `role` enum('Owner', 'Moderator', 'Member', 'Suspended', 'Unapproved') NOT NULL DEFAULT 'Unapproved',
            `avatar` enum('none', 'gif', 'jpg', 'png', 'webp') NOT NULL DEFAULT 'none',
            `joinip` varchar(45) NOT NULL,
            `ip` varchar(45) NOT NULL,
            `jointime` bigint NOT NULL,
            `lastactive` bigint NOT NULL,
            `namevisible` tinyint(1) NOT NULL DEFAULT '0',
            `emailvisible` tinyint(1) NOT NULL DEFAULT '0',
            `bio` varchar(4096) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $db->query("CREATE TABLE IF NOT EXISTS `posts` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `title` varchar(32) NOT NULL,
            `tags` varchar(128) NOT NULL,
            `content` text NOT NULL,
            `account` int unsigned NOT NULL,
            `starttime` bigint NOT NULL,
            `editedby` int unsigned DEFAULT NULL,
            `edittime` bigint DEFAULT NULL,
            `icon` enum('none', 'gif', 'jpg', 'png', 'webp') NOT NULL DEFAULT 'none',
            `published` tinyint(1) NOT NULL DEFAULT '0',
            `starred` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $db->query("CREATE TABLE IF NOT EXISTS `comments` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `account` int unsigned DEFAULT NULL,
            `post` int unsigned NOT NULL,
            `email` varchar(64) DEFAULT NULL,
            `ip` varchar(45) NOT NULL,
            `timestamp` bigint NOT NULL,
            `content` text NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $db->query("CREATE TABLE IF NOT EXISTS `views` (
            `ip` varchar(45) NOT NULL,
            `timestamp` bigint NOT NULL,
            `post` int unsigned NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        $db->query("CREATE TABLE IF NOT EXISTS `logins` (
            `ip` varchar(45) NOT NULL,
            `timestamp` bigint NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Write the administrator account. Will replace any existing account with the same username or email (if there is an old install of the software).
        $db->query("REPLACE INTO `accounts` (`username`, `email`, `password`, `name`, `role`, `joinip`, `ip`, `jointime`, `lastactive`) VALUES ('" . $db->real_escape_string($_POST["username"]) . "', '" . $db->real_escape_string($_POST["email"]) . "', '" . $db->real_escape_string(password_hash($_POST["password"], PASSWORD_DEFAULT)) . "', '" . $db->real_escape_string($_POST["name"]) . "', 'Owner', '" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "', '" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "', '" . $db->real_escape_string(time()) . "', '" . $db->real_escape_string(time()) . "')");

        // Create a new array of our new config values.
        $newConfig = array("installed" => true, "SQLServer" => $_POST["SQLServer"], "SQLDatabase" => $_POST["SQLDatabase"], "SQLUsername" => $_POST["SQLUsername"], "SQLPassword" => $_POST["SQLPassword"], "title" => $_POST["title"], "description" => $_POST["description"]);

        // Merge the old config array with the new one.
        $config = array_merge($config, $newConfig);

        // Write the new config array to the config file.
        flushConfig();

        // Print a message that the software has been installed.
        $content .= "<div class='success'>Software successfully installed!</div>";

        // We will want to take the user to their newly installed blog.
        redirect("", 2);
    }
    // Otherwise, display the errors.
    else {
        foreach ($errors as $e) {
            $content .= "<div class='error'>" . htmlspecialchars($e) . "</div>";
        }
    }
}

// Display the install page form.
if (!$config["installed"]) {
$content .= 
    "<div class='form installForm'>
        <h1>Installer</h1>
        <form method='post'>
            <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
            <b>SQL Details</b></br>
            <label for='sqlserver'>SQL Server: </label><input type='text' placeholder='localhost' name='SQLServer' id='sqlserver'" . (isset($_POST["SQLServer"]) ? " value='" . htmlspecialchars($_POST["SQLServer"]) . "'" : "") . " required></input></br>
            <label for='sqldatabase'>SQL Database: </label><input type='text' placeholder='AtomicBlog' name='SQLDatabase' id='sqldatabase'" . (isset($_POST["SQLDatabase"]) ? " value='" . htmlspecialchars($_POST["SQLDatabase"]) . "'" : "") . " required></input></br>
            <label for='sqlusername'>SQL Username: </label><input type='text' name='SQLUsername' id='sqlusername'" . (isset($_POST["SQLUsername"]) ? " value='" . htmlspecialchars($_POST["SQLUsername"]) . "'" : "") . " required></input></br>
            <label for='sqlpassword'>SQL Password: </label><input type='text' name='SQLPassword' id='sqlpassword'" . (isset($_POST["SQLPassword"]) ? " value='" . htmlspecialchars($_POST["SQLPassword"]) . "'" : "") . " required></input></br>

            <br><b>Blog Configuration</b></br>
            <label for='title'>Blog Title: </label><input type='text' name='title' id='title' maxlength='32'" . (isset($_POST["title"]) ? " value='" . htmlspecialchars($_POST["title"]) . "'" : "") . " required></input></br>
            <label for='description'>Blog Description: </label><textarea name='description' id='description' maxlength='128' required>" . (isset($_POST["description"]) ? htmlspecialchars($_POST["description"]) : "") . "</textarea></br>

            <br><b>Administrator Account Details</b></br>
            <label for='name'>Name: </label><input type='text' name='name' autocomplete='name' maxlength='64' id='name'" . (isset($_POST["name"]) ? " value='" . htmlspecialchars($_POST["name"]) . "'" : "") . " required></input></br>
            <label for='username'>Username: </label><input type='text' name='username' autocomplete='username' maxlength='32' id='username'" . (isset($_POST["username"]) ? " value='" . htmlspecialchars($_POST["username"]) . "'" : "") . " required></input></br>
            <label for='email'>Email Address: </label><input type='email' name='email' id='email' maxlength='64'" . (isset($_POST["email"]) ? " value='" . htmlspecialchars($_POST["email"]) . "'" : "") . " required></input></br>
            <label for='password'>Password: </label><input type='password' name='password' id='password'" . (isset($_POST["password"]) ? " value='" . htmlspecialchars($_POST["password"]) . "'" : "") . " required></input></br>
            <label for='repeatpassword'>Repeat password: </label><input type='password' name='repeatpassword' id='repeatpassword'" . (isset($_POST["repeatpassword"]) ? " value='" . htmlspecialchars($_POST["repeatpassword"]) . "'" : "") . " required></input></br>
            
            <br><b>Settings</b></br>
            <label for='overwrite'>Overwrite old database: </label><input type='checkbox' name='overwrite' id='overwrite'" . (isset($_POST["overwrite"]) ? " checked" : "") . ">
            
            <br><input type='submit' value='Install' class='button'></input>
        </form>
    </div>
";
}

render($content, $title);

?>
