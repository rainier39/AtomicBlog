<?php
// index.php
// Initializes the software and handles all requests first.

// Define a constant to ensure pages are only loaded through this index file.
define("INDEX", "1");

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
require "languages/" . $config["language"] . ".php";

// Initialize the file containing all of the global functions.
require "core/functions.php";

// Initialize the formatter.
require "core/formatter.php";

// Break up the URL for easy use throughout the software.
$url = explode('/', $_GET['url']);

// Initialize the session.
session_start();

// Serve the header.
require "pages/header.php";

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
elseif ($url[0] == "contact")
{
    // Display the contact page content.
    echo("
    <div class='contact'>
        <h2>Contact Us</h2>
        <label>Email: </label><a href='mailto:placeholder@example.com'>placeholder@example.com</a></br>
        <label>Phone: </label>000-000-0000
    </div>
    ");
}
elseif ($url[0] == "about")
{
    // Display the about page content.
    echo("
    <div class='about'>
        <h2>About Us</h2>
        Lorem ipsum...
    </div>
    ");
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

// Serve the footer.
require "pages/footer.php";

?>
