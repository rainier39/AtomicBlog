<?php
// post.php
// Displays an individual post.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$content = "";

// Get the requested post.
$post = $db->query("SELECT * FROM `posts` WHERE id='" . $db->real_escape_string($url[1]) . "'");

// Print a message if the post doesn't exist.
if ($post->num_rows < 1) {
    $content .= "The requested post doesn't exist.";
}
// Otherwise, display the post.
else {
    $formats = array("png", "jpg", "gif", "webp");
    while ($p = $post->fetch_assoc()) {
        $content .=
        "<div class='post'>
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

