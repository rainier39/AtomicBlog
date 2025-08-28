<?php
// post.php
// Displays an individual post.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// Get the requested post.
$post = $db->query("SELECT * FROM `posts` WHERE id='" . $db->real_escape_string($url[1]) . "'");

// Print a message if the post doesn't exist.
if ($post->num_rows < 1) {
    echo("The requested post doesn't exist.");
}
// Otherwise, display the post.
else {
    while ($p = $post->fetch_assoc()) {
        echo("
        <div class='post'>
            <div class='postHeader'>
                <h2>" . htmlspecialchars($p["title"]) . "</h2>
                <img src='/images/" . $p["id"] . "." . $p["icon"] . "'></br>
            </div>
            <div class='postContent'>
            " . format($p["content"]) . "
            </div>
        </div>
        ");
    }

    // Get views from this IP on this post, if any.
    $views = $db->query("SELECT 1 FROM `views` WHERE ip='" . $db->real_escape_string(ip2long($_SERVER["REMOTE_ADDR"])) . "' AND post='" . $db->real_escape_string($url[1]) . "'");

    // If there are none, count this as a view.
    if ($views->num_rows < 1) {
        $db->query("INSERT INTO `views` (ip, useragent, timestamp, post) VALUES ('" . $db->real_escape_string(ip2long($_SERVER["REMOTE_ADDR"])) . "', '" . $db->real_escape_string($_SERVER["HTTP_USER_AGENT"]) . "', '" . $db->real_escape_string(time()) . "', '" . $db->real_escape_string($url[1]) . "')");
    }
}

?>

