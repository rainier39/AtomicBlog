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
// Provides an interface for managing all user accounts.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

if (!checkPerm(PERM_MANAGE_USERS)) {
    $content .= error("You don't have permission to do this.");
    render($content, $title);
    exit();
}
$title = "Manage Users";
// Handle requests.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // If the CSRF token is sent and valid.
    if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
        // Generate a new token.
        generateCSRFToken();
        
        // Change a user's role.
        if (isset($_POST["changerole"]) and isset($_POST["id"])) {
            $rolequery = $db->query("SELECT `role` FROM `accounts` WHERE `id`='" . $_SESSION["id"] . "'");
            $crole = $rolequery->fetch_column(0);
            $userquery = $db->query("SELECT 1 FROM `accounts` WHERE `id`='" . $db->real_escape_string($_POST["id"]) . "'");
            // Does the user exist?
            if ($userquery->num_rows != 1) {
                $content .= error("Specified user does not exist.");
            }
            // Does the role exist?
            elseif (!array_key_exists($_POST["changerole"], $permissions)) {
                $content .= error("Specified role does not exist.");
            }
            // Does the role have less permissions than our own?
            elseif ($permissions[$_POST["changerole"]] >= $permissions[$crole]) {
                $content .= error("You don't have permission to do this.");
            }
            // Change the role.
            else {
                $db->query("UPDATE `accounts` SET `role`='" . $db->real_escape_string($_POST["changerole"]) . "' WHERE `id`='" . (int)$_POST["id"] . "'");
            }
        }
    }
}

// Display the userlist.
$content .= "<h1>Manage Users</h1>";
    
$users = $db->query("SELECT `id`, `name`, `username`, `role`, `email`, `joinip`, `jointime`, `ip`, `lastactive` FROM `accounts`");
    
$content .= "<table class='userlist'>";
$content .= "<thead>
  <th>Username</th>
  <th>Name</th>
  <th>Role</th>
  <th>Email</th>
  <th>Join IP</th>
  <th>IP (last login)</th>
  <th>Joined</th>
  <th>Last Active</th>
</thead>
<tbody>";
while ($u = $users->fetch_assoc()) {
    // If this user's role is below the current user's role, allow it to be changed.
    $rolequery = $db->query("SELECT `role` FROM `accounts` WHERE `id`='" . $_SESSION["id"] . "'");
    $crole = $rolequery->fetch_column(0);
    if ($permissions[$u["role"]] < $permissions[$crole]) {
        $roleform = "<form method='post'>";
        $roleform .= "<input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>";
        $roleform .= "<select name='changerole'>";
        foreach ($permissions as $role=>$perm) {
            if (($perm < $permissions[$crole]) and ($role != "Guest")) {
                if ($role == $u["role"]) {
                    $s = " selected";
                }
                else {
                    $s = "";
                }
                $roleform .= "<option value='" . htmlspecialchars($role) . "'" . $s . ">" . htmlspecialchars($role) . "</option>";
            }
        }
        $roleform .= "</select>";
        $roleform .= "<input type='hidden' name='id' value='" . $u["id"] . "'>";
        $roleform .= " <input type='submit' value='Change'>";
        $roleform .= "</form>";
    }
    else {
        $roleform = htmlspecialchars($u["role"]);
    }
    $content .= "<tr>"
    . "<td data-label='Username'>" . htmlspecialchars($u["username"]) . "</td>"
    . "<td data-label='Name'>" . htmlspecialchars($u["name"]) . "</td>"
    . "<td data-label='Role'>" . $roleform . "</td>"
    . "<td data-label='Email'>" . htmlspecialchars($u["email"]) . "</td>"
    . "<td data-label='Join IP'>" . htmlspecialchars($u["joinip"]) . "</td>"
    . "<td data-label='IP (last login)'>" . htmlspecialchars($u["ip"]) . "</td>"
    . "<td data-label='Joined'>" . date("F jS Y", $u["jointime"]) . "</td>"
    . "<td data-label='Last Active'>" . date("F jS Y", $u["lastactive"]) . "</td>"
    . "</tr>";
}
$content .= "</tbody></table>";

?>
