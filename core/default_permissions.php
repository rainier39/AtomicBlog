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

// Guest appropriate permissions.
define("PERM_VIEW_POST", 1);
define("PERM_VIEW_POSTS", 2);
define("PERM_COMMENT", 4);
define("PERM_EDIT_COMMENT", 8);
define("PERM_DELETE_COMMENT", 16);

// Every permission here and beyond should be for accounts only.
define("PERM_LOGIN", 32);
// Whether a user can create posts, edit their posts, and publish/unpublish their posts.
define("PERM_NEW_POST", 64);
// The following 5 apply only to one's OWN post.
define("PERM_DELETE_POST", 128);
define("PERM_UPLOAD", 256);
define("PERM_STAR_POST", 512);

// ** Moderator perms **
// Edit/delete others' comments.
define("PERM_MOD_COMMENTS", 1024);
define("PERM_MOD_EDIT_POST", 2048);
define("PERM_MOD_UPLOAD", 4096);
define("PERM_MOD_DELETE_POST", 8192);
// Mods can't star by default but this is something one may want to allow.
define("PERM_MOD_STAR_POST", 16384);

// ** Admin perms **
// Allows changing users' roles.
define("PERM_MANAGE_USERS", 32768);
// Allows configuring blog.
define("PERM_MANAGE_BLOG", 65536);

$permissions = array(
    "Owner" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_COMMENT|PERM_LOGIN|PERM_NEW_POST|PERM_DELETE_POST|PERM_UPLOAD|PERM_STAR_POST|PERM_MOD_EDIT_POST|PERM_MOD_UPLOAD|PERM_MOD_DELETE_POST|PERM_MOD_STAR_POST|PERM_MANAGE_BLOG|PERM_MANAGE_USERS,
    "Moderator" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_COMMENT|PERM_LOGIN|PERM_NEW_POST|PERM_DELETE_POST|PERM_UPLOAD|PERM_MOD_EDIT_POST|PERM_MOD_UPLOAD|PERM_MOD_DELETE_POST,
    "Author" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_COMMENT|PERM_LOGIN|PERM_NEW_POST|PERM_UPLOAD|PERM_DELETE_POST,
    "Member" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_COMMENT|PERM_LOGIN,
    // 0 means no permissions at all.
    "Suspended" => 0,
    "Unapproved" => 0,
    "Guest" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_COMMENT
);

?>
