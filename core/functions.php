<?php
// functions.php
// Defines global functions used throughout the software.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// Log a user out.
function logout() {
    session_unset();
    session_destroy();
}

// Render a page, placing the header and footer accordingly.
function render(string $content) {
    global $config;
    require "pages/header.php";
    echo($content);
    require "pages/footer.php";
}

// Set the user's CSRF token, overwriting the prior one if any.
function generateCSRFToken() {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Display a blog post tile.
function displayPost($id, $icon, $title, $account) {
    global $db;
    
    $id = (int)$id;
    $formats = array("png", "jpg", "gif");
    
    $post = "<div class='posts'><a href='/post/" . $id . "/'>";
    // If there is an icon, display it.
    if (in_array($icon, $formats) && file_exists("images/" . $id . "." . $icon)) {
        $post .= "<img src='/images/" . $id . "." . $icon . "'>";
    }
    //else {
    //}
    $post .= "</a></br><a href='/post/" . $id . "/'>" . htmlspecialchars($title) . "</a>";
    // Get the account information of the post author.
    $acc = $db->query("SELECT name FROM `accounts` WHERE id='" . $db->real_escape_string($account) . "'");
    if ($acc->num_rows > 0) {
        while ($a = $acc->fetch_assoc()) {
            $post .= "</br><small>By: " . htmlspecialchars($a["name"]) . "</small>";
        }
    }
    else {
        $post .= "</br><small>By: Nobody</small>";
    }
    $post .= "</div>";
    
    return $post;
}

?>

