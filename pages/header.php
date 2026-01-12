<?php
// header.php
// Serves the header.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// Header content.
$hcontent = "";

$hcontent .= "<!DOCTYPE html>
<html lang='en'>
<meta charset='UTF-8'>
<title>" . htmlspecialchars($htitle) . "</title>
<meta name='viewport' content='width=device-width,initial-scale=1'>
<link rel='stylesheet' href='" . makeURL("themes/" . htmlspecialchars($config["theme"]) . "/theme.css", true) . "'>
<link rel='icon' type='image/x-icon' href='" . makeURL("themes/" . htmlspecialchars($config["theme"]) . "/icon.png", true) . "'>
<body>";

$hcontent .= "<div class='header'><b>" . htmlspecialchars($config["title"]) . "</b></br><small>" . htmlspecialchars($config["description"]) . "</small></div>";

if ($config["installed"]) {
    $hcontent .= "<div class='navbar'><a href='" . makeURL("") . "'>Home</a> <a href='" . makeURL("posts") . "'>Posts</a>";
    if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"]) {
        $hcontent .= " <a href='" . makeURL("panel") . "'>Panel</a> <a href='" . makeURL("logout") . "'>Logout</a>";
    }
    else {
        $hcontent .= " <a href='" . makeURL("login") . "'>Login</a>";
        if ($config["allowRegistration"]) {
            $hcontent .= " <a href='" . makeURL("register") . "'>Register</a>";
        }
    }
    $hcontent .= "</div>";
}

$hcontent .= "<div class='content'>";

echo($hcontent);

?>

