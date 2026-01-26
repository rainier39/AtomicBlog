<?php
// panel.php
// Serves as an all-purpose utility for the blog administrator and users.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$content = "";
$title = "";

// If the user isn't logged in, don't let them into the panel.
if (!isset($_SESSION["logged_in"]) or ($_SESSION["logged_in"] !== true)) {
    $content .= "<div class='error'>You must be logged in to access this page.</div>";
}
// Display the default page.
elseif (!isset($url[1]) or $url[1] == "") {
    $title = "Panel";
    $content .= "<div class='panelcontent'>";
    $content .= "<h1>Panel</h1>";
    $content .= "<h2>User Actions</h2>";
    if (checkPerm(PERM_NEW_POST)) {
        $content .= "<a href='" . makeURL("panel/newpost") . "'>Create a new post</a>";
    }
    $content .= "</div>";
}
// Direct the user to the "create a new post" page.
elseif ($url[1] == "newpost") {
    require "panel/newpost.php";
}
// Display an error page.
else {
    $content .= "<div class='error'>The page you requested doesn't exist.</div>";
}

render($content, $title);

?>

