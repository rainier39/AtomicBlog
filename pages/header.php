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
<body>

<div class='header'><img src='/themes/" . htmlspecialchars($config["theme"]) . "/flag.png'> <b>" . htmlspecialchars($config["title"]) . "</b></br><small>" . htmlspecialchars($config["description"]) . "</small></div>

<div class='navbar'><a href='/'>Home</a> <a href='/posts/'>Posts</a> <a href='/about/'>About</a> <a href='/contact/'>Contact</a></div>

<div class='content'>");

?>

