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
    $content .= "<div class='panelcontent'>";
    $content .= "<h1>Panel</h1>";
    $content .= "<h2>User Actions</h2>";
    $content .= "<a href='" . makeURL("panel/newpost") . "'>Create a new post</a>";
    $content .= "</div>";
}
// Direct the user to the "create a new post" page.
elseif ($url[1] == "newpost") {
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
        	    $content .= "Successfully made <a href='" . makeURL("post/{$db->insert_id}") . "'>post</a>.";
        	    $success = true;
        	    redirect("post/{$db->insert_id}", 2);
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
                <label for='title'>Title: </label><input type='text' name='title' id='title' maxlength='32'" . (isset($_POST["title"]) ? " value='" . htmlspecialchars($_POST["title"]) . "'" : "") . "></input></br>
                <label for='tags'>Tags: </label><input type='text' name='tags' id='tags' maxlength='128'" . (isset($_POST["tags"]) ? " value='" . htmlspecialchars($_POST["tags"]) . "'" : "") . "></input></br>
                <label for='content'>Content: </label><textarea name='content' id='content' maxlength='65500'>" . (isset($_POST["content"]) ? htmlspecialchars($_POST["content"]) : "") . "</textarea></br>
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

