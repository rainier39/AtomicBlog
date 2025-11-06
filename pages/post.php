<?php
// post.php
// Displays an individual post.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$content = "";
$displayPost = true;

// Get the requested post.
$post = $db->query("SELECT * FROM `posts` WHERE id='" . $db->real_escape_string($url[1]) . "'");

// Print a message if the post doesn't exist.
if ($post->num_rows < 1) {
    $content .= "The requested post doesn't exist.";
    $displayPost = false;
}
// Handle deletions.
elseif (($_SERVER["REQUEST_METHOD"] == "POST") && (isset($_POST["delete"]))) {
    while ($p = $post->fetch_assoc()) {
        // Make sure the user is allowed to delete the post.
        if (isset($_SESSION["id"]) && ($_SESSION["id"] === $p["account"])) {
            // If the CSRF token is sent and valid.
            if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
                // Generate a new token.
                generateCSRFToken();
                
                // Delete the post.
                $db->query("DELETE FROM `posts` WHERE id='" . $db->real_escape_string($url[1]) . "'");
                // Delete all of the post's views.
                $db->query("DELETE FROM `views` WHERE post='" . $db->real_escape_string($url[1]) . "'");
                // Delete all of the post's comments.
                $db->query("DELETE FROM `comments` WHERE post='" . $db->real_escape_string($url[1]) . "'");
                $content .= "Successfully deleted the post.";
                $displayPost = false;
            }
        }
    }
}
// Handle editing.
elseif (isset($url[2]) && ($url[2] == "edit")) {
    $displayPost = false;
    $success = false;
    while ($p = $post->fetch_assoc()) {
        // Make sure the user is allowed to edit the post.
        if (isset($_SESSION["id"]) && ($_SESSION["id"] === $p["account"])) {
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // If the CSRF token is sent and valid.
                if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
                    // Generate a new token.
                    generateCSRFToken();

                    $errors = validatePost();
        	
        	        // If there are no errors, edit the post.
        	        if (count($errors) === 0) {
        	            $db->query("UPDATE `posts` SET title='" . $db->real_escape_string($_POST["title"]) . "', tags='" . $db->real_escape_string($_POST["tags"]) . "', content='" . $db->real_escape_string($_POST["content"]) . "', editedby='" . $db->real_escape_string($_SESSION["id"]) . "', edittime='" . $db->real_escape_string(time()) . "' WHERE id='" . $db->real_escape_string($url[1]) . "'");
        	            $success = true;
        	        }
        	        // Otherwise, print the errors.
        	        else {
        	            foreach ($errors as $e) {
        	                $content .= "<div class='error'>" . $e . "</div>";
        	            }
        	        }
                }
            }
            if (!$success) {
                $content .= "<div class='editPostForm'>
                    <h2>Edit Post</h2>
                    <form method='post'>
                        <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
                        <label>Title: </label><input type='text' name='title' maxlength='32'" . (isset($_POST["title"]) ? " value='" . htmlspecialchars($_POST["title"]) . "'" : "value='" . htmlspecialchars($p["title"]) . "'") . "></input></br>
                        <label>Tags: </label><input type='text' name='tags' maxlength='128'" . (isset($_POST["tags"]) ? " value='" . htmlspecialchars($_POST["tags"]) . "'" : "value='" . htmlspecialchars($p["tags"]) . "'") . "></input></br>
                        <label>Content: </label><textarea name='content' maxlength='65500'>" . (isset($_POST["content"]) ? htmlspecialchars($_POST["content"]) : htmlspecialchars($p["content"])) . "</textarea></br>
                        <br><input type='submit' value='Edit post' id='buttonEditPost'></input>
                    </form>
                </div>";
            }
        }
    }
    if ($success) $displayPost = true;
}
// Otherwise, display the post.
if ($displayPost) {
    $formats = array("png", "jpg", "gif", "webp");
    // Get the requested post again if the user edited it.
    if (isset($url[2]) && ($url[2] == "edit")) {
        $post = $db->query("SELECT * FROM `posts` WHERE id='" . $db->real_escape_string($url[1]) . "'");
    }
    while ($p = $post->fetch_assoc()) {
        $content .=
        "<div class='post'>
            <div class='postButtons'>
                " . ((($_SESSION["id"] ?? "") === $p["account"]) ? "<a href='" . makeURL("post/{$p["id"]}/edit") . "' class='postButton'>Edit</a> <form method='post' onsubmit='return confirm(\"Are you sure you want to delete this post?\");'><input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'><input type='submit' class='postButton' name='delete' value='Delete'></form>" : "") . "
            </div>
            <div class='postHeader'>
                <h2>" . htmlspecialchars($p["title"]) . "</h2>";
        // Get the account information of the post author.
        $acc = $db->query("SELECT name FROM `accounts` WHERE id='" . $db->real_escape_string($p["account"]) . "'");
        if ($acc->num_rows > 0) {
            while ($a = $acc->fetch_assoc()) {
                $content .= "<h4>By: " . htmlspecialchars($a["name"]) . "</h4>";
            }
        }
        else {
            $content .= "<h4>By: Nobody</h4>";
        }
        $content .= "<h4>Published: <span title='" . date ("h:i:s", $p["starttime"]). "'>" . date("m-d-Y", $p["starttime"]) . "</span></h4>";
        if (!empty($p["edittime"])) {
            $content .= "<h4>Modified: <span title='" . date ("h:i:s", $p["edittime"]). "'>" . date("m-d-Y", $p["edittime"]) . "</span></h4>";
        }
        $icon = $p["icon"];
        $id = (int)$p["id"];
        // Display the post's image if it exists.
        if (in_array($icon, $formats) && file_exists("images/" . $id . "." . $icon)) {
            $content .= "<img src='/images/" . $id . "." . $icon . "'></br>";
        }
        $content .= "</div>
            <div class='postContent'>
            " . format($p["content"]) . "
            </div>
        </div>";
    }

    // Get views from this IP on this post, if any.
    $views = $db->query("SELECT 1 FROM `views` WHERE ip='" . $db->real_escape_string(ip2long($_SERVER["REMOTE_ADDR"])) . "' AND post='" . $db->real_escape_string($url[1]) . "'");

    // If there are none, count this as a view.
    if ($views->num_rows < 1) {
        $db->query("INSERT INTO `views` (ip, timestamp, post) VALUES ('" . $db->real_escape_string(ip2long($_SERVER["REMOTE_ADDR"])) . "', '" . $db->real_escape_string(time()) . "', '" . $db->real_escape_string($url[1]) . "')");
    }
}

render($content);

?>

