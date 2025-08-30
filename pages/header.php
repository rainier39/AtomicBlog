<?php
// header.php
// Serves the header.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

echo("<!DOCTYPE html>
<html lang='en'>
<meta charset='UTF-8'>
<title>" . htmlspecialchars($config["title"]) . "</title>
<meta name='viewport' content='width=device-width,initial-scale=1'>
<link rel='stylesheet' href='/themes/" . htmlspecialchars($config["theme"]) . "/theme.css'>
<link rel='icon' type='image/x-icon' href='/themes/" . htmlspecialchars($config["theme"]) . "/icon.png'>
<body>");

echo("<div class='header'><b>" . htmlspecialchars($config["title"]) . "</b></br><small>" . htmlspecialchars($config["description"]) . "</small></div>");

if ($config["installed"]) {
    echo("<div class='navbar'><a href='/'>Home</a> <a href='/posts/'>Posts</a> <a href='/about/'>About</a> <a href='/contact/'>Contact</a>" . ((isset($_SESSION["logged_in"]) && $_SESSION["logged_in"]) ? " <a href='/panel/'>Panel</a> <a href='/logout/'>Logout</a>" : " <a href='/login/'>Login</a> <a href='/register/'>Register</a>") . "</div>");
}

echo("<div class='content'>");

?>

