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

// newpost.php
// Handles creating a new post.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$success = false;

if (!checkPerm(PERM_NEW_POST)) {
    $content .= error("You don't have permission to do this.");
    render($content, $title);
    exit();
}
$title = "New Post";
// Handle requests.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // If the CSRF token is sent and valid.
    if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
        // Generate a new token.
        generateCSRFToken();

        $errors = validatePost();
        	
        // If there are no errors, make the post.
        if (count($errors) === 0) {
            // Add the new post to the database.
            $db->query("INSERT INTO `posts` (`title`, `tags`, `content`, `account`, `starttime`, `icon`, `published`) VALUES ('" . $db->real_escape_string($_POST["title"]) . "', '" . $db->real_escape_string($_POST["tags"]) . "', '" . $db->real_escape_string($_POST["content"]) . "', '" . $db->real_escape_string($_SESSION["id"]) . "', '" . time() . "', 'none', '1')");
            // Print a message.
            // Temporarily leaving this as-is due to htmlspecialchars() call in success().
            $content .= "<div class='success'>Successfully made <a href='" . makeURL("post/{$db->insert_id}") . "'>post</a>.</div>";
            $success = true;
            redirect("post/{$db->insert_id}", 2);
        }
        // Otherwise, print the errors.
        else {
            foreach ($errors as $e) {
                $content .= error($e);
            }
        }
    }
}
// Display the new post form.
if (!$success) {
    $content .=
    "<div class='form newPostForm'>
        <h1>New Post</h1>
        <form method='post'>
            <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
            <label for='title'>Title: </label><input type='text' name='title' id='title' maxlength='32'" . (isset($_POST["title"]) ? " value='" . htmlspecialchars($_POST["title"]) . "'" : "") . "></input></br>
            <label for='tags'>Tags: </label><input type='text' name='tags' id='tags' maxlength='128'" . (isset($_POST["tags"]) ? " value='" . htmlspecialchars($_POST["tags"]) . "'" : "") . "></input></br>
            <label for='content'>Content: </label><textarea name='content' id='content' maxlength='65500'>" . (isset($_POST["content"]) ? htmlspecialchars($_POST["content"]) : "") . "</textarea></br>
            <br><input type='submit' value='Submit post' class='button'></input>
        </form>
    </div>";
}

?>
