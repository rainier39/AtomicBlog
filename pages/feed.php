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

// feed.php
// Atom feed.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

header("Content-Type: application/atom+xml");
// This is here for debugging purposes.
//header("Content-Type: text/plain");

$uri = ($config["https"] ? "https://" : "http://") . $_SERVER["SERVER_NAME"];

$lastPostTime = 0;
$lastPostTimeQuery = $db->query("SELECT `starttime`, `edittime` FROM `posts` WHERE `published`='1' ORDER BY `edittime` DESC,`starttime` DESC LIMIT 1");
while ($t = $lastPostTimeQuery->fetch_assoc()) {
    $lastPostTime = $t["edittime"] ?? $t["starttime"];
}

// Get the latest 15 posts.
$latest = $db->query("SELECT `title`, `id`, `account`, `starttime`, `edittime`, `content` FROM `posts` WHERE `published`='1' ORDER BY `starttime` DESC LIMIT 15");

echo('<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">

  <title>' . htmlspecialchars($config["title"], ENT_NOQUOTES) . '</title>
  <subtitle>' . htmlspecialchars($config["description"], ENT_NOQUOTES) . '</subtitle>
  <link rel="self" href="' . $uri . makeURL("feed") . '" type="application/atom+xml"/>
  <updated>' . date("Y-m-d\\TH:i:sP", $lastPostTime) . '</updated>
  <icon>' . $uri . makeURL("themes/" . $config["theme"] . "/icon.png") . '</icon>
  <author>
');

$names = array();
while ($l = $latest->fetch_assoc()) {
    $name = $db->query("SELECT `name` FROM `accounts` WHERE `id`='" . $l["account"] . "'");
    $name = $name->fetch_assoc()["name"];
    if (!in_array($name, $names)) {
        $names[] = $name;
        echo("    <name>" . htmlspecialchars($name, ENT_NOQUOTES) . "</name>\n");
    }
}

echo('  </author>
  <id>' . $uri . '/' . ($config["dir"] ? $config["dir"] . "/" : "") . '</id>
  <generator uri="https://github.com/rainier39/AtomicBlog/releases/tag/' . $config["version"] . '" version="' . $config["version"] . '">
    AtomicBlog
  </generator>
');

mysqli_data_seek($latest,0);
while ($l = $latest->fetch_assoc()) {
    echo('
  <entry>
    <title>' . htmlspecialchars($l["title"], ENT_NOQUOTES) . '</title>
    <link href="' . $uri . makeURL("post/{$l["id"]}") . '"/>
    <id>' . $uri . makeURL("post/{$l["id"]}") . '</id>
    <updated>' . date("Y-m-d\\TH:i:sP", ($l["edittime"] ?? $l["starttime"])) . '</updated>
    <content>' . htmlspecialchars($l["content"], ENT_NOQUOTES) . '</content>
  </entry>');
}

echo('

</feed>');

?>
