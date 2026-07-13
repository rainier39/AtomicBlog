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

// post.php
// Displays an individual post.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$displayPost = true;
$updatePost = false;
$title = "";

if (!checkPerm(PERM_VIEW_POST)) {
    $messages[] = error("You don't have permission to view this post.");
    render_page("", array());
    exit();
}

$id = $_SESSION["id"] ?? 0;

// Get the requested post.
$post = $db->query("SELECT * FROM `posts` WHERE id='" . $db->real_escape_string($url[1]) . "' AND (published='1' OR (published='0' AND account='" . $id . "'))");

while ($p = $post->fetch_assoc()) {
    $p_id = $p["id"];
    $p_title = $p["title"];
    $p_tags = $p["tags"];
    $p_content = $p["content"];
    $p_account = $p["account"];
    $p_starttime = $p["starttime"];
    $p_editedby = $p["editedby"]; // unused, may remove later
    $p_edittime = $p["edittime"];
    $p_published = $p["published"];
    $p_starred = $p["starred"];
}

// Print a message if the post doesn't exist.
if ($post->num_rows < 1) {
    $messages[] = error("The requested post doesn't exist.");
    $displayPost = false;
}
// Handle star toggling.
elseif (isset($_POST["toggleStar"])) {
    // Make sure the user is allowed to star/unstar the post.
    if (($id == $p_account) and checkPerm(PERM_STAR_POST)) {
        // If the CSRF token is sent and valid.
        if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
            // Generate a new token.
            generateCSRFToken();
            
            // Star.
            if ($_POST["toggleStar"] == "Star") {
                $db->query("UPDATE `posts` SET `starred`='1' WHERE id='" . $db->real_escape_string($p_id) . "'");
            }
            // Unstar.
            else {
                $db->query("UPDATE `posts` SET `starred`='0' WHERE id='" . $db->real_escape_string($p_id) . "'");
            }
            $updatePost = true;
        }
    }
    else {
        $messages[] = error("You don't have permission to do this.");
    }
}
// Handle published toggling.
elseif (isset($_POST["togglePublished"])) {
    // Make sure the user is allowed to publish/unpublish the post.
    if (isset($_SESSION["id"]) and ($_SESSION["id"] == $p_account) and checkPerm(PERM_PUBLISH_POST)) {
        // If the CSRF token is sent and valid.
        if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
            // Generate a new token.
            generateCSRFToken();
                
            // Star.
            if ($_POST["togglePublished"] == "Publish") {
                $db->query("UPDATE `posts` SET `published`='1' WHERE id='" . $db->real_escape_string($p_id) . "'");
            }
            // Unstar.
            else {
                $db->query("UPDATE `posts` SET `published`='0' WHERE id='" . $db->real_escape_string($p_id) . "'");
            }
            $updatePost = true;
        }
    }
    else {
        $messages[] = error("You don't have permission to do this.");
    }
}
// Handle deletions.
elseif (isset($_POST["delete"])) {
        // Make sure the user is allowed to delete the post.
        if (isset($_SESSION["id"]) and ($_SESSION["id"] == $p_account) and checkPerm(PERM_DELETE_POST)) {
            // If the CSRF token is sent and valid.
            if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
                // Generate a new token.
                generateCSRFToken();
                
                // Delete the post.
                $db->query("DELETE FROM `posts` WHERE `id`='" . $db->real_escape_string($p_id) . "'");
                // Delete all of the post's views.
                $db->query("DELETE FROM `views` WHERE `post`='" . $db->real_escape_string($p_id) . "'");
                // Delete all of the post's comments.
                $db->query("DELETE FROM `comments` WHERE `post`='" . $db->real_escape_string($p_id) . "'");
                // Delete all icons and attachments.
                $uploads = scandir("images/");
                foreach ($uploads as $u) {
                    if (str_starts_with($u, $p_id . ".") or str_starts_with($u, $p_id . "_")) {
                        unlink("images/" . $u);
                    }
                }
                
                $messages[] = success("Successfully deleted the post.");
                $displayPost = false;
                redirect("", 2);
            }
        }
        else {
            $messages[] = error("You don't have permission to do this.");
        }
}
// Handle editing.
elseif (isset($url[2]) && ($url[2] == "edit")) {
    $title = "Edit Post";
    $displayPost = false;
    $success = false;
    // Make sure the user is allowed to edit the post.
    if (isset($_SESSION["id"]) and ($_SESSION["id"] === $p_account) and checkPerm(PERM_EDIT_POST)) {
        if (isset($_POST["edit"])) {
            // If the CSRF token is sent and valid.
            if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
                // Generate a new token.
                generateCSRFToken();

                $errors = validatePost(true);
                
                if (($_POST["title"] == $p_title) and ($_POST["tags"] == $p_tags) and ($_POST["content"] == $p_content)) {
                    $errors[] = "Nothing has been changed.";
                }
        	
    	        // If there are no errors, edit the post.
    	        if (count($errors) === 0) {
    	            $db->query("UPDATE `posts` SET `title`='" . $db->real_escape_string($_POST["title"]) . "', `tags`='" . $db->real_escape_string($_POST["tags"]) . "', `content`='" . $db->real_escape_string($_POST["content"]) . "', `editedby`='" . $db->real_escape_string($_SESSION["id"]) . "', `edittime`='" . time() . "' WHERE `id`='" . $db->real_escape_string($p_id) . "'");
    	            $success = true;
     	            $updatePost = true;
       	        }
       	        // Otherwise, print the errors.
       	        else {
       	            foreach ($errors as $e) {
       	                $messages[] = error($e);
       	            }
       	        }
            }
        }
        // If the user pressed the cancel button, fallthrough to the redirect.
        elseif (isset($_POST["cancel"])) {
            $success = true;
        }
        if (!$success) {
            $posteditvars = array("token" => $_SESSION["csrf_token"],
            "title" => $_POST["title"] ?? $p_title,
            "tags" => $_POST["tags"] ?? $p_tags,
            "buttons" => markdownButtons(),
            "content" => $_POST["content"] ?? $p_content);
            
            render_page("postEdit.html", $posteditvars, $title);
        }
    }
    else {
        $messages[] = error("You don't have permission to do this.");
        $updatePost = true;
    }
    if ($success) {
        $displayPost = true;
        redirect("post/" . $url[1]);
    }
}
// Handle uploading.
elseif (isset($url[2]) && ($url[2] == "uploads")) {
    $title = "Manage Uploads";
    $displayPost = false;
    $success = false;
    // Make sure the user is allowed to upload.
    if (isset($_SESSION["id"]) and ($_SESSION["id"] === $p_account) and checkPerm(PERM_UPLOAD)) {
        // Handle uploading icon.
        if (isset($_FILES["icon"])) {
            $upload = upload("icon", $p_id);
            if ($upload == "") {
                $messages[] = success("Successfully uploaded icon.");
            }
            else {
                $messages[] = error($upload);
            }
        }
        // Handle uploading attachment.
        elseif (isset($_FILES["attachment"])) {
            // We will need to generate a value that isn't already being used.
            $a_id = rand();
            while (file_exists("images/" . $p_id . "_" . $a_id . ".webp")) {
                $a_id = rand();
            }
            $upload = upload("attachment", $p_id . "_" . $a_id);
            if ($upload === "") {
                $messages[] = success("Successfully uploaded attachment.");
            }
            else {
                $messages[] = error($upload);
            }
        }
        // Handle deleting icon.
        elseif (isset($_POST["deleteIcon"]) and isset($_POST["dicon"])) {
            // Filepath sanitization.
            $target = basename($_POST["dicon"]);
            // Make sure that this icon is actually from the same post.
            if (!str_starts_with($target, $p_id . ".")) {
                $messages[] = error("Nice try.");
            }
            // Make sure that the target icon exists.
            elseif (!is_file("images/" . $target)) {
                $messages[] = error("Specified icon doesn't exist.");
            }
            else {
                $deleted = unlink("images/" . $target);
                if ($deleted) {
                    $messages[] = success("Successfully deleted icon.");
                }
                else {
                    $messages[] = error("Failed to delete icon.");
                }
            }
        }
        // Handle deleting attachment.
        elseif (isset($_POST["deleteAttachment"]) and isset($_POST["dattachment"])) {
            // Filepath sanitization.
            $target = basename($_POST["dattachment"]);
            // Make sure that this attachment is actually from the same post.
            if (!str_starts_with($target, $p_id . "_")) {
                $messages[] = error("Nice try.");
            }
            // Make sure that the target attachment exists.
            elseif (!is_file("images/" . $target)) {
                $messages[] = error("Specified attachment doesn't exist.");
            }
            else {
                $deleted = unlink("images/" . $target);
                if ($deleted) {
                    $messages[] = success("Successfully deleted attachment.");
                }
                else {
                    $messages[] = error("Failed to delete attachment.");
                }
            }
        }
        $uploads = scandir("images/");
        $icons = array();
        $attachments = array();
        // Get all icons.
        foreach ($uploads as $u) {
            if (str_starts_with($u, $p_id . ".")) {
                $icons[] = $u;
            }
        }
        // Get all attachments.
        foreach ($uploads as $u) {
            if (str_starts_with($u, $p_id . "_")) {
                $attachments[] = $u;
            }
        }
        // Display forms and images.
        $postuploadsvars = array("back" => makeURL("post/{$p_id}"),
        "icons" => "",
        "script" => makeURL("javascript/uploads.js"),
        "attachments" => "");
        
        foreach ($icons as $icon) {
            $postuploadsvars["icons"] .= "<div class='uploadTile'>
              <img src='" . makeURL("images/{$icon}") . "'>
              <hr>
              <form method='post' onsubmit='return confirm(\"Are you sure you want to delete this icon?\");'>
                <input type='hidden' value='{$icon}' name='dicon'>
                <input type='submit' value='Delete' name='deleteIcon' class='button'>
              </form>
            </div>";
        }
        foreach ($attachments as $attachment) {
            $postuploadsvars["attachments"] .= "<div class='uploadTile'>
              <img src='" . makeURL("images/{$attachment}") . "'>
              URL: <a onclick='copy(\"" . (($ishttps == "on") ? "https://" : "http://") . $_SERVER["SERVER_NAME"] . makeURL("images/{$attachment}") . "\");'>copy me</a>
              <hr>
              <form method='post' onsubmit='return confirm(\"Are you sure you want to delete this attachment?\");'>
                <input type='hidden' value='{$attachment}' name='dattachment'>
                <input type='submit' value='Delete' name='deleteAttachment' class='button'>
              </form>
            </div>";
        }
        
        render_page("postUploads.html", $postuploadsvars, $title);
    }
    else {
        $messages[] = error("You don't have permission to do this.");
    }
}
// Otherwise, display the post.
if ($displayPost) {
    // Get the requested post again if the user edited it or starred it.
    if ($updatePost) {
        $post = $db->query("SELECT * FROM `posts` WHERE `id`='" . $db->real_escape_string($url[1]) . "'");
        while ($p = $post->fetch_assoc()) {
            $p_id = $p["id"];
            $p_title = $p["title"];
            $p_tags = $p["tags"]; //unused
            $p_content = $p["content"];
            $p_account = $p["account"];
            $p_starttime = $p["starttime"];
            $p_editedby = $p["editedby"]; // unused, may remove later
            $p_edittime = $p["edittime"];
            $p_published = $p["published"];
            $p_starred = $p["starred"];
        }
    }
    
    $postvars = array("postbuttons" => "",
    "title" => $p_title,
    "author" => "Nobody",
    "ptime" => date("g:i:sa", $p_starttime),
    "pdate" => date("F jS Y", $p_starttime),
    "edited" => "",
    "icon" => "",
    "tags" => "",
    "content" => $p_content);
    
    $title = $p_title;
    
    if (($id == $p_account) and checkPerm(PERM_EDIT_POST)) {
        $postvars["postbuttons"] .= "
            <a href='" . makeURL("post/{$p_id}/edit") . "' class='button postButton'>Edit</a>";
    }
    if (($id == $p_account) and checkPerm(PERM_DELETE_POST)) {
        $postvars["postbuttons"] .= 
            "<form method='post' onsubmit='return confirm(\"Are you sure you want to delete this post?\");'><input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'><input type='submit' class='button postButton' name='delete' value='Delete'></form>";
    }
    if (($id == $p_account) and checkPerm(PERM_STAR_POST)) {
        $postvars["postbuttons"] .=
            "<form method='post'><input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'><input type='submit' class='button postButton' name='toggleStar' value='" . (($p_starred == "1") ? "Unstar" : "Star") . "'></form>";
    }
    if (($id == $p_account) and checkPerm(PERM_PUBLISH_POST)) {
        $postvars["postbuttons"] .=
            "<form method='post'><input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'><input type='submit' class='button postButton' name='togglePublished' value='" . (($p_published == "1") ? "Unpublish" : "Publish") . "'></form>";
    }
    if (($id == $p_account) and checkPerm(PERM_UPLOAD)) {
        $postvars["postbuttons"] .= "
            <a href='" . makeURL("post/{$p_id}/uploads") . "' class='button postButton'>Manage Uploads</a>";
    }
    // Get the account information of the post author.
    $acc = $db->query("SELECT `name` FROM `accounts` WHERE `id`='" . $db->real_escape_string($p_account) . "'");
    if ($acc->num_rows > 0) {
        while ($a = $acc->fetch_assoc()) {
            $postvars["author"] = $a["name"];
        }
    }
    if (!empty($p_edittime)) {
        $postvars["edited"] .= " | <small>Modified: <span title='" . date("g:i:sa", $p_edittime) . "'>" . date("F jS Y", $p_edittime) . "</span></small>";
    }
    // Display the post's icon if it exists.
    $uploads = scandir("images/");
    foreach ($uploads as $u) {
        if (str_starts_with($u, $p_id . ".")) {
            $postvars["icon"] = "<p><img src='" . makeURL("images/{$u}") . "' class='pIcon'></p>";
            // Just use the first icon we find.
            break;
        }
    }
    $tags = parseTags($p_tags);
    foreach ($tags as $tag) {
        $postvars["tags"] .= "<div class='tag'>" . htmlspecialchars($tag) . "</div>";
    }

    // Get views from this IP on this post, if any.
    $views = $db->query("SELECT 1 FROM `views` WHERE `ip`='" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "' AND `post`='" . $db->real_escape_string($p_id) . "'");

    // If there are none, count this as a view.
    if ($views->num_rows < 1) {
        $db->query("INSERT INTO `views` (`ip`, `timestamp`, `post`) VALUES ('" . $db->real_escape_string($_SERVER["REMOTE_ADDR"]) . "', '" . time() . "', '" . $db->real_escape_string($p_id) . "')");
    }
    
    render_page("post.html", $postvars, $title);
}

?>

