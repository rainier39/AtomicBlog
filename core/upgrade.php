<?php
// upgrade.php
// Performs any necessary database alterations to upgrade the software.

if ($config["version"] != VERSION) {
    if ($config["version"] == "v2.0.0-alpha") {
        // accounts table changes.
        $db->query("ALTER TABLE `accounts` MODIFY COLUMN `role` enum('Owner', 'Moderator', 'Member', 'Suspended', 'Unapproved') NOT NULL DEFAULT 'Unapproved'");
        $db->query("ALTER TABLE `accounts` MODIFY COLUMN `ip` varchar(45) NOT NULL");
        $db->query("ALTER TABLE `accounts` MODIFY COLUMN `jointime` bigint NOT NULL");
        $db->query("ALTER TABLE `accounts` MODIFY COLUMN `lastactive` bigint NOT NULL");
        $db->query("ALTER TABLE `accounts` ADD `joinip` varchar(45) NOT NULL");
        // posts table changes.
        $db->query("ALTER TABLE `posts` MODIFY COLUMN `starttime` bigint NOT NULL");
        $db->query("ALTER TABLE `posts` MODIFY COLUMN `edittime` bigint NOT NULL");
        // comments table changes.
        $db->query("ALTER TABLE `comments` MODIFY COLUMN `ip` varchar(45) NOT NULL");
        $db->query("ALTER TABLE `comments` MODIFY COLUMN `timestamp` bigint NOT NULL");
        // views table changes.
        $db->query("ALTER TABLE `views` MODIFY COLUMN `ip` varchar(45) NOT NULL");
        $db->query("ALTER TABLE `views` MODIFY COLUMN `timestamp` bigint NOT NULL");
        $db->query("ALTER TABLE `views` DROP COLUMN id");
        // Add logins table.
        $db->query("CREATE TABLE IF NOT EXISTS `logins` (
            `ip` varchar(45) NOT NULL,
            `timestamp` bigint NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Bump the version.
        $config["version"] = "newversionplaceholder";
    }
    
    // Write the new config to a file.
    flushConfig();
}

?>
