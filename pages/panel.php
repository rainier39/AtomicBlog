<?php
// panel.php
// Serves as an all-purpose utility for the blog administrator.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// If the user isn't logged in, don't let them into the panel.
if (!isset($_SESSION["logged_in"]) or ($_SESSION["logged_in"] !== true)) {
    echo("You must be logged in to access the panel.");
}
// Display the default page.
elseif (!isset($url[1]) or $url[1] == "") {
    echo("<a href='/panel/newpost/'>Create a new post</a>");
}
// Direct the user to the "create a new post" page.
elseif ($url[1] == "newpost") {
    // Handle requests.
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Add the new post to the database.
        $db->query("INSERT INTO `posts` (title, tags, content, account, startip, startuseragent, starttime, editip, edituseragent, editedby, edittime, icon, published, starred) VALUES ('" . $db->real_escape_string($_POST["title"]) . "', '" . $db->real_escape_string($_POST["tags"]) . "', '" . $db->real_escape_string($_POST["content"]) . "', '" . $db->real_escape_string($_SESSION["id"]) . "', '" . $db->real_escape_string(ip2long($_SERVER["REMOTE_ADDR"])) . "', '" . $db->real_escape_string($_SERVER["HTTP_USER_AGENT"]) . "', '" . $db->real_escape_string(time()) . "', '" . $db->real_escape_string(ip2long($_SERVER["REMOTE_ADDR"])) . "', '" . $db->real_escape_string($_SERVER["HTTP_USER_AGENT"]) . "', '" . $db->real_escape_string($_SESSION["id"]) . "', '" . $db->real_escape_string(time()) . "', 'none', '0', '0')");
        // Print a message.
        echo("Successfully made post.");
    }
    // Display the new post form.
    else {
        echo("
        <div class='newPostForm'>
            <h2>New Post</h2>
            <form method='post'>
                <label>Title: </label><input type='text' name='title' maxlength='32'></input></br>
                <label>Tags: </label><input type='text' name='tags' maxlength='128'></input></br>
                <label>Content: </label><textarea name='content' maxlength='32767'></textarea></br>
                <br><input type='submit' value='Submit post' id='buttonNewPost'></input>
            </form>
        </div>
        ");
    }
}
// Display an error page.
else {
    echo("The page you requested doesn't exist.");
}

?>

