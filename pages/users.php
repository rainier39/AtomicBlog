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
    $messages[] = error("error.noviewpage");
    render_page("", array(), $title);
    exit();
}

if (validateCSRFToken()) {
    $victimQuery = preparedQuery("SELECT `role`, `id` FROM `accounts` WHERE `id`=?", array($_POST["id"] ?? 0));
    $victim = $victimQuery->fetch_assoc();
    if (!checkPerm(PERM_MANAGE_USERS)) {
        $messages[] = error("error.nopermission");
    }
    elseif ($victimQuery->num_rows < 1) {
        $messages[] = error("error.noaccount");
    }
    elseif ($_SESSION["id"] == $victim["id"]) {
        $messages[] = error("error.nopermission");
    }
    // The current user must outrank the target user.
    elseif ($permissions[$_SESSION["role"]] <= $permissions[$victim["role"]]) {
        $messages[] = error("error.nopermission");
    }
    elseif (!array_key_exists($_POST["changerole"], $permissions)) {
        $messages[] = error("error.norole");
    }
    // User accounts cannot be turned into Guests.
    elseif ($_POST["changerole"] == "Guest") {
        $messages[] = error("error.nopermission");
    }
    // The current user can't give someone a role above/including their own.
    elseif ($permissions[$_POST["changerole"]] >= $permissions[$_SESSION["role"]]) {
        $messages[] = error("error.nopermission");
    }
    elseif ($_POST["changerole"] == $victim["role"]) {
        $messages[] = info("info.nochange");
    }
    else {
        preparedQuery("UPDATE `accounts` SET `role`=? WHERE `id`=?", array($_POST["changerole"], $victim["id"]));
        $messages[] = success("success.changerole");
        // Log the event.
        preparedQuery("INSERT INTO `logs` (`logtype`, `targetid`, `perpid`, `ip`, `useragent`, `timestamp`, `content`) VALUES ('change_role', ?, ?, ?, ?, ?, ?)", array($victim["id"], $_SESSION["id"], $_SERVER["REMOTE_ADDR"], substr($_SERVER["HTTP_USER_AGENT"], 0, 256), time(), "{$victim["role"]}|{$_POST["changerole"]}"));
    }
}

$usersvars = array(
 "users" => "",
 "pagination" => ""
);

$itemsPerPage = 20;
$userCountQuery = $db->query("SELECT COUNT(*) FROM `accounts`");
$userCount = $userCountQuery->fetch_assoc()["COUNT(*)"];
$pages = ceil($userCount/$itemsPerPage);

// Figure out what page we're on.
if (isset($url[1])) {
    $page = clamp((float)$url[1], 1, $pages);
}
// Default to first page.
else {
    $page = 1;
}

// Calculate the offset.
$offset = ($page - 1) * $itemsPerPage;

$usersQuery = preparedQuery("SELECT * FROM `accounts` LIMIT ? OFFSET ?", array($itemsPerPage, $offset));
if ($usersQuery->num_rows < 1) {
    $usersvars["users"] .= info("info.nousers");
}
else {
    while ($u = $usersQuery->fetch_assoc()) {
        $tilevars = array(
         "name" => $u["namevisible"] ? $u["name"] : lang("user.anonymous"),
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
                 <div>[[ global.username: ]] " . htmlspecialchars($u["username"]) . "</div>
                 <div>[[ global.email: ]] " . htmlspecialchars($u["email"]) . "</div>
                 <div>[[ user.joinip ]] " . htmlspecialchars($u["joinip"]) . "</div>
                 <div>[[ user.currentip ]] " . htmlspecialchars($u["ip"]) . "</div>
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
                 <input type='submit' value='" . lang("user.change") . "'>
                </form>";
            }
        }
        $usersvars["users"] .= render_template("userlistTile.html", $tilevars, false);
    }
}

// Generate the pagination.
$usersvars["pagination"] = generatePagination($pages, $page, "users");

render_page("users.html", $usersvars, $title);

?>
