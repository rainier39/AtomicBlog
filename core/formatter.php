<?php
// formatter.php
// Formats content with custom markdown implementation.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// Format a given string.
function format($string) {
    // Make sure to sanitize the string first to prevent XSS.
    $string = htmlspecialchars($string);

    // Apply the various formatter functions to the string.
    $string = format_bold($string);
    $string = format_italic($string);
    $string = format_inline_code($string);
    $string = format_horizontal_rule($string);

    // In the end, return the string.
    return $string;
}

// Define the various formatter functions.
function format_bold($string) {
    return preg_replace("/\*\*(.+?)\*\*|__(.+?)__/is", "<b>$1$2</b>", $string);
}

function format_italic($string) {
    return preg_replace("/\*(.+?)\*|_(.+?)_/is", "<i>$1$2</i>", $string);
}

function format_inline_code($string) {
    return preg_replace("/`(.+?)`/is", "<code>$1</code>", $string);
}

function format_horizontal_rule($string) {
    return preg_replace("/\-\-\-/is", "<hr>", $string);
}

?>
