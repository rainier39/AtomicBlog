<?php
// config.php
// Stores the configuration for the software.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$config = array(
    "installed" => false,
    "SQLServer" => "",
    "SQLDatabase" => "",
    "SQLUsername" => "",
    "SQLPassword" => "",
    "title" => "AtomicBlog",
    "description" => "A lightweight, easy to use, and easy to administrate blogging software.",
    "footer" => "Powered by AtomicBlog",
    "theme" => "Light",
    "language" => "english",
    "userlist" => true,
    "https" => false,
    "prettyURLs" => false
);

?>

