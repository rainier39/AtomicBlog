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

if (!checkPerm(PERM_NEW_POST)) {
    $messages[] = error("You don't have permission to do this.");
    render_page("", array(), $title);
    exit();
}
$title = "New Post";
// Handle requests.
if (validateCSRFToken()) {
    // Remove ampersands from tags because they can be used to inject URL parameters.
    $_POST["tags"] = str_replace("&", "", $_POST["tags"] ?? "");
    // Remove slashes because they can ruin a search.
    $_POST["tags"] = str_replace("/", "", $_POST["tags"] ?? "");

    // Lock to avoid race conditions.
    $db->query("LOCK TABLE `posts` WRITE");
    $errors = validatePost();
    	
    // If there are no errors, make the post.
    if (!count($errors)) {
        if (isset($_POST["unpublished"]) and ($_POST["unpublished"] == "on")) {
            $published = "0";
        }
        else {
            $published = "1";
        }
        
        // Add the new post to the database.
        $db->query("INSERT INTO `posts` (`title`, `content`, `account`, `starttime`, `published`) VALUES ('" . $db->real_escape_string($_POST["title"]) . "', '" . $db->real_escape_string($_POST["content"]) . "', '" . $db->real_escape_string($_SESSION["id"]) . "', '" . time() . "', '" . $published . "')");
        $postid = $db->insert_id;
        $db->query("UNLOCK TABLES");
        // Add the tags.
        $newtags = parseTags($_POST["tags"]);
        if (count($newtags)) {
            $db->query("DELETE FROM `tags` WHERE `post`='{$postid}'");
            foreach ($newtags as $tag) {
                $db->query("INSERT INTO `tags` (`tag`,`post`) VALUES ('" . $db->real_escape_string($tag) . "', '{$postid}')");
            }
        }
        // Print a message.
        $_SESSION["messages"][] = unsafe_success("Successfully made new post.");
        redirect("post/{$postid}");
    }
    // Otherwise, print the errors.
    else {
        $db->query("UNLOCK TABLES");
        foreach ($errors as $e) {
            $messages[] = error($e);
        }
    }
}

$newpostvars = array("token" => $_SESSION["csrf_token"],
"title" => $_POST["title"] ?? "",
"tags" => $_POST["tags"] ?? "",
"markdownbuttons" => markdownButtons(),
"content" => $_POST["content"] ?? "",
"unpublished" => (isset($_POST["unpublished"]) ? " checked" : ""));

render_page("panel/newpost.html", $newpostvars, $title);

?>
