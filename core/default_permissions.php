<?php
/*
 * Copyright © 2025 rainier39 <rainier39@proton.me>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

// default_permissions.php
// Stores the default permissions for the default roles.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

/* Permissions are stored as individual bits within an integer. Every role has an entry in the $permissions array with one of these integers. The permissions are all defined here as constants, their values indicating which bit in the integer must be set for them to be given. Permissions can be granted by using the bitwise OR operator. Permissions may be revoked using the bitwise XOR operator (see the config/permissions.php file for an example of this). */

define("PERM_VIEW_POST", 1);
define("PERM_VIEW_POSTS", 2);
define("PERM_LOGIN", 4);
// Whether a user with the role can create posts.
define("PERM_NEW_POST", 8);
// The following 4 apply only to one's OWN post.
define("PERM_EDIT_POST", 16);
define("PERM_DELETE_POST", 32);
define("PERM_STAR_POST", 64);
define("PERM_PUBLISH_POST", 128);
// Allows changing users' roles.
define("PERM_MANAGE_USERS", 256);
// Allows configuring blog.
define("PERM_MANAGE_BLOG", 4096);

$permissions = array(
    "Owner" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_LOGIN|PERM_NEW_POST|PERM_EDIT_POST|PERM_DELETE_POST|PERM_STAR_POST|PERM_PUBLISH_POST|PERM_MANAGE_BLOG|PERM_MANAGE_USERS,
    "Moderator" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_LOGIN|PERM_NEW_POST|PERM_EDIT_POST|PERM_DELETE_POST|PERM_PUBLISH_POST,
    "Author" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_LOGIN|PERM_NEW_POST|PERM_EDIT_POST|PERM_DELETE_POST|PERM_PUBLISH_POST,
    "Member" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_LOGIN,
    // 0 means no permissions at all.
    "Suspended" => 0,
    "Unapproved" => 0,
    "Guest" => PERM_VIEW_POST|PERM_VIEW_POSTS
);

?>
