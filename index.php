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

// index.php
// Initializes the software and handles all requests first.

// Define a constant to ensure pages are only loaded through this index file.
define("INDEX", "1");
// Define the software's current version.
define("VERSION", "v2.5.0-alpha");

// Prevent clickjacking by preventing the website from loading in an iframe.
// TODO: make it possible to disable this in the config.
header("Content-Security-Policy: frame-ancestors 'none';");
header("X-Frame-Options: DENY");

// Get the configuration settings.
require "core/default_config.php";
if (file_exists("core/config.php")) {
    require "core/config.php";
}
else {
    $config = array();
}
$config = array_merge($default_config, $config);

// Make sure that the page is accessed over HTTPS if applicable.
$ishttps = $_SERVER["HTTPS"] ?? "";
if (($ishttps != "on") && $config["https"])
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}

// Initialize the permissions file.
require "core/default_permissions.php";
// Initialize the file containing all of the global functions.
require "core/functions.php";

// If the forum is installed, create a database connection.
if ($config["installed"])
{
    // Establish a connection to the database.
    $db = mysqli_connect($config["SQLServer"], $config["SQLUsername"], $config["SQLPassword"], $config["SQLDatabase"]);
    // Run the upgrade script.
    require "core/upgrade.php";
}

// Initialize the language file.
// TODO: implement languages, loader function to read a JSON file for languages.

// Initialize the formatter.
require "core/formatter.php";

// Break up the URL for easy use throughout the software.
$url = explode('/', ($_GET['url'] ?? ""));

// Initialize the session.
session_name("AtomicBlog");
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => "strict",
    // Only set the secure attribute if the site is being served over HTTPS.
    'cookie_secure' => (($ishttps == "on") ? true : false),
]);

// Generate a CSRF token if needed.
if (!isset($_SESSION["csrf_token"])) {
    generateCSRFToken();
}

// If a user is logged in but lacks permission to log in (i.e. their role has been changed since they logged in), log them out.
if (isset($_SESSION["logged_in"]) and $_SESSION["logged_in"] and (!checkPerm(PERM_LOGIN))) {
    logout(true);
}

// If the software hasn't been installed yet, direct all requests to the install page.
if ($config["installed"] == false)
{
    require "core/install.php";
}
elseif ($url[0] == "logout")
{
    logout(true);
    require "pages/home.php";
}
elseif ($url[0] == "login")
{
    require "pages/login.php";
}
elseif ($url[0] == "panel")
{
    require "pages/panel.php";
}
elseif ($url[0] == "posts")
{
    require "pages/posts.php";
}
elseif ($url[0] == "register")
{
    require "pages/register.php";
}
elseif ($url[0] == "post")
{
    require "pages/post.php";
}
// Default everything else to the homepage.
else
{
    require "pages/home.php";
}

?>
