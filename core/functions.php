<?php
// functions.php
// Defines global functions used throughout the software.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// Log a user out.
function logout() {
    session_unset();
    session_destroy();
}

// Render a page, placing the header and footer accordingly.
function render(string $content) {
    global $config;
    require "pages/header.php";
    echo($content);
    require "pages/footer.php";
}

?>

