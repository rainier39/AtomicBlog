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
    $string = format_list($string);
    $string = format_numeric_list($string);
    $string = format_bold($string);
    $string = format_italic($string);
    $string = format_code_block($string);
    $string = format_inline_code($string);
    $string = format_heading($string);
    $string = format_subheading($string);
    $string = format_horizontal_rule($string);
    $string = format_blockquote($string);
    $string = format_image($string);
    $string = format_link($string);
    $string = format_paragraphs($string);
    $string = format_linebreaks($string);

    // In the end, return the string.
    return $string;
}

// Define the various formatter functions.
function format_list($string) {
    return preg_replace("/^\*\s+(.+?)$|^-\s+(.+?)$/mis", "<li>$1$2</li>", $string);
}

function format_numeric_list($string) {
    return preg_replace("/^\d+\.\s+(.+?)$|^\d+\)\s+(.+?)$/mis", "<ol start='$0'><li>$1$2</li></ol>", $string);
}

function format_bold($string) {
    return preg_replace("/\*\*(.+?)\*\*|__(.+?)__/is", "<b>$1$2</b>", $string);
}

function format_italic($string) {
    return preg_replace("/\*(.+?)\*|_(.+?)_/is", "<i>$1$2</i>", $string);
}

function format_code_block($string) {
    return preg_replace("/```(.+?)```/is", "<div class='codeblock'>$1</div>", $string);
}

function format_inline_code($string) {
    return preg_replace("/`(.+?)`/is", "<code>$1</code>", $string);
}

function format_heading($string) {
    return preg_replace("/^#\s+(.+?)$/mis", "<h1>$1</h1>", $string);
}

function format_subheading($string) {
    return preg_replace("/^##\s+(.+?)$/mis", "<h2>$1</h2>", $string);
}

function format_horizontal_rule($string) {
    return preg_replace("/---/is", "<hr>", $string);
}

function format_blockquote($string) {
    return preg_replace("/^&gt;\s+(.+?)$/mis", "<blockquote>$1</blockquote>", $string);
}

function format_image($string) {
    return preg_replace("/!\[(.+?)\]\((http|https):\/\/(.+?)\)/is", "<img src='$2://$3' alt='$1'>", $string);
}

function format_link($string) {
    return preg_replace("/\[(.+?)\]\((http|https):\/\/(.+?)\)/is", "<a href='$2://$3' target='_blank' rel='nofollow'>$1</a>", $string);
}

function format_paragraphs($string) {
    return preg_replace("/\r\n\r\n|\r\r|\n\n/is", "</p>", $string);
}

function format_linebreaks($string) {
    return preg_replace("/\s\s$/mis", "</br>", $string);
}

?>
