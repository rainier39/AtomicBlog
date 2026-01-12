<?php
// functions.php
// Defines global functions used throughout the software.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// Log a user out.
function logout($redirect=false) {
    session_unset();
    session_destroy();
    if ($redirect) redirect("");
}

// Render a page, placing the header and footer accordingly.
function render(string $content) {
    global $config;
    require "pages/header.php";
    echo($content);
    require "pages/footer.php";
}

// Set the user's CSRF token, overwriting the prior one if any.
function generateCSRFToken() {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Display a blog post tile.
function displayPost($id, $icon, $title, $account) {
    global $db;
    
    $id = (int)$id;
    $formats = array("png", "jpg", "gif", "webp");
    
    $post = "<div class='posts'><a href='/post/" . $id . "/'>";
    // If there is an icon, display it.
    if (in_array($icon, $formats) && file_exists("images/" . $id . "." . $icon)) {
        $post .= "<img src='/images/" . $id . "." . $icon . "'>";
    }
    //else {
    //}
    $post .= "</a></br><a href='" . makeURL("post/" . $id) . "'>" . htmlspecialchars($title) . "</a>";
    // Get the account information of the post author.
    $acc = $db->query("SELECT `name` FROM `accounts` WHERE `id`='" . $db->real_escape_string($account) . "'");
    if ($acc->num_rows > 0) {
        while ($a = $acc->fetch_assoc()) {
            $post .= "</br><small>By: " . htmlspecialchars($a["name"]) . "</small>";
        }
    }
    else {
        $post .= "</br><small>By: Nobody</small>";
    }
    $post .= "</div>";
    
    return $post;
}

// Make a valid (relative) URL for a given page.
function makeURL($page, $direct=false) {
    global $config;
    // If we're using pretty URLs or linking directly to a file.
    if ($config["prettyURLs"] || $direct) {
        return ($config["dir"] != "" ? "/" . $config["dir"] . "/" : "/") . (trim($page, "/"));
    }
    // If not.
    else {
        $trimmed = trim($page, "/");
        if ($trimmed === "") {
            return ($config["dir"] != "" ? "/" . $config["dir"] . "/" : "/");
        }
        else {
            return ($config["dir"] != "" ? "/" . $config["dir"] . "/" : "/") . "index.php?url=" . $trimmed;
        }
    }
}

// Checks to be performed when making/editing posts.
function validatePost() {
    $errors = array();
        	
    // Title.
    if (!isset($_POST["title"]) or (strlen($_POST["title"]) < 1)) {
        $errors[] = "Error: post title cannot be less than 1 character long.";
    }
    elseif (strlen($_POST["title"]) > 32) {
        $errors[] = "Error: post title cannot be more than 32 characters long.";
    }
    // Tags.
    if (!isset($_POST["tags"]) or (strlen($_POST["tags"]) < 1)) {
        $errors[] = "Error: post tags cannot be less than 1 character long.";
    }
    elseif (strlen($_POST["tags"]) > 128) {
        $errors[] = "Error: post tags cannot be more than 128 characters long.";
    }
    // Content.
    if (!isset($_POST["content"]) or (strlen($_POST["content"]) < 1)) {
        $errors[] = "Error: post content cannot be less than 1 character long.";
    }
    elseif (strlen($_POST["content"]) > 65500) {
        $errors[] = "Error: post content cannot be more than 65500 characters long.";
    }
            
    return $errors;
}

// Checks to be performed when setting or changing a username.
function validateUsername($username) {
    global $config, $db;
    $errors = array();
    
    if (strlen($username) < 1) {
        $errors[] = "Your username must be at least 1 character long.";
    }
    elseif (strlen($username) > 32) {
        $errors[] = "Your username cannot be longer than 32 characters.";
    }
    
    if ($config["installed"]) {
        $usernameCheck = $db->query("SELECT 1 FROM `accounts` WHERE `username`='" . $db->real_escape_string($username) . "'");
        if ($usernameCheck->num_rows != 0) {
            $errors[] = "Your username is already taken.";
        }
    }
    
    return $errors;
}

// Checks to be performed when setting or changing an email address.
function validateEmail($email, $takenCheck=false) {
    global $config, $db;
    $errors = array();
    
    // Make sure their email isn't too short.
    if (strlen($email) < 1) {
        $errors[] = "Your email address is too short. Make sure your email address is at least 1 character in length.";
    }
    // Make sure their email isn't too long.
    elseif (strlen($email) > 64) {
        $errors[] = "Your email address is too long. Make sure your email address is no more than 64 characters in length.";
    }
    // Make sure their email is valid.
    // May replace this with custom regex.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Your email address is invalid. Please try entering a valid email address.";
    }
    // Make sure their email isn't taken.
    if ($config["installed"] && $takenCheck) {
        $emailCheck = $db->query("SELECT 1 FROM `accounts` WHERE `email`='" . $db->real_escape_string($email) . "'");
        if ($emailCheck->num_rows != 0) {
            $errors[] = "Your email address is already taken.";
        }
    }
    
    return $errors;
}

// Safely redirect to some page.
function redirect($loc, int $delay=0) {
    global $config;
    // Figure out if we're using HTTP or HTTPS.
    if (isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on")) {
        $proto = "https://";
    }
    else {
        $proto = "http://";
    }
    // Figure out if the blog is installed in a directory.
    if ($config["dir"] != "") {
        $dir = $config["dir"] . "/";
    }
    else {
        $dir = "";
    }
    // If no delay is specified, immediately redirect with the location header.
    if ($delay < 1) {
        if ($config["prettyURLs"]) {
           header("Location: " . $proto . $_SERVER["HTTP_HOST"] . "/" . $dir . ltrim($loc, "/"));
        }
        else {
            header("Location: " . $proto . $_SERVER["HTTP_HOST"] . "/" . $dir . "index.php?url=" . ltrim($loc, "/"));
        }
        exit();
    }
    // If a delay is specified, use the delay with the refresh header.
    else {
        if ($config["prettyURLs"]) {
           header("Refresh: " . $delay . "; url=" . $proto . $_SERVER["HTTP_HOST"] . "/" . $dir . ltrim($loc, "/"));
        }
        else {
            header("Refresh: " . $delay . "; url=" . $proto . $_SERVER["HTTP_HOST"] . "/" . $dir . "index.php?url=" . ltrim($loc, "/"));
        }
    }
}

?>

