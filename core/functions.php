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
    setcookie($config["cookiePrefix"] . "login", "0", array("expires" => 1));
    $id = $_SESSION["id"] ?? 0;
    // For the weird case of a user being logged in, but no database connection.
    if ($db) {
        $db->query("UPDATE `accounts` SET `cookie`=NULL WHERE `id`='" . $id . "'");
    }
    session_unset();
    session_destroy();
    if ($redirect) redirect("");
}

// Render a page, placing the header and footer accordingly.
function render_page($templatename, $templatevars, string $htitle="") {
    global $config, $hcontent, $messages;
    if ($htitle == "") {
        $htitle = $config["title"];
    }
    else {
        $htitle = $htitle . " - " . $config["title"];
    }
    require "pages/header.php";
    render_template($templatename, $templatevars);
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
function displayPost($id, $title, $account, $starred, $published) {
    global $db;
    
    $id = (int)$id;
    $account = (int)$account;
    
    $postTilevars = array("url" => makeURL("post/" . $id),
    "image" => "",
    "title" => $title,
    "author" => "Nobody",
    "classes" => "");
    
    if ($starred) {
        $postTilevars["classes"] = " postTileStarred";
    }
    if (!$published) {
        $postTilevars["classes"] .= " postTileUnpublished";
    }

    // Display the post's icon if it exists.
    $uploads = scandir("images/");
    foreach ($uploads as $u) {
        if (str_starts_with($u, $id . ".")) {
            $postTilevars["image"] = "<img src='" . makeURL("images/{$u}") . "?" . time() . "'>";
            // Just use the first icon we find.
            break;
        }
    }

    // Get the account information of the post author.
    $acc = $db->query("SELECT `name`, `namevisible` FROM `accounts` WHERE `id`='" . $db->real_escape_string($account) . "'");
    if ($acc->num_rows > 0) {
        while ($a = $acc->fetch_assoc()) {
            if ($a["namevisible"]) {
                $postTilevars["author"] = "<a class='profileLink' href='" . makeURL("profile/" . $account) . "'>" . htmlspecialchars($a["name"]) . "</a>";
            }
            else {
                $postTilevars["author"] = "Anonymous";
            }
        }
    }
    
    return render_template("postTile.html", $postTilevars, false);
}

function success($message) {
    return "<div class='message success'>" . htmlspecialchars($message) . "</div>";
}

// This function does not htmlspecialchars() its content, so user supplied input needs to be escaped by the caller beforehand.
function unsafe_success($message) {
    return "<div class='message success'>" . $message . "</div>";
}

function info($message) {
    return "<div class='message info'>" . htmlspecialchars($message) . "</div>";
}

function error($message) {
    return "<div class='message error'>" . htmlspecialchars($message) . "</div>";
}

// Make a valid (relative) URL for a given page.
function makeURL($page, $direct=false) {
    global $config;
    // If we're using pretty URLs or linking directly to a file.
    if ($config["prettyURLs"] or $direct) {
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
    if (strlen($_POST["title"] ?? "") < 1) {
        $errors[] = "Post title cannot be less than 1 character long.";
    }
    elseif (strlen($_POST["title"]) > 32) {
        $errors[] = "Post title cannot be more than 32 characters long.";
    }
    // Tags.
    if (strlen($_POST["tags"] ?? "") > 128) {
        $errors[] = "Post tags cannot be more than 128 characters long.";
    }
    // Content.
    if (strlen($_POST["content"] ?? "") < 1) {
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
    if ($config["installed"] and $takenCheck) {
        $emailCheck = $db->query("SELECT 1 FROM `accounts` WHERE `email`='" . $db->real_escape_string($email) . "'");
        if ($emailCheck->num_rows != 0) {
            $errors[] = "Your email address is already taken.";
        }
    }
    
    return $errors;
}

function validatePassword($password) {
    $errors = array();
    
    $passwordLen = strlen($password);
    
    // Enforce minimum password length requirement.
    if ($passwordLen < MIN_PASSWORD_LENGTH) {
        $errors[] = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters long.";
    }
    
    // Protect against mostly numeric passwords (these will be relatively weak).
    $numbers = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
    $numberCount = 0;
    foreach ($numbers as $number) {
        $numberCount += substr_count($password, $number);
    }
    $otherCount = $passwordLen - $numberCount;
    if ($numberCount > $otherCount) {
        $errors[] = "More than half of your password cannot be numeric.";
    }

    return $errors;
}

// Safely redirect to some page.
function redirect($loc, int $delay=0) {
    global $config;
    // Figure out if we're using HTTP or HTTPS.
    if (($_SERVER["HTTPS"] ?? "") == "on") {
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
    
    // In the weird case of a user being logged in, but no database connection.
    if (!$db) {
        return false;
    }
    
    // If a user is logged in.
    if ($_SESSION["logged_in"]) {
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

// Whether or not one user outranks the other.
function checkOutrank($actinguserid, $targetuserid) {
    global $db, $permissions;
    
    // Default to Guest, since deleted accounts should have no rights.
    $actinguserrole = "Guest";
    $targetuserrole = "Guest";
    
    $actinguser = $db->query("SELECT `role` FROM `accounts` WHERE `id`='" . $db->real_escape_string($actinguserid) . "'");
    $targetuser = $db->query("SELECT `role` FROM `accounts` WHERE `id`='" . $db->real_escape_string($targetuserid) . "'");
    
    while ($a = $actinguser->fetch_assoc()) {
        $actinguserrole = $a["role"];
    }
    while ($t = $targetuser->fetch_assoc()) {
        $targetuserrole = $t["role"];
    }
    
    if ($permissions[$actinguserrole] > $permissions[$targetuserrole]) {
        return true;
    }
    else {
        return false;
    }
}

// Upload an image given its name in $_FILES, and what it should be named.
function upload($file, $name) {
    global $config, $db;
    
    $upload_dir = "images/";
    
    if (!extension_loaded("gd")) {
        return "PHP GD is not enabled.";
    }
    if (!is_writable($upload_dir)) {
        return "Upload failed, image directory isn't writable.";
    }
    if ($_FILES[$file]["size"] < 1) {
        return "Upload failed, content empty (file may be too large).";
    }
    if (!file_exists($_FILES[$file]["tmp_name"])) {
        return "Upload failed, file likely too large or non-existent.";
    }
    if ($_FILES[$file]["size"] > $config["maxUploadSize"]) {
        return "Upload failed, file too large.";
    }
    // Basic sanity check, not intended as a true security measure.
    if (false === getimagesize($_FILES[$file]["tmp_name"])) {
        return "Upload failed, invalid image.";
    }
    
    // Figure out what kind of image we are dealing with by reading the magic bytes.
    $bytes = file_get_contents($_FILES[$file]["tmp_name"], false, null, 0, 12);
    
    if ($bytes === false) {
        return "Upload failed, didn't recognize image type.";
    }
    
    // Get the current user's disk quota.
    $userQuota = $db->query("SELECT `quota` FROM `accounts` WHERE `id`='" . $_SESSION["id"] . "'");
    $uq = (int)$userQuota->fetch_assoc()["quota"];

    $globalQuota = $db->query("SELECT SUM(`quota`) AS `total` FROM `accounts`");
    $gq = (int)$globalQuota->fetch_assoc()["total"];
    
    // Enforce disk quotas.
    if (($uq + $_FILES[$file]["size"]) > $config["perUserDiskQuota"]) {
        return "Upload failed, your disk quota would be exceeded.";
    }
    if (($gq + $_FILES[$file]["size"]) > $config["totalDiskQuota"]) {
        return "Upload failed, the total disk quota would be exceeded.";
    }
    
    $overwriting = false;
    
    // GIFs.
    if (str_starts_with($bytes, hex2bin("474946383761")) or str_starts_with($bytes, hex2bin("474946383961"))) {
        //$image = imagecreatefromgif($_FILES[$file]["tmp_name"]);
        
        // This is safe because we never use any user-supplied value in $name.
        $target = $upload_dir . $name . ".gif";
        
        if (is_file($target)) {
            $overwriting = true;
            $oldsize = filesize($target);
        }
        
        //if ($image === false) {
        //    return "Upload failed, invalid GIF image.";
        //}
        
        // TODO: enforce rate limits.
        
        //$success = imagegif($image, $target);
        // Just accepting the file as-is may have security implications. Though it allows users to upload animated GIFs.
        $success = move_uploaded_file($_FILES[$file]["tmp_name"], $target);
    
        if (!$success) {
            return "Failed to write image to file.";
        }
        // We don't do a final disk quota check here because we just took the GIF wholesale so the size hasn't changed since the initial check.
        // Add the new filesize to the user's quota.
        $db->query("UPDATE `accounts` SET `quota`=`quota`+" . filesize($target) . " WHERE `id`='" . $_SESSION["id"] . "'");
        if ($overwriting) {
            $db->query("UPDATE `accounts` SET `quota`=`quota`-" . $oldsize . " WHERE `id`='" . $_SESSION["id"] . "'");
        }
        return "";
    }
    // JPEGs. (technically signature analysis could be tighter, as in the above GIF example)
    elseif (str_starts_with($bytes, hex2bin("FFD8FF"))) {
        $image = imagecreatefromjpeg($_FILES[$file]["tmp_name"]);
        
        // This is safe because we never use any user-supplied value in $name.
        $target = $upload_dir . $name . ".webp";
        
        if (is_file($target)) {
            $overwriting = true;
            $oldsize = filesize($target);
        }
        
        if ($image === false) {
            return "Upload failed, invalid JPEG image.";
        }
        
        // TODO: enforce rate limits.
        
        $success = imagewebp($image, $target);
        
        // We do this here in case the later checks erase the new file.
        if ($success and $overwriting) {
            $db->query("UPDATE `accounts` SET `quota`=`quota`-" . $oldsize . " WHERE `id`='" . $_SESSION["id"] . "'");
        }
    
        if (!$success) {
            return "Failed to write image to file.";
        }
        // We do these checks in case the filesize grows after the image has been processed.
        elseif ((filesize($target) + $uq) > $config["perUserDiskQuota"]) {
            unlink($target);
            return "Upload failed, your disk quota would be exceeded.";
        }
        elseif ((filesize($target) + $gq) > $config["totalDiskQuota"]) {
            unlink($target);
            return "Upload failed, the total disk quota would be exceeded.";
        }
        $db->query("UPDATE `accounts` SET `quota`=`quota`+" . filesize($target) . " WHERE `id`='" . $_SESSION["id"] . "'");
        return "";
    }
    // PNGs.
    elseif (str_starts_with($bytes, hex2bin("89504E470D0A1A0A"))) {
        $image = imagecreatefrompng($_FILES[$file]["tmp_name"]);
        
        // This is safe because we never use any user-supplied value in $name.
        $target = $upload_dir . $name . ".webp";
        
        if (is_file($target)) {
            $overwriting = true;
            $oldsize = filesize($target);
        }
        
        if ($image === false) {
            return "Upload failed, invalid PNG image.";
        }
        
        // TODO: enforce rate limits.
        
        $success = imagewebp($image, $target);
        
        // We do this here in case the later checks erase the new file.
        if ($success and $overwriting) {
            $db->query("UPDATE `accounts` SET `quota`=`quota`-" . $oldsize . " WHERE `id`='" . $_SESSION["id"] . "'");
        }
    
        if (!$success) {
            return "Failed to write image to file.";
        }
        // We do these checks in case the filesize grows after the image has been processed.
        elseif ((filesize($target) + $uq) > $config["perUserDiskQuota"]) {
            unlink($target);
            return "Upload failed, your disk quota would be exceeded.";
        }
        elseif ((filesize($target) + $gq) > $config["totalDiskQuota"]) {
            unlink($target);
            return "Upload failed, the total disk quota would be exceeded.";
        }
        $db->query("UPDATE `accounts` SET `quota`=`quota`+" . filesize($target) . " WHERE `id`='" . $_SESSION["id"] . "'");
        return "";
    }
    // WEBPs.
    elseif (str_starts_with($bytes, hex2bin("52494646")) and str_ends_with($bytes, hex2bin("57454250"))) {
        $image = imagecreatefromwebp($_FILES[$file]["tmp_name"]);
        
        // This is safe because we never use any user-supplied value in $name.
        $target = $upload_dir . $name . ".webp";
        
        if (is_file($target)) {
            $overwriting = true;
            $oldsize = filesize($target);
        }
        
        if ($image === false) {
            return "Upload failed, invalid WEBP image.";
        }
        
        // TODO: enforce rate limits.
        
        $success = imagewebp($image, $target);
        
        // We do this here in case the later checks erase the new file.
        if ($success and $overwriting) {
            $db->query("UPDATE `accounts` SET `quota`=`quota`-" . $oldsize . " WHERE `id`='" . $_SESSION["id"] . "'");
        }
    
        if (!$success) {
            return "Failed to write image to file.";
        }
        // We do these checks in case the filesize grows after the image has been processed.
        elseif ((filesize($target) + $uq) > $config["perUserDiskQuota"]) {
            unlink($target);
            return "Upload failed, your disk quota would be exceeded.";
        }
        elseif ((filesize($target) + $gq) > $config["totalDiskQuota"]) {
            unlink($target);
            return "Upload failed, the total disk quota would be exceeded.";
        }
        $db->query("UPDATE `accounts` SET `quota`=`quota`+" . filesize($target) . " WHERE `id`='" . $_SESSION["id"] . "'");
        return "";
    }
    else {
        return "Upload failed, unsupported or unrecognized image type.";
    }
}

// TODO: make a function for generating thumbnails of existing images.

function generateCaptchaText(int $length) {
    $characters = "abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
    $text = "";
    for ($i = 0; $i < $length; $i++) {
        $text .= $characters[random_int(0, strlen($characters)-1)];
    }
    return $text;
}

function generateCaptcha() {
    global $config;

    $length = $config["captchaLength"];

    $_SESSION["captcha"] = generateCaptchaText($length);
    
    // Pick a random font.
    $font = random_int(2, 5);
    
    $fontwidth = imagefontwidth($font);
    $fontheight = imagefontheight($font);

    $width = $length*$fontwidth+($length*5)+5;
    $height = $fontheight+20;

    $img = imagecreatetruecolor($width, $height);
    
    // Put a background of random pixels.
    for ($w = 0; $w < $width; $w++) {
        for ($h = 0; $h < $height; $h++) {
            $rand = imagecolorallocate($img, rand(0, 80), rand(0, 80), rand(0, 80));
            imagesetpixel($img, $w, $h, $rand);
        }
    }
    
    $white = imagecolorallocate($img, 255, 255, 255);
    
    // Draw some lines.
    $rand = imagecolorallocate($img, random_int(120, 200), random_int(120, 200), random_int(120, 200));
    imageline($img, 1+random_int(2, 10), 1+random_int(2, 10), $width-random_int(2, 10), $height-random_int(2, 10), $rand);
    $rand = imagecolorallocate($img, random_int(120, 200), random_int(120, 200), random_int(120, 200));
    imageline($img, $width+random_int(2, 10), 1+random_int(2, 10), 1-random_int(2, 10), $height-random_int(2, 10), $rand);
    
    // Draw the letters.
    for ($i = 0; $i < $length; $i++) {
        $rand = imagecolorallocate($img, random_int(180, 255), random_int(180, 255), random_int(180, 255));
        imagestring($img, $font, $i*($fontwidth+5)+5+random_int(-3, 3), $fontheight/2+random_int(-6, 6), $_SESSION["captcha"][$i], $rand);
    }
    
    // Make the image larger so it's easier to read.
    $img = imagescale($img, $width*3, $height*3);
    
    imagefilter($img, IMG_FILTER_SCATTER, 0, 2);
    imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
    
    // Turn the image into a webp.
    ob_start();
    imagewebp($img);
    $webp = ob_get_clean();
    
    return base64_encode($webp);
}

function markdownButtons() {
    // We need the "javascript:;" part because we need a valid href attribute so that these buttons are focusable (I.E. the user can use tab to select them).
    return "<div></div><div class='markdownbuttons'><script src='" . makeURL("javascript/markdownbuttons.js", true) . "'></script>
    <label></label>
    <a class='button buttonSmall' title='bold' onclick='format(\"bold\")' href='javascript:;'><b>B</b></a>
    <a class='button buttonSmall' title='italic' onclick='format(\"italic\")' href='javascript:;'><i>i</i></a>
    <a class='button buttonSmall' title='code' onclick='format(\"code\")' href='javascript:;'>c</a>
    <a class='button buttonSmall' title='codeblock' onclick='format(\"codeblock\")' href='javascript:;'>&lt;&gt;</a>
    <a class='button buttonSmall' title='link' onclick='format(\"link\")' href='javascript:;'>link</a>
    <a class='button buttonSmall' title='image' onclick='format(\"image\")' href='javascript:;'>img</a>
    <a class='button buttonSmall' title='heading' onclick='format(\"header\")' href='javascript:;'><b>H</b></a>
    <a class='button buttonSmall' title='horizontal rule' onclick='format(\"hr\")' href='javascript:;'>hr</a>
    <a class='button buttonSmall' title='blockquote' onclick='format(\"blockquote\")' href='javascript:;'>bq</a>
    <a class='button buttonSmall' title='list' onclick='format(\"list\")' href='javascript:;'>li</a>
    </div>";
}

function parseTags($tagstring) {
    $tags = explode(",", $tagstring);
    
    // Remove whitespace from start and end of each tag, and convert tags to lowercase.
    for ($t = 0; $t < count($tags); $t++) {
        $tags[$t] = strtolower(trim($tags[$t]));
    }
    
    // Remove empty tags.
    $index = array_search("", $tags);
    while ($index !== false) {
        array_splice($tags, $index, 1);
        $index = array_search("", $tags);
    }
    
    // Remove any duplicate tags.
    $tags = array_unique($tags);
    
    return $tags;
}

// Make sure a number is within a specified range.
function clamp($number, $min, $max) {
    return max($min, min($max, $number));
}

// TODO: make sure this function is being used in a secure manner.
function sendEmail($address, $subject, $message) {
    // If there are already CRLFs change them so we don't end up duplicating the CR's.
    $message = str_replace("\r\n", "\n", $message);
    // All lines must be separated by CRLF.
    $message = str_replace("\n", "\r\n", $message);

    return mail($address, $subject, $message);
}

// On the login logs page we want to redact IPs for privacy reasons.
function redactIP($ip) {
    // If it's an IPv4 address, redact the last octet.
    if (preg_match("/[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}/", $ip)) {
        $ip = explode(".", $ip);
        $ip[3] = "0";
        return implode(".", $ip);
    }
    // Otherwise it's IPv6 or perhaps invalid.
    else {
        $raw = inet_pton($ip);
        if ($raw !== false) {
            // Redact the host part of the IPv6 address.
            // See: https://en.wikipedia.org/wiki/IPv6#Addressing
            $raw = $raw & inet_pton("ffff:ffff:ffff:ffff:0000:0000:0000:0000");
            $ip = inet_ntop($raw);
            return $ip;
        }
    }
    // By default, it must be an invalid IP address.
    return "invalid";
}

// Return a human-readable version of a user agent string.
// This is a best-effort function, it relies on some regular expressions and is imperfect. It aims to cover most but not all cases.
function parseUserAgent($ua) {
    $result = array("os" => "Unknown", "browser" => "Unknown");
    
    // ----- OS -----
    // -- Desktop OSes --
    // - Windows -
    if (preg_match("/^Mozilla\/5\.0 \(Windows NT 10\.0(\)|;)/m", $ua)) {
        $result["os"] = "Windows 10/11";
    }
    elseif (preg_match("/^Mozilla\/5\.0 \(Windows NT 6\.3(\)|;)/m", $ua)) {
        $result["os"] = "Windows 8.1";
    }
    elseif (preg_match("/^Mozilla\/5\.0 \(Windows NT 6\.2(\)|;)/m", $ua)) {
        $result["os"] = "Windows 8";
    }
    elseif (preg_match("/^Mozilla\/5\.0 \(Windows NT 6\.1(\)|;)/m", $ua)) {
        $result["os"] = "Windows 7";
    }
    elseif (preg_match("/^Mozilla\/5\.0 \(Windows NT 6\.0(\)|;)/m", $ua)) {
        $result["os"] = "Windows Vista";
    }
    elseif (preg_match("/^Mozilla\/5\.0 \(Windows NT 5\.(1|2)(\)|;)/m", $ua)) {
        $result["os"] = "Windows XP";
    }
    // Generic Windows.
    elseif (preg_match("/^Mozilla\/5\.0 \(Windows(\)|;)/m", $ua)) {
        $result["os"] = "Windows";
    }
    // - Linux -
    elseif (preg_match("/^Mozilla\/5\.0 \(X11; Linux x86_64(\)|;)/m", $ua)) {
        $result["os"] = "Linux x86_64";
    }
    elseif (preg_match("/^Mozilla\/5\.0 \(X11; Linux i686(\)|;)/m", $ua)) {
        $result["os"] = "Linux i686";
    }
    elseif (preg_match("/^Mozilla\/5\.0 \(X11; Ubuntu; Linux x86_64(\)|;)/m", $ua)) {
        $result["os"] = "Ubuntu x86_64";
    }
    elseif (preg_match("/^Mozilla\/5\.0 \(X11; Ubuntu; Linux i686(\)|;)/m", $ua)) {
        $result["os"] = "Ubuntu i686";
    }
    // - Macintosh -
    elseif (preg_match("/^Mozilla\/5\.0 \(Macintosh;/m", $ua)) {
        $result["os"] = "Macintosh";
    }
    // -- Mobile OSes --
    // - Android -
    elseif (preg_match("/^Mozilla\/5\.0 \(Android/m", $ua)) {
        $result["os"] = "Android";
    }
    elseif (preg_match("/^Mozilla\/5\.0 \(Linux; Android/m", $ua)) {
        $result["os"] = "Android";
    }
    elseif (preg_match("/^Mozilla\/5\.0 \(Linux; U; Android/m", $ua)) {
        $result["os"] = "Android";
    }
    // - iPad -
    elseif (preg_match("/^Mozilla\/5\.0 \(iPad;/m", $ua)) {
        $result["os"] = "iPad";
    }
    // - iPhone -
    elseif (preg_match("/^Mozilla\/5\.0 \(iPhone;/m", $ua)) {
        $result["os"] = "iPhone";
    }
    
    // ----- Browser -----
    // - Firefox -
    if (preg_match("/Gecko\/[0-9.]+ Firefox\/[0-9.]+$/m", $ua)) {
        $result["browser"] = "Firefox";
    }
    // - Chrome Desktop -
    elseif (preg_match("/Chrome\/[0-9.]+ Safari\/[0-9.]+$/m", $ua)) {
        $result["browser"] = "Google Chrome";
    }
    // - Samsung Browser -
    elseif (preg_match("/SamsungBrowser\/[0-9.]+ Chrome\/[0-9.]+( Mobile | )Safari\/[0-9.]+$/m", $ua)) {
        $result["browser"] = "Samsung Browser";
    }
    // - Chrome Mobile -
    elseif (preg_match("/Chrome\/[0-9.]+ Mobile Safari\/[0-9.]+$/m", $ua)) {
        $result["browser"] = "Google Chrome (Mobile)";
    }
    // - Microsoft Edge -
    elseif (preg_match("/Chrome\/[0-9.]+ Safari\/[0-9.]+ Edge\/[0-9.]+$/m", $ua)) {
        $result["browser"] = "Microsoft Edge";
    }
    // - Safari -
    elseif (preg_match("/Version\/[0-9.]+ Safari\/[0-9.]+$/m", $ua)) {
        $result["browser"] = "Safari";
    }
    // - Opera -
    elseif (preg_match("/OPR\/[0-9.]+$/m", $ua)) {
        $result["browser"] = "Opera";
    }
    
    return $result;
}

// --- PHP 7.* Compatibility ---
if (!function_exists("str_starts_with")) {
    function str_starts_with(string $haystack, string $needle) {
        if (strlen($needle) > strlen($haystack)) {
            return false;
        }
        
        if (substr($haystack, 0, strlen($needle)) === $needle) {
            return true;
        }
        
        // Default to false.
        return false;
    }
}

if (!function_exists("str_ends_with")) {
    function str_ends_with(string $haystack, string $needle) {
        if (strlen($needle) > strlen($haystack)) {
            return false;
        }
        
        if (substr($haystack, strlen($needle)*-1) === $needle) {
            return true;
        }
        
        // Default to false.
        return false;
    }
}

?>
