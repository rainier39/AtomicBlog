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

if (!checkPerm(PERM_VIEW_PROFILE)
or (($config["userlist"] == "users") and !$_SESSION["logged_in"])
or ($config["userlist"] == "admins") and !checkPerm(PERM_MANAGE_USERS)) {
    $messages[] = error("You don't have permission to view this page.");
    render_page("", array(), $title);
    exit();
}

if (validateCSRFToken()) {
    $victimQuery = preparedQuery("SELECT `role`, `id` FROM `accounts` WHERE `id`=?", array($_POST["id"] ?? 0));
    $victim = $victimQuery->fetch_assoc();
    if (!checkPerm(PERM_MANAGE_USERS)) {
        $messages[] = error("You don't have permission to do this.");
    }
    elseif ($victimQuery->num_rows < 1) {
        $messages[] = error("The specified account does not exist.");
    }
    elseif ($_SESSION["id"] == $victim["id"]) {
        $messages[] = error("You cannot change your own role.");
    }
    // The current user must outrank the target user.
    elseif ($permissions[$_SESSION["role"]] <= $permissions[$victim["role"]]) {
        $messages[] = error("You don't have permission to do this.");
    }
    elseif (!array_key_exists($_POST["changerole"], $permissions)) {
        $messages[] = error("Specified role does not exist.");
    }
    // User accounts cannot be turned into Guests.
    elseif ($_POST["changerole"] == "Guest") {
        $messages[] = error("You don't have permission to do this.");
    }
    // The current user can't give someone a role above/including their own.
    elseif ($permissions[$_POST["changerole"]] >= $permissions[$_SESSION["role"]]) {
        $messages[] = error("You don't have permission to do this.");
    }
    elseif ($_POST["changerole"] == $victim["role"]) {
        $messages[] = info("Nothing to change.");
    }
    else {
        preparedQuery("UPDATE `accounts` SET `role`=? WHERE `id`=?", array($_POST["changerole"], $victim["id"]));
        $messages[] = success("Successfully changed role.");
        // Log the event.
        preparedQuery("INSERT INTO `logs` (`logtype`, `targetid`, `perpid`, `ip`, `useragent`, `timestamp`, `content`) VALUES ('change_role', ?, ?, ?, ?, ?, ?)", array($victim["id"], $_SESSION["id"], $_SERVER["REMOTE_ADDR"], substr($_SERVER["HTTP_USER_AGENT"], 0, 256), time(), "{$victim["role"]}|{$_POST["changerole"]}"));
    }
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
         "role" => htmlspecialchars($u["role"]),
         "joindate" => date("F jS, Y", $u["jointime"]),
         "jointime" => date("g:i:sa", $u["jointime"]),
         "lastactivedate" => date("F jS, Y", $u["lastactive"]),
         "lastactivetime" => date("g:i:sa", $u["lastactive"]),
         "color" => $u["color"],
         "profile" => makeURL("profile/" . $u["id"]),
         "admininfo" => ""
        );
        if (checkPerm(PERM_MANAGE_USERS)) {
            if ($permissions[$_SESSION["role"]] > $permissions[$u["role"]]) {
                $tilevars["admininfo"] = "<details>
                 <summary>More info</summary>
                 <div>Username: " . htmlspecialchars($u["username"]) . "</div>
                 <div>Email: " . htmlspecialchars($u["email"]) . "</div>
                 <div>Join IP: " . htmlspecialchars($u["joinip"]) . "</div>
                 <div>Current IP: " . htmlspecialchars($u["ip"]) . "</div>
                </details>";
            }
            if ($permissions[$_SESSION["role"]] > $permissions[$u["role"]]) {
                $tilevars["role"] = "<form method='post'>
                 <input type='hidden' name='csrf_token' value='" . $_SESSION["csrf_token"] . "'>";
                $tilevars["role"] .= "<select name='changerole'>";
                foreach ($permissions as $role=>$perm) {
                    if (($perm < $permissions[$_SESSION["role"]])
                    and ($role != "Guest")) {
                        if ($role == $u["role"]) $s = " selected";
                        else $s = "";
                        $tilevars["role"] .= "<option value='" . htmlspecialchars($role) . "'" . $s . ">" . htmlspecialchars($role) . "</option>";
                    }
                }
                $tilevars["role"] .= "</select>";
                $tilevars["role"] .= " <input type='hidden' name='id' value='" . $u["id"] . "'>
                 <input type='submit' value='Change'>
                </form>";
            }
        }
        $usersvars["users"] .= render_template("userlistTile.html", $tilevars, false);
    }
}

render_page("users.html", $usersvars, $title);

?>
