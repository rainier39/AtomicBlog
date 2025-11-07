<?php
// index.php
// Initializes the software and handles all requests first.

// Define a constant to ensure pages are only loaded through this index file.
define("INDEX", "1");

// Prevent clickjacking by preventing the website from loading in an iframe.
header("Content-Security-Policy: frame-ancestors 'none';");
header("X-Frame-Options: DENY");

// Initialize the configuration file.
require "core/config.php";

// Make sure that the page is accessed over HTTPS if applicable.
if ((isset($_SERVER["HTTPS"])) && ($_SERVER["HTTPS"] != "on") && $config["https"])
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}

// If the forum is installed, create a database connection.
if ($config["installed"])
{
    // Establish a connection to the database.
    $db = mysqli_connect($config["SQLServer"], $config["SQLUsername"], $config["SQLPassword"], $config["SQLDatabase"]);

    // Display an error if the connection failed.
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit;
    }
}

// Initialize the language file.
// TODO: implement languages, loader function to read a JSON file for languages.
//require "languages/" . $config["language"] . ".php";

// Initialize the file containing all of the global functions.
require "core/functions.php";

// Initialize the formatter.
require "core/formatter.php";

// Break up the URL for easy use throughout the software.
$url = explode('/', ($_GET['url'] ?? ""));

// Initialize the session.
session_name("AtomicBlog");
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => "strict",
    'cookie_secure' => $config["https"],
]);

// Generate a CSRF token if needed.
if (!isset($_SESSION["csrf_token"])) {
    generateCSRFToken();
}

// If the software hasn't been installed yet, direct all requests to the install page.
if ($config["installed"] == false)
{
    require "core/install.php";
}
elseif ($url[0] == "logout")
{
    logout();
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
