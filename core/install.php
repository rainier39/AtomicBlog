<?php
// install.php
// Installs the software.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$content = "";

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
    
    // Stop if the config file isn't writable.
    if (!is_writable("./core/config.php")) {
        $errors[] = "Cannot install, config file isn't writable.";
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
    // Stop if the username is too long or too short.
    if ((!isset($_POST["username"])) or (strlen($_POST["username"]) < 1)) {
        $errors[] = "Cannot install, username must be at least 1 character long.";
    }
    elseif (strlen($_POST["username"]) > 32) {
        $errors[] = "Cannot install, username cannot be longer than 32 characters.";
    }
    // Stop if the email is too long or too short.
    if ((!isset($_POST["email"])) or (strlen($_POST["email"]) < 1)) {
        $errors[] = "Cannot install, email must be at least 1 character long.";
    }
    elseif (strlen($_POST["email"]) > 64) {
        $errors[] = "Cannot install, email cannot be longer than 64 characters.";
    }
    // TODO: make sure email looks valid.
    // Stop if the password(s) is/are too short.
    if ((!isset($_POST["password"])) or (strlen($_POST["password"]) < 8)) {
        $errors[] = "Cannot install, password must be at least 8 characters long.";
    }
    if ((!isset($_POST["repeatpassword"])) or ($_POST["password"] !== $_POST["repeatpassword"])) {
        $errors[] = "Cannot install, passwords do not match.";
    }
    // TODO: enforce more advanced, stringent password requirements.
    
    // If there are no errors, install.
    if (count($errors) === 0) {
        // Connect to the database with the given credentials.
        try {
            $db = mysqli_connect($_POST["SQLServer"], $_POST["SQLUsername"], $_POST["SQLPassword"], $_POST["SQLDatabase"]);
        }
        catch (Exception $e) {
            $content .= "Database Connection Error: " . $e->getMessage();
            require "pages/footer.php";
            exit();
        }

        // Write the database.
        $db->query("CREATE TABLE IF NOT EXISTS `accounts` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `username` varchar(32) NOT NULL,
            `email` varchar(64) NOT NULL,
            `password` varchar(128) NOT NULL,
            `birthday` char(8) NOT NULL,
            `name` varchar(64) NOT NULL,
            `role` enum('Owner', 'Member', 'Unapproved') NOT NULL DEFAULT 'Unapproved',
            `ip` int unsigned NOT NULL,
            `useragent` varchar(128) NOT NULL,
            `jointime` int unsigned NOT NULL,
            `lastactive` int unsigned NOT NULL,
            `namevisible` tinyint(1) NOT NULL DEFAULT '0',
            `birthdayvisible` tinyint(1) NOT NULL DEFAULT '0',
            `agevisible` tinyint(1) NOT NULL DEFAULT '0',
            `emailvisible` tinyint(1) NOT NULL DEFAULT '0',
            `bio` varchar(4096) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;");

        $db->query("CREATE TABLE IF NOT EXISTS `posts` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `title` varchar(32) NOT NULL,
            `tags` varchar(128) NOT NULL,
            `content` text NOT NULL,
            `account` int unsigned NOT NULL,
            `startip` int unsigned NOT NULL,
            `startuseragent` varchar(128) NOT NULL,
            `starttime` int unsigned NOT NULL,
            `editip` int unsigned NOT NULL,
            `edituseragent` varchar(128) NOT NULL,
            `editedby` int unsigned NOT NULL,
            `edittime` int unsigned NOT NULL,
            `icon` enum('none', 'png', 'jpg', 'gif') NOT NULL DEFAULT 'none',
            `published` tinyint(1) NOT NULL DEFAULT '0',
            `starred` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;");

        $db->query("CREATE TABLE IF NOT EXISTS `comments` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `account` int unsigned NOT NULL,
            `email` varchar(64) NOT NULL,
            `ip` int unsigned NOT NULL,
            `useragent` varchar(128) NOT NULL,
            `timestamp` int unsigned NOT NULL,
            `content` text NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;");

        $db->query("CREATE TABLE IF NOT EXISTS `views` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `ip` int unsigned NOT NULL,
            `useragent` varchar(128) NOT NULL,
            `timestamp` int unsigned NOT NULL,
            `post` int unsigned NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;");

        $db->query("CREATE TABLE IF NOT EXISTS `extensions` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(32) NOT NULL,
            `author` varchar(32) NOT NULL,
            `version` varchar(16) NOT NULL,
            `description` varchar(128) NOT NULL,
            `enabled` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;");

        // Write the administrator account. Will replace any existing account with the same username or email (I.E. there is an old install of the software).
        $db->query("REPLACE INTO `accounts` (username, email, password, birthday, name, role, ip, useragent, jointime, lastactive) VALUES ('" . $db->real_escape_string($_POST["username"]) . "', '" . $db->real_escape_string($_POST["email"]) . "', '" . $db->real_escape_string(password_hash($_POST["password"], PASSWORD_DEFAULT)) . "', '00000000', 'Owner', 'Owner', '" . ip2long($_SERVER["REMOTE_ADDR"]) . "', '" . $db->real_escape_string($_SERVER["HTTP_USER_AGENT"]) . "', '" . $db->real_escape_string(time()) . "', '" . $db->real_escape_string(time()) . "')");

        // Create a new array of our new config values.
        $newConfig = array("installed" => true, "SQLServer" => $_POST["SQLServer"], "SQLDatabase" => $_POST["SQLDatabase"], "SQLUsername" => $_POST["SQLUsername"], "SQLPassword" => $_POST["SQLPassword"], "title" => $_POST["title"], "description" => $_POST["description"]);

        // Merge the old config array with the new one.
        $config = array_merge($config, $newConfig);

        // Write the new config array to the config file.
        file_put_contents("./core/config.php", "<?php\n\nif (!defined('INDEX')) exit;\n\n\$config = " . var_export($config, true) . "\n\n?>\n");

        // Print a message that the software has been installed.
        $content .= "Software successfully installed!";
    }
    // Otherwise, display the errors.
    else {
        foreach ($errors as $e) {
            $content .= "<div class='error'>" . $e . "</div>";
        }
    }
}

// Display the install page form.
if (!$config["installed"]) {
$content .= 
    "<div class='installForm'>
        <h2>Installer</h2>
        <form method='post'>
            <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
            <b>SQL Details</b></br>
            <label>SQL Server: </label><input type='text' name='SQLServer'" . (isset($_POST["SQLServer"]) ? " value='" . htmlspecialchars($_POST["SQLServer"]) . "'" : "") . "></input></br>
            <label>SQL Database: </label><input type='text' name='SQLDatabase'" . (isset($_POST["SQLDatabase"]) ? " value='" . htmlspecialchars($_POST["SQLDatabase"]) . "'" : "") . "></input></br>
            <label>SQL Username: </label><input type='text' name='SQLUsername'" . (isset($_POST["SQLUsername"]) ? " value='" . htmlspecialchars($_POST["SQLUsername"]) . "'" : "") . "></input></br>
            <label>SQL Password: </label><input type='text' name='SQLPassword'" . (isset($_POST["SQLPassword"]) ? " value='" . htmlspecialchars($_POST["SQLPassword"]) . "'" : "") . "></input></br>

            <br><b>Blog Configuration</b></br>
            <label>Blog Title: </label><input type='text' name='title' maxlength='32'" . (isset($_POST["title"]) ? " value='" . htmlspecialchars($_POST["title"]) . "'" : "") . "></input></br>
            <label>Blog Description: </label><textarea name='description' maxlength='128'>" . (isset($_POST["description"]) ? htmlspecialchars($_POST["description"]) : "") . "</textarea></br>

            <br><b>Administrator Account Details</b></br>
            <label>Username: </label><input type='text' name='username' autocomplete='username' maxlength='32'" . (isset($_POST["username"]) ? " value='" . htmlspecialchars($_POST["username"]) . "'" : "") . "></input></br>
            <label>Email Address: </label><input type='email' name='email' maxlength='64'" . (isset($_POST["email"]) ? " value='" . htmlspecialchars($_POST["email"]) . "'" : "") . "></input></br>
            <label>Password: </label><input type='password' name='password'" . (isset($_POST["password"]) ? " value='" . htmlspecialchars($_POST["password"]) . "'" : "") . "></input></br>
            <label>Repeat password: </label><input type='password' name='repeatpassword'" . (isset($_POST["repeatpassword"]) ? " value='" . htmlspecialchars($_POST["repeatpassword"]) . "'" : "") . "></input></br>
            <br><input type='submit' value='Install' id='button'></input>
        </form>
    </div>
";
}

render($content);

?>
