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

define("PERM_VIEW_POST", 1);
define("PERM_VIEW_POSTS", 2);
define("PERM_LOGIN", 4);
// Whether a role can create posts.
define("PERM_NEW_POST", 8);
// The following 3 apply only to one's OWN post.
define("PERM_EDIT_POST", 16);
define("PERM_DELETE_POST", 32);
define("PERM_STAR_POST", 64);

$permissions = array(
    "Owner" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_LOGIN|PERM_NEW_POST|PERM_EDIT_POST|PERM_DELETE_POST|PERM_STAR_POST,
    "Moderator" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_LOGIN|PERM_NEW_POST|PERM_EDIT_POST|PERM_DELETE_POST,
    "Author" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_LOGIN|PERM_NEW_POST|PERM_EDIT_POST|PERM_DELETE_POST,
    "Member" => PERM_VIEW_POST|PERM_VIEW_POSTS|PERM_LOGIN,
    "Suspended" => 0,
    "Unapproved" => 0,
    "Guest" => PERM_VIEW_POST|PERM_VIEW_POSTS
);

?>
