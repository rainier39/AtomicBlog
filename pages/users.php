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

// users.php
// Displays a list of all user accounts.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$title = "Users";

if ((($config["userlist"] == "users") and !$_SESSION["logged_in"])
or ($config["userlist"] == "admins") and !checkPerm(PERM_MANAGE_USERS)) {
    $messages[] = error("You don't have permission to view this page.");
    render_page("", array(), $title);
    exit();
}

$usersvars = array("users" => "");

$usersQuery = $db->query("SELECT * FROM `accounts`");
if ($usersQuery->num_rows < 1) {
    $usersvars["users"] .= info("No users to display.");
}
else {
    while ($u = $usersQuery->fetch_assoc()) {
        $tilevars = array(
         "name" => $u["name"],
         "role" => $u["role"],
         "lastactivedate" => date("F jS, Y", $u["lastactive"]),
         "lastactivetime" => date("g:i:sa", $u["lastactive"]),
         "color" => $u["color"],
         "profile" => makeURL("profile/" . $u["id"])
        );
        $usersvars["users"] .= render_template("userlistTile.html", $tilevars, false);
    }
}

render_page("users.html", $usersvars, $title);

?>
