<?php
// panel.php
// Serves as an all-purpose utility for the blog administrator.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$content = "";
$success = false;

// If the user isn't logged in, don't let them into the panel.
if (!isset($_SESSION["logged_in"]) or ($_SESSION["logged_in"] !== true)) {
    $content .= "You must be logged in to access the panel.";
}
// Display the default page.
elseif (!isset($url[1]) or $url[1] == "") {
    $content .= "<a href='" . makeURL("panel/newpost") . "'>Create a new post</a>";
}
// Direct the user to the "create a new post" page.
elseif ($url[1] == "newpost") {
    // Handle requests.
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // If the CSRF token is sent and valid.
        if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
            // Generate a new token.
            generateCSRFToken();

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
        	
        	// If there are no errors, make the post.
        	if (count($errors) === 0) {
        	    // Add the new post to the database.
        	    $db->query("INSERT INTO `posts` (title, tags, content, account, startip, startuseragent, starttime, editip, edituseragent, editedby, edittime, icon, published, starred) VALUES ('" . $db->real_escape_string($_POST["title"]) . "', '" . $db->real_escape_string($_POST["tags"]) . "', '" . $db->real_escape_string($_POST["content"]) . "', '" . $db->real_escape_string($_SESSION["id"]) . "', '" . $db->real_escape_string(ip2long($_SERVER["REMOTE_ADDR"])) . "', '" . $db->real_escape_string($_SERVER["HTTP_USER_AGENT"]) . "', '" . $db->real_escape_string(time()) . "', '" . $db->real_escape_string(ip2long($_SERVER["REMOTE_ADDR"])) . "', '" . $db->real_escape_string($_SERVER["HTTP_USER_AGENT"]) . "', '" . $db->real_escape_string($_SESSION["id"]) . "', '" . $db->real_escape_string(time()) . "', 'none', '0', '0')");
        	    // Print a message.
        	    $content .= "Successfully made post.";
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
    // Display the new post form.
    if (!$success) {
        $content .=
        "<div class='newPostForm'>
            <h2>New Post</h2>
            <form method='post'>
            	<input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>
                <label>Title: </label><input type='text' name='title' maxlength='32'" . (isset($_POST["title"]) ? " value='" . htmlspecialchars($_POST["title"]) . "'" : "") . "></input></br>
                <label>Tags: </label><input type='text' name='tags' maxlength='128'" . (isset($_POST["tags"]) ? " value='" . htmlspecialchars($_POST["tags"]) . "'" : "") . "></input></br>
                <label>Content: </label><textarea name='content' maxlength='65500'>" . (isset($_POST["content"]) ? htmlspecialchars($_POST["content"]) : "") . "</textarea></br>
                <br><input type='submit' value='Submit post' id='buttonNewPost'></input>
            </form>
        </div>";
    }
}
// Display an error page.
else {
    $content .= "The page you requested doesn't exist.";
}

render($content);

?>

