<?php
// posts.php
// Displays the blog posts.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$content = "";

// Get all of the blog posts.
$posts = $db->query("SELECT * FROM `posts`");

// Display the allPosts fieldset.
$content .= "<fieldset class='allPosts'><legend>All Posts</legend>";

// If there are posts, display them.
if ($posts->num_rows > 0) {
    // Display the posts.
    while ($p = $posts->fetch_assoc()) {
        $content .= displayPost($p["id"], $p["icon"], $p["title"]);
    }
}
// Otherwise print a message.
else {
    $content .= "No posts yet.";
}

// End the fieldset.
$content .= "</fieldset>";

render($content);

?>

