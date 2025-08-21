<?php
// formatter.php
// Formats content in a bbcode style.
// Is currently deprecated, will be replaced with a markdown implementation.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// Format a given string.
function format($string) {
    // Make sure to sanitize the string first to prevent XSS.
    $string = htmlspecialchars($string);

    // Apply the various formatter functions to the string.
    $string = format_bold($string);
    $string = format_p($string);
    $string = format_link($string);
    $string = format_br($string);
    $string = format_img($string);

    // In the end, return the string.
    return $string;
}

// Format bold tags.
function format_bold($string) {
    return preg_replace("/\[b\](.+?)\[\/b\]/is", "<b>$1</b>", $string);
}

// Format paragraph breaks.
function format_p($string) {
    return str_replace("[/p]", "</p>", $string);
}

// Format links.
function format_link($string) {
    $string = preg_replace("/\[url=(http|ftp|https):\/\/(.+?)\](.+?)\[\/url\]/is", "<a href='$1://$2' target='_blank'>$3</a>", $string);
    $string = preg_replace("/\[url=(.+?)\](http|ftp|https):\/\/(.+?)\[\/url\]/is", "<a href='$2://$3' target='_blank'>$1</a>", $string);
    $string = preg_replace("/\[url\](http|ftp|https):\/\/(.+?)\[\/url\]/is", "<a href='$1://$2' target='_blank'>$2</a>", $string);
    return $string;
}

// Format line breaks.
function format_br($string) {
    return str_replace("[/br]", "</br>", $string);
}

// Format images.
function format_img($string) {
    return preg_replace("/\[img\](http|https):\/\/(.+?)\[\/img\]/is", "<img id='imgPost' src='$1://$2'>", $string);
}

?>

