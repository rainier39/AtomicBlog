<?php
// default_permissions.php
// Stores the default permissions for the default roles.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

define("PERM_VIEW_POST", 1);
define("PERM_VIEW_POSTS", 2);
define("PERM_LOGIN", 4);
// Whether a role can create posts.
define("PERM_NEW_POST", 8);
// The following 3 apply only to one's OWN post.
define("PERM_EDIT_POST", 16);
define("PERM_DELETE_POST", 32);
define("PERM_STAR_POST", 64);

$perms = array(
    "Owner" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_LOGIN,
    "Moderator" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_LOGIN,
    "Member" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_LOGIN,
    "Suspended" => 0,
    "Unapproved" => 0,
    "Guest" => PERM_VIEW_POST|PERM_VIEW_POSTS
);

?>
