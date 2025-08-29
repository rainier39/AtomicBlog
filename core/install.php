<?php
// install.php
// Installs the software.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$content = "";

// Handle requests.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Stop if the config file isn't writable.
    if (!is_writable("./core/config.php")) {
        $content .= "Cannot install, config file isn't writable.";
    }
    // Stop if any of the SQL details aren't set.
    elseif (!isset($_POST["SQLServer"])) {
        $content .= "Cannot install, SQLServer field cannot be blank.";
    }
    elseif (!isset($_POST["SQLUsername"])) {
        $content .= "Cannot install, SQLUsername field cannot be blank.";
    }
    elseif (!isset($_POST["SQLPassword"])) {
        $content .= "Cannot install, SQLPassword field cannot be blank.";
    }
    elseif (!isset($_POST["SQLDatabase"])) {
        $content .= "Cannot install, SQLDatabase field cannot be blank.";
    }
    // Stop if any of the SQL details are blank.
    elseif ($_POST["SQLServer"] == "") {
        $content .= "Cannot install, SQLServer field cannot be blank.";
    }
    elseif ($_POST["SQLUsername"] == "") {
        $content .= "Cannot install, SQLUsername field cannot be blank.";
    }
    elseif ($_POST["SQLPassword"] == "") {
        $content .= "Cannot install, SQLPassword field cannot be blank.";
    }
    elseif ($_POST["SQLDatabase"] == "") {
        $content .= "Cannot install, SQLDatabase field cannot be blank.";
    }
    else {
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

        // Write the administrator account.
        $db->query("INSERT INTO `accounts` (username, email, password, birthday, name, role, ip, useragent, jointime, lastactive) VALUES ('" . $db->real_escape_string($_POST["username"]) . "', '" . $db->real_escape_string($_POST["email"]) . "', '" . $db->real_escape_string(password_hash($_POST["password"], PASSWORD_DEFAULT)) . "', '00000000', 'Owner', 'Owner', '" . ip2long($_SERVER["REMOTE_ADDR"]) . "', '" . $db->real_escape_string($_SERVER["HTTP_USER_AGENT"]) . "', '" . $db->real_escape_string(time()) . "', '" . $db->real_escape_string(time()) . "')");

        // Create a new array of our new config values.
        $newConfig = array("installed" => true, "SQLServer" => $_POST["SQLServer"], "SQLDatabase" => $_POST["SQLDatabase"], "SQLUsername" => $_POST["SQLUsername"], "SQLPassword" => $_POST["SQLPassword"], "title" => $_POST["title"], "description" => $_POST["description"]);

        // Merge the old config array with the new one.
        $newConfig = array_merge($config, $newConfig);

        // Write the new config array to the config file.
        file_put_contents("./core/config.php", "<?php\n\nif (!defined('INDEX')) exit;\n\n\$config = " . var_export($newConfig, true) . "\n\n?>\n");

        // Print a message that the software has been installed.
        $content .= "Software successfully installed!";
    }
}

// Display the install page form.
else {
$content .= 
    "<div class='installForm'>
        <h2>Installer</h2>
        <form method='post'>
            <b>SQL Details</b></br>
            <label>SQL Server: </label><input type='text' name='SQLServer'></input></br>
            <label>SQL Database: </label><input type='text' name='SQLDatabase'></input></br>
            <label>SQL Username: </label><input type='text' name='SQLUsername'></input></br>
            <label>SQL Password: </label><input type='text' name='SQLPassword'></input></br>

            <br><b>Blog Configuration</b></br>
            <label>Blog Title: </label><input type='text' name='title' maxlength='32'></input></br>
            <label>Blog Description: </label><textarea name='description' maxlength='2048'></textarea></br>

            <br><b>Administrator Account Details</b></br>
            <label>Username: </label><input type='text' name='username' autocomplete='username' maxlength='32'></input></br>
            <label>Email Address: </label><input type='email' name='email' maxlength='64'></input></br>
            <label>Password: </label><input type='password' name='password'></input></br>
            <label>Repeat password: </label><input type='password' name='repeatpassword'></input></br>
            <br><input type='submit' value='Install' id='button'></input>
        </form>
    </div>
";
}

render($content);

?>

