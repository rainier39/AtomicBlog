<?php
// panel.php
// Serves as an all-purpose utility for the blog administrator and users.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$content = "";
$success = false;
$title = "";

// If the user isn't logged in, don't let them into the panel.
if (!isset($_SESSION["logged_in"]) or ($_SESSION["logged_in"] !== true)) {
    $content .= "<div class='error'>You must be logged in to access this page.</div>";
}
// Display the default page.
elseif (!isset($url[1]) or $url[1] == "") {
    $title = "Panel";
    $content .= "<div class='panelcontent'>";
    $content .= "<h1>Panel</h1>";
    $content .= "<h2>User Actions</h2>";
    if (checkPerm(PERM_NEW_POST)) {
        $content .= "<a href='" . makeURL("panel/newpost") . "'>Create a new post</a>";
    }
    $content .= "</div>";
}
// Direct the user to the "create a new post" page.
elseif ($url[1] == "newpost") {
    if (!checkPerm(PERM_NEW_POST)) {
        $content .= "<div class='error'>You don't have permission to do this.</div>";
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
        	    $content .= "Successfully made <a href='" . makeURL("post/{$db->insert_id}") . "'>post</a>.";
        	    $success = true;
        	    redirect("post/{$db->insert_id}", 2);
        	}
        	// Otherwise, print the errors.
        	else {
        	    foreach ($errors as $e) {
        	        $content .= "<div class='error'>" . htmlspecialchars($e) . "</div>";
        	    }
        	}
        }
    }
    // Display the new post form.
    if (!$success) {
        $content .=
        "<div class='newPostForm'>
            <h1>New Post</h1>
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
    $content .= "<div class='error'>The page you requested doesn't exist.</div>";
}

render($content, $title);

?>

