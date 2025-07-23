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

?>

