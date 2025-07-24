<?php
// posts.php
// Displays the blog posts.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// Get all of the blog posts.
$posts = $db->query("SELECT * FROM `posts`");

// Display the allPosts fieldset.
echo("<fieldset class='allPosts'><legend>All Posts</legend>");

// If there are posts, display them.
if ($posts->num_rows > 0) {
    // Display the posts.
    while ($p = $posts->fetch_assoc()) {
        echo("<div class='posts'><a href='/post/" . $p["id"] . "/'><img src='/images/" . $p["id"] . "." . $p["icon"] . "'></a></br><a href='/post/" . $p["id"] . "/'>" . htmlspecialchars($p["title"]) . "</a></div>");
    }
}
// Otherwise print a message.
else {
    echo("No posts yet.");
}

// End the fieldset.
echo("</fieldset>");

?>

