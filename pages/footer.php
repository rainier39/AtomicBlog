<?php
// footer.php
// Serves the footer.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

echo("</div>

<div class='footer'>" . htmlspecialchars($config["footer"]) . "</div>

</body>
</html>");

?>

