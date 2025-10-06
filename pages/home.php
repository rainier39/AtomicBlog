<?php
// home.php
// Displays the homepage.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$content = "";

// Get all of the starred blog posts.
$starred = $db->query("SELECT * FROM `posts` WHERE starred='1'");

// Display the starred posts fieldset.
$content .= "<fieldset class='starred'><legend>Starred Posts</legend>";

// Only display the posts if there are any.
if ($starred->num_rows > 0) {
    // Display the starred posts themselves.
    while ($s = $starred->fetch_assoc()) {
        $content .= displayPost($s["id"], $s["icon"], $s["title"]);
    }
}
// Otherwise print a message.
else {
    $content .= "No starred posts yet.";
}

// End the starred posts fieldset.
$content .= "</fieldset>";

// Create an array to store every postid and later how many views each one has.
$views = array();

// Get all the postids.
$postids = $db->query("SELECT * FROM `posts`");

// If there are any posts...
if ($postids->num_rows > 0) {
    // Populate the array.
    while ($p = $postids->fetch_assoc()) {
        $views[$p["id"]] = 0;
    }

    // Get all of the views, and just get their posts.
    $posts = $db->query("SELECT post FROM `views`");

    // Fill the array with the proper amount of views per post.
    while ($p = $posts->fetch_assoc()) {
        $views[$p["post"]] += 1;
    }

    // Make a second array consisting of the 5 highest viewed posts, namely their ids.
    $mostViewed = array();

    // Populate the array.
    for ($i = 0; (($i < 5) and (count($views) !== 0)); $i++) {
        // Find the highest viewed post.
        $value = max($views);

        // Get its key.
        $key = array_search($value, $views);

        // Remove it from the array.
        unset($views[$key]);

        // Add the postid to the new array.
        $mostViewed[$i] = $key;
    }
}

// Display the most viewed fieldset.
$content .= "</br><fieldset class='mostViewed'><legend>Most Viewed</legend>";

// Only try to display posts if there are any.
if ($postids->num_rows > 0) {
    foreach ($mostViewed as $mv) {
        // Get the posts we wish to display.
        $mostViewedPosts = $db->query("SELECT * FROM `posts` WHERE id='" . $db->real_escape_string($mv) . "'");

        // Display the posts.
        while ($m = $mostViewedPosts->fetch_assoc()) {
            $content .= displayPost($m["id"], $m["icon"], $m["title"]);
        }
    }
}
// Otherwise print a message.
else {
    $content .= "No posts yet.";
}

// End the fieldset.
$content .= "</fieldset>";

// Get the 5 most recent posts.
$recent = $db->query("SELECT * FROM `posts` ORDER BY starttime DESC LIMIT 5");

// Display the most recent fieldset.
$content .= "</br><fieldset class='mostRecent'><legend>Most Recent</legend>";

// Only try to display posts if there are any.
if ($recent->num_rows > 0) {
    // Display the posts.
    while ($r = $recent->fetch_assoc()) {
        $content .= displayPost($r["id"], $r["icon"], $r["title"]);
    }
}
// Otherwise print a message.
else {
    $content .= "No posts yet.";
}

// End the fieldset.
$content .= "</fieldset>";

// Finally, render the page.
render($content);

?>
