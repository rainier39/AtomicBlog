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
$post = preparedQuery("SELECT `posts`.*,`accounts`.`name`,`accounts`.`namevisible` FROM `posts` LEFT JOIN `accounts` ON `accounts`.`id`=`posts`.`account` WHERE `posts`.`id`=? AND (published='1' OR (published='0' AND account=?))", array($url[1], $id));

$p = $post->fetch_assoc();

// Print a message if the post doesn't exist.
if ($post->num_rows < 1) {
    http_response_code(404);
    $messages[] = error("Post not found.");
    render_page("", array(), "Post not found");
    exit();
}

// Get the tags.
$tagsquery = preparedQuery("SELECT `tag` FROM `tags` WHERE `post`=?", array($p["id"]));
$tags = array();
while ($t = $tagsquery->fetch_assoc()) {
    $tags[] = $t["tag"];
}

// Handle star toggling.
if (isset($_POST["toggleStar"]) and validateCSRFToken()) {
    // Make sure the user is allowed to star/unstar the post.
    if ((($id == $p["account"]) and checkPerm(PERM_STAR_POST))
    or (checkPerm(PERM_MOD_STAR_POST) and checkOutrank($id, $p["account"]))) {
        preparedQuery("UPDATE `posts` SET `starred`=`starred`^1 WHERE id=?", array($p["id"]));
        // Let's not update the whole post just for flipping 1 bit.
        $p["starred"] = !$p["starred"];
    }
    else {
        $messages[] = error("You don't have permission to do this.");
    }
}
// Handle published toggling.
elseif (isset($_POST["togglePublished"]) and validateCSRFToken()) {
    // Make sure the user is allowed to publish/unpublish the post.
    if (($id == $p["account"]) and checkPerm(PERM_NEW_POST)) { 
        preparedQuery("UPDATE `posts` SET `published`=`published`^1 WHERE id=?", array($p["id"]));
        // Let's not update the whole post just for flipping 1 bit.
        $p["published"] = !$p["published"];
    }
    else {
        $messages[] = error("You don't have permission to do this.");
    }
}
// Handle deletions.
elseif (isset($_POST["delete"]) and validateCSRFToken()) {
    // Make sure the user is allowed to delete the post.
    if ((($id == $p["account"]) and checkPerm(PERM_DELETE_POST))
    or (checkPerm(PERM_MOD_DELETE_POST) and checkOutrank($id, $p["account"]))) {
        // Delete the post.
        preparedQuery("DELETE FROM `posts` WHERE `id`=?", array($p["id"]));
        // Delete all of the post's tags.
        preparedQuery("DELETE FROM `tags` WHERE `post`=?", array($p["id"]));
        // Delete all of the post's views.
        preparedQuery("DELETE FROM `views` WHERE `post`=?", array($p["id"]));
        // Delete all of the post's comments.
        preparedQuery("DELETE FROM `comments` WHERE `post`=?", array($p["id"]));
        // Delete all icons and attachments.
        $uploads = scandir("images/");
        foreach ($uploads as $u) {
            if (str_starts_with($u, $p["id"] . ".") or str_starts_with($u, $p["id"] . "_")) {
                unlink("images/" . $u);
            }
        }
                
        $_SESSION["messages"][] = success("Successfully deleted the post.");
        redirect("");
    }
    else {
        $messages[] = error("You don't have permission to do this.");
    }
}
// Handle new comments.
elseif (isset($_POST["newcomment"]) and validateCSRFToken()) {
    // Make sure the user is allowed to comment.
    if (checkPerm(PERM_COMMENT)) {
        $rateLimited = false;
        
        // Lock to avoid race conditions.
        $db->query("LOCK TABLES `comments` WRITE, `accounts` WRITE");
        // Get comments from this IP.
        $ipCheck = preparedQuery("SELECT 1 FROM `comments` WHERE `ip`=? AND `timestamp`>?", array($_SERVER["REMOTE_ADDR"], (time()-$config["commentDelay"])));
            
        if ($ipCheck->num_rows > 0) {
            $rateLimited = true;
        }
            
        // If it's a user with an account.
        if ($_SESSION["logged_in"]) {
            $emailquery = preparedQuery("SELECT `email` FROM `accounts` WHERE `id`=?", array($_SESSION["id"]));
                
            while ($e = $emailquery->fetch_assoc()) {
                $email = $e["email"];
            }
                
            $commentid = $id;
                
            // Get comments from this account too.
            $accCheck = preparedQuery("SELECT 1 FROM `comments` WHERE `account`=? AND `timestamp`>?", array($id, (time()-$config["commentDelay"])));
            
            if ($accCheck->num_rows > 0) {
                $rateLimited = true;
            }
        }
        // Guests.
        else {
            $commentid = 0;
            $email = $_POST["email"] ?? "";
        }
            
        $content = $_POST["content"] ?? "";
            
        $errors = array();
            
        if ($rateLimited) {
            $errors[] = "You must wait a little bit before making another comment.";
        }

        // Only validate Guest emails.
        if (!$commentid) {
            $emailerrors = validateEmail($email, true);
            if (count($emailerrors)) $errors = array_merge($errors, $emailerrors);
        }
            
        if (strlen($content) < 1) {
            $errors[] = "Comment cannot be blank.";
        }
        elseif (strlen($content) > $config["commentMaxLength"]) {
            $errors[] = "Comment is too long.";
        }
            
        if (count($errors) != 0) {
            foreach ($errors as $e) {
                $messages[] = error($e);
            }
        }
        else {
            // Make the commment.
            preparedQuery("INSERT INTO `comments` (`account`,`post`,`email`,`ip`,`timestamp`,`content`) VALUES (?, ?, ?, ?, ?, ?)", array($commentid, $p["id"], $email, $_SERVER["REMOTE_ADDR"], time(), $content));
            $messages[] = success("Successfully made comment.");
            $_POST["content"] = "";
        }
        $db->query("UNLOCK TABLES");
    }
    else {
        $messages[] = error("You don't have permission to do this.");
    }
}
// Handle deleting a comment.
elseif (isset($_POST["deletecomment"]) and validateCSRFToken()) {
    if (isset($_POST["commentid"])) {
        $commentinfo = preparedQuery("SELECT `id`, `account`, `ip` FROM `comments` WHERE `id`=?", array($_POST["commentid"]));
        
        if ($commentinfo->num_rows < 1) {
            $messages[] = error("You don't have permission to do this.");
        }
        else {
            while ($c = $commentinfo->fetch_assoc()) {
                $c_account = $c["account"];
                $c_id = $c["id"];
                $c_ip = $c["ip"];
            }
            // Permission check.
            if ((checkPerm(PERM_DELETE_COMMENT) and (($c_account === $id)
            // Guest with same IP.
            or (($c_account === "0") and ($c_ip == $_SERVER["REMOTE_ADDR"]))))
            or checkPerm(PERM_MOD_COMMENTS)) {
                // Delete the comment.
                preparedQuery("DELETE FROM `comments` WHERE `id`=?", array($c_id));
                    
                $messages[] = success("Successfully deleted comment.");
            }
        }
    }
    else {
        $messages[] = error("You don't have permission to do this.");
    }
}
// Handle editing a comment.
elseif (isset($_POST["editcomment"]) and validateCSRFToken()) {
    if (isset($_POST["commentid"])) {
        $commentinfo = preparedQuery("SELECT `id`, `account`, `ip`, `content` FROM `comments` WHERE `id`=?", array($_POST["commentid"]));
        
        if ($commentinfo->num_rows < 1) {
            $messages[] = error("You don't have permission to do this.");
        }
        else {
            while ($c = $commentinfo->fetch_assoc()) {
                $c_account = $c["account"];
                $c_id = $c["id"];
                $c_ip = $c["ip"];
                $c_content = $c["content"];
            }
            // Permission check.
            if ((checkPerm(PERM_EDIT_COMMENT) and (($c_account === $id)
            // Guest with same IP.
            or (($c_account === "0") and ($c_ip == $_SERVER["REMOTE_ADDR"]))))
            or checkPerm(PERM_MOD_COMMENTS)) {
                $content = $_POST["newcontent"] ?? "";
                    
                $errors = array();
                    
                $rateLimited = false;
                    
                $rlcheck = preparedQuery("SELECT 1 FROM `comments` WHERE (`account`=? OR `ip`=?) AND `timestamp`>?", array($id, $_SERVER["REMOTE_ADDR"], (time()-$config["editDelay"])));
                
                if ($rlcheck->num_rows > 0) {
                    $rateLimited = true;
                }
            
                if ($rateLimited) {
                    $errors[] = "You must wait a few seconds before making another edit.";
                }
                    
                if (strlen($content) < 1) {
                    $errors[] = "Comment cannot be blank.";
                }
                elseif (strlen($content) > $config["commentMaxLength"]) {
                    $errors[] = "Comment is too long.";
                }
                elseif ($content == $c_content) {
                    $errors[] = "Nothing to change.";
                }
            
                if (count($errors) != 0) {
                    foreach ($errors as $e) {
                        $messages[] = error($e);
                    }
                }
                else {
                    // Edit the comment.
                    preparedQuery("UPDATE `comments` SET `content`=?, `timestamp`=? WHERE `id`=?", array($content, time(), $c_id));
                
                    $_SESSION["messages"][] = success("Successfully edited comment.");
                    redirect("post/" . $p["id"]);
                }
            }
        }
    }
    else {
        $messages[] = error("You don't have permission to do this.");
    }
}
// Handle cancelling editing a comment.
elseif (isset($_POST["canceleditcomment"]) and validateCSRFToken()) {
    redirect("post/" . $p["id"]);
}
// Handle editing.
elseif (($url[2] ?? "") == "edit") {
    $title = "Edit Post";
    $displayPost = false;
    $success = false;
    // Make sure the user is allowed to edit the post.
    if ((($id === $p["account"]) and checkPerm(PERM_NEW_POST))
    or (checkPerm(PERM_MOD_EDIT_POST) and checkOutrank($id, $p["account"]))) {
        if (isset($_POST["edit"]) and validateCSRFToken()) {
            // Remove ampersands from tags because they can be used to inject URL parameters.
            $_POST["tags"] = str_replace("&", "", $_POST["tags"] ?? "");
            // Remove slashes because they can ruin a search.
            $_POST["tags"] = str_replace("/", "", $_POST["tags"] ?? "");

            $errors = validatePost(true);
            
            $tagstring = unparseTags($tags);

            if (($_POST["title"] == $p["title"]) and (unparseTags(parseTags($_POST["tags"])) == $tagstring) and ($_POST["content"] == $p["content"])) {
                $errors[] = "Nothing has been changed.";
            }
        	
            // If there are no errors, edit the post.
            if (!count($errors)) {
                preparedQuery("UPDATE `posts` SET `title`=?, `content`=?, `editedby`=?, `edittime`=? WHERE `id`=?", array($_POST["title"], $_POST["content"], $_SESSION["id"], time(), $p["id"]));
                // Add the tags.
                $newtags = parseTags($_POST["tags"]);
                if (count($newtags)) {
                    preparedQuery("DELETE FROM `tags` WHERE `post`=?", array($p["id"]));
                    // Construct a single INSERT query to add all of the tags.
                    $tagQuery = "INSERT INTO `tags` (`tag`,`post`) VALUES ";
                    $tagParams = array();
                    foreach ($newtags as $tag) {
                        // This is safe because $p["id"] is not a user-supplied value.
                        $tagQuery .= "(?, {$p["id"]}),";
                        $tagParams[] = $tag;
                    }
                    // Remove the trailing comma.
                    $tagQuery = rtrim($tagQuery, ",");
                    preparedQuery($tagQuery, $tagParams);
                }
                $success = true;
                $updatePost = true;
                $_SESSION["messages"][] = success("Successfully edited post.");
            }
            // Otherwise, print the errors.
            else {
                foreach ($errors as $e) {
                    $messages[] = error($e);
       	        }
            }
        }
        // If the user pressed the cancel button, fallthrough to the redirect.
        elseif (isset($_POST["cancel"]) and validateCSRFToken()) {
            $success = true;
        }
        if (!$success) {
            $posteditvars = array("token" => $_SESSION["csrf_token"],
            "title" => $_POST["title"] ?? $p["title"],
            "tags" => $_POST["tags"] ?? unparseTags($tags),
            "buttons" => markdownButtons(),
            "content" => $_POST["content"] ?? $p["content"]);
            
            render_page("postEdit.html", $posteditvars, $title);
        }
    }
    else {
        $messages[] = error("You don't have permission to do this.");
        $displayPost = true;
    }
    if ($success) {
        $displayPost = true;
        redirect("post/" . $p["id"]);
    }
}
// Handle uploading.
elseif (($url[2] ?? "") == "uploads") {
    $title = "Manage Uploads";
    $displayPost = false;
    $success = false;
    // Make sure the user is allowed to upload.
    if ((($id === $p["account"]) and checkPerm(PERM_UPLOAD))
    or (checkPerm(PERM_MOD_UPLOAD) and checkOutrank($id, $p["account"]))) {
        // Token check.
        if (!validateCSRFToken()) {
            unset($_POST);
            unset($_FILES);
        }
        
        // Handle uploading icon.
        if (isset($_FILES["icon"])) {
            $upload = upload("icon", $p["id"]);
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
            $a_id = bin2hex(random_bytes(8));
            while (file_exists("images/" . $p["id"] . "_" . $a_id . ".webp")) {
                $a_id = bin2hex(random_bytes(8));
            }
            $upload = upload("attachment", $p["id"] . "_" . $a_id);
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
            if (!str_starts_with($target, $p["id"] . ".")) {
                $messages[] = error("Nice try.");
            }
            // Make sure that the target icon exists.
            elseif (!is_file("images/" . $target)) {
                $messages[] = error("Specified icon doesn't exist.");
            }
            else {
                $size = filesize("images/" . $target);
                $deleted = unlink("images/" . $target);
                if ($deleted) {
                    // Subtract from the quota, ensuring it never drops below zero.
                    preparedQuery("UPDATE `accounts` SET `quota`=GREATEST(`quota`-?, 0) WHERE `id`=?", array($size, $_SESSION["id"]));
                    // Log the deletion.
                    preparedQuery("INSERT INTO `logs` (`logtype`,`perpid`,`content`,`ip`,`useragent`,`timestamp`) VALUES ('image_delete', ?, ?, ?, ?, ?)", array($_SESSION["id"], $target, $_SERVER["REMOTE_ADDR"], substr($_SERVER["HTTP_USER_AGENT"], 0, 256), time()));
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
            if (!str_starts_with($target, $p["id"] . "_")) {
                $messages[] = error("Nice try.");
            }
            // Make sure that the target attachment exists.
            elseif (!is_file("images/" . $target)) {
                $messages[] = error("Specified attachment doesn't exist.");
            }
            else {
                $size = filesize("images/" . $target);
                $deleted = unlink("images/" . $target);
                if ($deleted) {
                    // Subtract from the quota, ensuring it never drops below zero.
                    preparedQuery("UPDATE `accounts` SET `quota`=GREATEST(`quota`-?, 0) WHERE `id`=?", array($size, $_SESSION["id"]));
                    // Log the deletion.
                    preparedQuery("INSERT INTO `logs` (`logtype`,`perpid`,`content`,`ip`,`useragent`,`timestamp`) VALUES ('image_delete', ?, ?, ?, ?, ?)", array($_SESSION["id"], $target, $_SERVER["REMOTE_ADDR"], substr($_SERVER["HTTP_USER_AGENT"], 0, 256), time()));
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
            if (str_starts_with($u, $p["id"] . ".")) {
                $icons[] = $u;
            }
        }
        // Get all attachments.
        foreach ($uploads as $u) {
            if (str_starts_with($u, $p["id"] . "_")) {
                $attachments[] = $u;
            }
        }
        // Display forms and images.
        $postuploadsvars = array("back" => makeURL("post/{$p["id"]}"),
        "token" => $_SESSION["csrf_token"],
        "icons" => "",
        "script" => makeURL("javascript/uploads.js", true),
        "attachments" => "");
        
        foreach ($icons as $icon) {
            // Get the upload time to add as a URL parameter when showing the image to avoid an old cached version being displayed by the browser.
            $uploadTime = $db->query("SELECT `timestamp` FROM `logs` WHERE `content`='images/{$icon}' ORDER BY `timestamp` DESC LIMIT 1");
            if ($uploadTime->num_rows > 0) {
                $ut = $uploadTime->fetch_assoc()["timestamp"];
            }
            else {
                $ut = time();
            }
            $postuploadsvars["icons"] .= "<div class='uploadTile'>
              <img src='" . makeURL("images/{$icon}") . "?{$ut}'>
              <hr>
              <form method='post' onsubmit='return confirm(\"Are you sure you want to delete this icon?\");'>
                <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
                <input type='hidden' value='{$icon}' name='dicon'>
                <input type='submit' value='Delete' name='deleteIcon' class='button'>
              </form>
            </div>";
        }
        foreach ($attachments as $attachment) {
            // Get the upload time to add as a URL parameter when showing the image to avoid an old cached version being displayed by the browser.
            $uploadTime = $db->query("SELECT `timestamp` FROM `logs` WHERE `content`='images/{$attachment}' ORDER BY `timestamp` DESC LIMIT 1");
            if ($uploadTime->num_rows > 0) {
                $ut = $uploadTime->fetch_assoc()["timestamp"];
            }
            else {
                $ut = time();
            }
            $postuploadsvars["attachments"] .= "<div class='uploadTile'>
              <img src='" . makeURL("images/{$attachment}") . "?{$ut}'>
              URL: <a onclick='copy(\"" . (($ishttps == "on") ? "https://" : "http://") . $_SERVER["SERVER_NAME"] . makeURL("images/{$attachment}") . "\");'>copy me</a>
              <hr>
              <form method='post' onsubmit='return confirm(\"Are you sure you want to delete this attachment?\");'>
                <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
                <input type='hidden' value='{$attachment}' name='dattachment'>
                <input type='submit' value='Delete' name='deleteAttachment' class='button'>
              </form>
            </div>";
        }
        
        render_page("postUploads.html", $postuploadsvars, $title);
    }
    else {
        $messages[] = error("You don't have permission to do this.");
        $displayPost = true;
    }
}
// Otherwise, display the post.
if ($displayPost) {
    // Get the requested post again if the user edited it or starred it.
    if ($updatePost) {
        $post = preparedQuery("SELECT `posts`.*,`accounts`.`name`,`accounts`.`namevisible` FROM `posts` LEFT JOIN `accounts` ON `accounts`.`id`=`posts`.`account` WHERE `posts`.`id`=? AND (published='1' OR (published='0' AND account=?))", array($p["id"], $id));
        $p = $post->fetch_assoc();
        // Get the tags.
        $tagsquery = preparedQuery("SELECT `tag` FROM `tags` WHERE `post`=?", array($p["id"]));
        $tags = array();
        while ($t = $tagsquery->fetch_assoc()) {
            $tags[] = $t["tag"];
        }
    }
    
    $postvars = array("postbuttons" => "",
    "title" => $p["title"],
    "author" => "Nobody",
    "ptime" => date("g:i:sa", $p["starttime"]),
    "pdate" => date("F jS, Y", $p["starttime"]),
    "edited" => "",
    "icon" => "",
    "tags" => "",
    "content" => $p["content"],
    "comments" => "");
    
    $title = $p["title"];
    
    if ((($id == $p["account"]) and checkPerm(PERM_NEW_POST))
    or (checkPerm(PERM_MOD_EDIT_POST) and checkOutrank($id, $p["account"]))) {
        $postvars["postbuttons"] .= "
            <a href='" . makeURL("post/{$p["id"]}/edit") . "' class='button postButton'>Edit</a>";
    }
    if ((($id == $p["account"]) and checkPerm(PERM_DELETE_POST))
    or (checkPerm(PERM_MOD_DELETE_POST) and checkOutrank($id, $p["account"]))) {
        $postvars["postbuttons"] .= 
            "<form method='post' onsubmit='return confirm(\"Are you sure you want to delete this post?\");'><input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'><input type='submit' class='button postButton' name='delete' value='Delete'></form>";
    }
    if ((($id == $p["account"]) and checkPerm(PERM_STAR_POST))
    or (checkPerm(PERM_MOD_STAR_POST) and checkOutrank($id, $p["account"]))) {
        $postvars["postbuttons"] .=
            "<form method='post'><input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'><input type='submit' class='button postButton' name='toggleStar' value='" . (($p["starred"] == "1") ? "Unstar" : "Star") . "'></form>";
    }
    if (($id == $p["account"]) and checkPerm(PERM_NEW_POST)) {
        $postvars["postbuttons"] .=
            "<form method='post'><input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'><input type='submit' class='button postButton' name='togglePublished' value='" . (($p["published"] == "1") ? "Unpublish" : "Publish") . "'></form>";
    }
    if ((($id == $p["account"]) and checkPerm(PERM_UPLOAD))
    or (checkPerm(PERM_MOD_UPLOAD) and checkOutrank($id, $p["account"]))) {
        $postvars["postbuttons"] .= "
            <a href='" . makeURL("post/{$p["id"]}/uploads") . "' class='button postButton'>Manage Uploads</a>";
    }
    // Show the post author's name if applicable.
    if ($p["namevisible"]) {
        $postvars["author"] = "<a class='profileLink' href='" . makeURL("profile/" . $p["account"]) . "'>" . htmlspecialchars($p["name"], ENT_NOQUOTES) . "</a>";
    }
    elseif (!$p["name"]) {
        $postvars["author"] = "Nobody";
    }
    else {
        $postvars["author"] = "<a class='profileLink' href='" . makeURL("profile/" . $p["account"]) . "'>Anonymous</a>";
    }
    if (!empty($p["edittime"])) {
        $postvars["edited"] .= " | <small>Modified: <abbr class='date' title='" . date("g:i:sa", $p["edittime"]) . "'>" . date("F jS, Y", $p["edittime"]) . "</abbr></small>";
    }
    // Display the post's icon if it exists.
    $uploads = scandir("images/");
    foreach ($uploads as $u) {
        if (str_starts_with($u, $p["id"] . ".")) {
            // Get the upload time to add as a URL parameter when showing the image to avoid an old cached version being displayed by the browser.
            $uploadTime = $db->query("SELECT `timestamp` FROM `logs` WHERE `content`='images/{$u}' ORDER BY `timestamp` DESC LIMIT 1");
            if ($uploadTime->num_rows > 0) {
                $ut = $uploadTime->fetch_assoc()["timestamp"];
            }
            else {
                $ut = time();
            }
            $postvars["icon"] = "<p><img src='" . makeURL("images/{$u}") . "?{$ut}' class='pIcon'></p>";
            // Just use the first icon we find.
            break;
        }
    }
    foreach ($tags as $tag) {
        $postvars["tags"] .= "<a href='" . makeURL("posts/&tag=" . urlencode(htmlspecialchars($tag))) . "' class='tag'>" . htmlspecialchars($tag) . "</a>";
    }
    
    if ($config["enableComments"]) {
        // First, display the comments form if we have permission to comment.
        if (checkPerm(PERM_COMMENT)) {
            $commentformvars = array("token" => $_SESSION["csrf_token"],
            "email" => $_POST["email"] ?? "",
            "emailRequired" => ($_SESSION["logged_in"] ?? false) ? "disabled" : "",
            "max" => $config["commentMaxLength"],
            "content" => $_POST["content"] ?? "");
            $postvars["comments"] .= render_template("postCommentForm.html", $commentformvars, false);
        }
        else {
            $postvars["comments"] .= error("You don't have permission to comment.");
        }
        // Next, display the existing comments.
        $comments = preparedQuery("SELECT `comments`.*,`accounts`.`namevisible`,`accounts`.`name`,`accounts`.`color` FROM `comments` LEFT JOIN `accounts` ON `comments`.`account`=`accounts`.`id` WHERE `post`=? ORDER BY `comments`.`id` DESC", array($p["id"]));
        
        if ($comments->num_rows < 1) {
            $postvars["comments"] .= "<br>" . info("No comments to display yet.");
        }
        
        while ($c = $comments->fetch_assoc()) {
            $postvars["comments"] .= "<div class='comment' id='comment_{$c["id"]}'>";
            
            // Get author name.
            if ($c["account"] == 0) {
                if (checkPerm(PERM_MANAGE_USERS)) {
                    $authorname = "Guest (" . htmlspecialchars($c["email"]) . ", " . htmlspecialchars($c["ip"]) . ")";
                }
                else {
                    $authorname = "Guest";
                }
                $authorcolor = "";
            }
            else {
                if ($c["namevisible"]) {
                    $authorname = "<a class='profileLink' href='" . makeURL("profile/" . $c["account"]) . "'>" . htmlspecialchars($c["name"], ENT_NOQUOTES) . "</a>";
                }
                else {
                    $authorname = "<a class='profileLink' href='" . makeURL("profile/" . $c["account"]) . "'>Anonymous</a>";
                }
                // This is safe because the value of `color` is constrained.
                $authorcolor = " style='background: #" . $c["color"] . ";'";
            }
            
            $url2 = $url[2] ?? "";
            $url3 = explode("#", $url[3] ?? "");
            $editing = false;
            
            if (($url2 == "editcomment") and ($url3[0] == $c["id"])) {
                $editing = true;
            }
            
            $postvars["comments"] .= "<div class='commentHeader' " . $authorcolor . "><div class='commentHeaderItems'><div>By: " . $authorname . "</div>";
            
            $postvars["comments"] .= "<small><abbr class='date' title='" . date("g:i:sa", $c["timestamp"]) . "'>" . date("F jS, Y", $c["timestamp"]) . "</abbr></small>";
            
            if (((checkPerm(PERM_EDIT_COMMENT) and (($c["account"] === $id)
            // Guest with same IP.
            or (($c["account"] === "0") and ($c["ip"] == $_SERVER["REMOTE_ADDR"]))))
            or checkPerm(PERM_MOD_COMMENTS))
            and (!$editing)) {
                $postvars["comments"] .= "<a href='" . makeURL("post/{$p["id"]}/editcomment/{$c["id"]}#comment_{$c["id"]}") . "' class='button'>Edit</a>";
            }
            
            if ((checkPerm(PERM_DELETE_COMMENT) and (($c["account"] === $id)
            // Guest with same IP.
            or (($c["account"] === "0") and ($c["ip"] == $_SERVER["REMOTE_ADDR"]))))
            or checkPerm(PERM_MOD_COMMENTS)) {
                $postvars["comments"] .= "<form method='post' onsubmit='return confirm(\"Are you sure you want to delete this comment?\");'>
                <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
                <input type='hidden' name='commentid' value='" . $c["id"] . "'>
                <input class='button' type='submit' name='deletecomment' value='Delete'>
                </form>";
            }
            
            if (!$editing) {
                $postvars["comments"] .= "</div></div>
                <div class='commentContent'>
                " . htmlspecialchars($c["content"]) . "
                </div>
                </div>";
            }
            else {
                $postvars["comments"] .= "</div></div>
                <div class='commentContent'>
                <form method='post' class='form'>
                 <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
                 <input type='hidden' name='commentid' value='" . $c["id"] . "'>
                 <textarea name='newcontent' maxlength='" . $config["commentMaxLength"] . "' required>" . htmlspecialchars($_POST["newcontent"] ?? $c["content"]) . "</textarea>
                 <div></div>
                 <input class='button' type='submit' name='editcomment' value='Edit'>
                 <div></div>
                 <input class='button' type='submit' name='canceleditcomment' value='Cancel Edit' formnovalidate>
                </form>
                </div>
                </div>";
            }
        }
    }
    else {
        $postvars["comments"] .= error("Comments are disabled.");
    }

    // Lock to avoid race conditions.
    $db->query("LOCK TABLE `views` WRITE");
    // Get views from this IP on this post, if any.
    $views = preparedQuery("SELECT 1 FROM `views` WHERE `ip`=? AND `post`=?", array($_SERVER["REMOTE_ADDR"], $p["id"]));

    // If there are none, count this as a view.
    if ($views->num_rows < 1) {
        preparedQuery("INSERT INTO `views` (`ip`, `timestamp`, `post`) VALUES (?, ?, ?)", array($_SERVER["REMOTE_ADDR"], time(), $p["id"]));
    }
    $db->query("UNLOCK TABLES");
    
    render_page("post.html", $postvars, $title);
}

?>
