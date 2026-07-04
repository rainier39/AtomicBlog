<?php
/*
 * Copyright © 2025 rainier39 <rainier39@proton.me>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

// functions.php
// Defines global functions used throughout the software.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// Log a user out.
function logout($redirect=false) {
    global $db;
    // Remove any login cookies and purge the database of them too.
    setcookie("AtomicBlog_login", "0", array("expires" => 1));
    $id = $_SESSION["id"] ?? 0;
    $db->query("UPDATE `accounts` SET `cookie`=NULL WHERE `id`='" . $id . "'");
    session_unset();
    session_destroy();
    if ($redirect) redirect("");
}

// Render a page, placing the header and footer accordingly.
function render(string $content, string $htitle="") {
    global $config, $hcontent;
    if ($htitle == "") {
        $htitle = $config["title"];
    }
    else {
        $htitle = $htitle . " - " . $config["title"];
    }
    require "pages/header.php";
    echo($content);
    require "pages/footer.php";
}

// Set the user's CSRF token, overwriting the prior one if any.
function generateCSRFToken() {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Write the current configuration to the config file.
function flushConfig() {
    global $config;
    return file_put_contents("./config/config.php", "<?php\n\nif (!defined('INDEX')) exit;\n\n\$config = " . var_export($config, true) . "\n\n?>\n");
}

// Display a blog post tile.
function displayPost($id, $title, $account) {
    global $db;
    
    $id = (int)$id;
    
    $post = "<td class='postTile'><a href='" . makeURL("post/" . $id) . "'>";

    // Display the post's icon if it exists.
    $uploads = scandir("images/");
    foreach ($uploads as $u) {
        if (str_starts_with($u, $id . ".")) {
            $post .= "<img src='" . makeURL("images/{$u}") . "'>";
            // Just use the first icon we find.
            break;
        }
    }
    $post .= "</a><a href='" . makeURL("post/" . $id) . "'>" . htmlspecialchars($title) . "</a>";
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
    $post .= "</td>";
    
    return $post;
}

function success($message) {
    return "<div class='success'>" . htmlspecialchars($message) . "</div>";
}

function info($message) {
    return "<div class='info'>" . htmlspecialchars($message) . "</div>";
}

function error($message) {
    return "<div class='error'>" . htmlspecialchars($message) . "</div>";
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
function validatePost($edit=false) {
    global $db, $config;
    $errors = array();
        	
    // Title.
    if (!isset($_POST["title"]) or (strlen($_POST["title"]) < 1)) {
        $errors[] = "Post title cannot be less than 1 character long.";
    }
    elseif (strlen($_POST["title"]) > 32) {
        $errors[] = "Post title cannot be more than 32 characters long.";
    }
    // Tags.
    if (!isset($_POST["tags"]) or (strlen($_POST["tags"]) < 1)) {
        $errors[] = "Post tags cannot be less than 1 character long.";
    }
    elseif (strlen($_POST["tags"]) > 128) {
        $errors[] = "Post tags cannot be more than 128 characters long.";
    }
    // Content.
    if (!isset($_POST["content"]) or (strlen($_POST["content"]) < 1)) {
        $errors[] = "Post content cannot be less than 1 character long.";
    }
    elseif (strlen($_POST["content"]) > 65500) {
        $errors[] = "Post content cannot be more than 65500 characters long.";
    }
    
    // Rate limit.
    if ($edit) {
        $lastPost = $db->query("SELECT 1 FROM `posts` WHERE `account`='" . $_SESSION["id"] . "' AND `edittime`>=" . (time()-$config["editDelay"]) . "");
        if ($lastPost->num_rows > 0) {
            $errors[] = "You edited a post too recently. Wait a few seconds and try again.";
        }
    }
    else { 
        $lastPost = $db->query("SELECT 1 FROM `posts` WHERE `account`='" . $_SESSION["id"] . "' AND `starttime`>=" . (time()-$config["postDelay"]) . "");
        if ($lastPost->num_rows > 0) {
            $errors[] = "You made a post too recently. Wait a little while and try again.";
        }
    }
            
    return $errors;
}

// Checks to be performed when setting or changing a name.
function validateName($name) {
    $errors = array();
    
    if (strlen($name) < 1) {
        $errors[] = "Your name must be at least 1 character long.";
    }
    elseif (strlen($name) > 64) {
        $errors[] = "Your name cannot be longer than 64 characters.";
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

// Check whether a user is permitted to perform a given action or not.
function checkPerm($perm) {
    global $permissions, $db;
    
    // If a user is signed in.
    if (isset($_SESSION["logged_in"]) and ($_SESSION["logged_in"] == true)) {
        $roleCheck = $db->query("SELECT `role` FROM `accounts` WHERE `id`='" . $db->real_escape_string($_SESSION["id"]) . "'");
        
        // If the account doesn't exist, deny.
        if ($roleCheck->num_rows < 1) {
            return false;
        }
        
        while ($r = $roleCheck->fetch_assoc()) {
            // If no permissions are defined for the role, deny.
            if (!array_key_exists($r["role"], $permissions)) {
                return false;
            }
            elseif ($permissions[$r["role"]]&$perm) {
                return true;
            }
            else {
                return false;
            }
        }
    }
    // If it's a guest.
    else {
        if ($permissions["Guest"]&$perm) {
            return true;
        }
        else {
            return false;
        }
    }
    
    // Default to false if we somehow fall through to here.
    return false;
}

// Check whether a given target role has a permission or not.
function checkRolePerm($perm, $role) {
    global $permissions;
    
    // If no permissions are defined for the role, deny.
    if (!array_key_exists($role, $permissions)) {
        return false;
    }
    elseif ($permissions[$role]&$perm) {
        return true;
    }
    else {
        return false;
    }
}

// Upload an image given its name in $_FILES, and what it should be named.
function upload($file, $name) {
    global $config;
    
    $upload_dir = "images/";
    
    if (!extension_loaded("gd")) {
        return "PHP GD is not enabled.";
    }
    
    if (!is_writable($upload_dir)) {
        return "Upload failed, image directory isn't writable.";
    }
    elseif ($_FILES[$file]["size"] < 1) {
        return "Upload failed, content empty (file may be too large).";
    }
    elseif (!file_exists($_FILES[$file]["tmp_name"])) {
        return "Upload failed, file likely too large or non-existent.";
    }
    elseif ($_FILES[$file]["size"] > $config["maxUploadSize"]) {
        return "Upload failed, file too large.";
    }
    // Basic sanity check, not intended as a true security measure.
    elseif (false === getimagesize($_FILES[$file]["tmp_name"])) {
        return "Upload failed, invalid image.";
    }
    
    // Figure out what kind of image we are dealing with by reading the magic bytes.
    $bytes = file_get_contents($_FILES[$file]["tmp_name"], false, null, 0, 12);
    
    if ($bytes === false) {
        return "Upload failed, didn't recognize image type.";
    }
    
    // GIFs.
    if (str_starts_with($bytes, hex2bin("474946383761")) or str_starts_with($bytes, hex2bin("474946383961"))) {
        $image = imagecreatefromgif($_FILES[$file]["tmp_name"]);
        
        // This is safe because we never use any user-supplied value in $name.
        $target = $upload_dir . $name . ".gif";
        
        if ($image === false) {
            return "Upload failed, invalid GIF image.";
        }
        
        // TODO: enforce disk quotas, rate limits.
        
        $success = imagegif($image, $target);
    
        if ($success) {
            return "";
        }
        else {
            return "Failed to write GIF image to file.";
        }
    }
    // JPEGs. (technically signature analysis could be tighter, as in the above GIF example)
    elseif (str_starts_with($bytes, hex2bin("FFD8FF"))) {
        $image = imagecreatefromjpeg($_FILES[$file]["tmp_name"]);
        
        // This is safe because we never use any user-supplied value in $name.
        $target = $upload_dir . $name . ".jpg";
        
        if ($image === false) {
            return "Upload failed, invalid JPEG image.";
        }
        
        // TODO: enforce disk quotas, rate limits.
        
        $success = imagejpeg($image, $target);
    
        if ($success) {
            return "";
        }
        else {
            return "Failed to write JPEG image to file.";
        }
    }
    // PNGs.
    elseif (str_starts_with($bytes, hex2bin("89504E470D0A1A0A"))) {
        $image = imagecreatefrompng($_FILES[$file]["tmp_name"]);
        
        // This is safe because we never use any user-supplied value in $name.
        $target = $upload_dir . $name . ".png";
        
        if ($image === false) {
            return "Upload failed, invalid PNG image.";
        }
        
        // TODO: enforce disk quotas, rate limits.
        
        $success = imagepng($image, $target);
    
        if ($success) {
            return "";
        }
        else {
            return "Failed to write PNG image to file.";
        }
    }
    // WEBPs.
    elseif (str_starts_with($bytes, hex2bin("52494646")) and str_ends_with($bytes, hex2bin("57454250"))) {
        $image = imagecreatefromwebp($_FILES[$file]["tmp_name"]);
        
        // This is safe because we never use any user-supplied value in $name.
        $target = $upload_dir . $name . ".webp";
        
        if ($image === false) {
            return "Upload failed, invalid WEBP image.";
        }
        
        // TODO: enforce disk quotas, rate limits.
        
        $success = imagewebp($image, $target);
    
        if ($success) {
            return "";
        }
        else {
            return "Failed to write WEBP image to file.";
        }
    }
    else {
        return "Upload failed, unsupported or unrecognized image type.";
    }
}

function markdownButtons() {
    return "<script src='" . makeURL("javascript/markdownbuttons.js") . "'></script>
    <label></label>
    <a class='button' onclick='format(\"bold\")'><b>B</b></a>
    <a class='button' onclick='format(\"italic\")'><i>i</i></a>
    <a class='button' onclick='format(\"codeblock\")'>&lt;/&gt;</a>
    <a class='button' onclick='format(\"link\")'>link</a>
    <a class='button' onclick='format(\"image\")'>img</a>
    <br>";
}

?>

