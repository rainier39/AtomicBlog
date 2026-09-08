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

if (!$config["feedEnabled"]) {
    http_response_code(404);
    $messages[] = error("Page not found.");
    render_page("", array(), "Page not found");
    exit();
}

header("Content-Type: application/atom+xml");
// This is here for debugging purposes.
//header("Content-Type: text/plain");

$uri = $config["baseURL"] . "/";

$lastPostTime = 0;
$lastPostTimeQuery = $db->query("SELECT `starttime`, `edittime` FROM `posts` WHERE `published`='1' ORDER BY `edittime` DESC,`starttime` DESC LIMIT 1");
while ($t = $lastPostTimeQuery->fetch_assoc()) {
    $lastPostTime = $t["edittime"] ?? $t["starttime"];
}

// Get the latest 15 posts.
$latest = $db->query("SELECT `title`, `posts`.`id`, `accounts`.`name`, `starttime`, `edittime`, `content` FROM `posts` LEFT JOIN `accounts` ON `accounts`.`id`=`posts`.`account` WHERE `published`='1' ORDER BY `starttime` DESC LIMIT 15");

echo('<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">

  <title>' . htmlspecialchars($config["title"], ENT_NOQUOTES) . '</title>
  <subtitle>' . htmlspecialchars($config["description"], ENT_NOQUOTES) . '</subtitle>
  <link rel="self" href="' . $uri . makeURL("feed") . '" type="application/atom+xml"/>
  <updated>' . date("Y-m-d\\TH:i:sP", $lastPostTime) . '</updated>
  <icon>' . $uri . makeURL("themes/" . $config["theme"] . "/icon.png") . '</icon>
');

echo('  <id>' . $uri . makeURL("feed") . '</id>
  <generator uri="https://github.com/rainier39/AtomicBlog/releases/tag/' . $config["version"] . '" version="' . $config["version"] . '">
    AtomicBlog
  </generator>
');

while ($l = $latest->fetch_assoc()) {
    echo('
  <entry>
    <title>' . htmlspecialchars($l["title"], ENT_NOQUOTES) . '</title>
    <link href="' . $uri . makeURL("post/{$l["id"]}") . '"/>
    <id>' . $uri . makeURL("post/{$l["id"]}") . '</id>
    <updated>' . date("Y-m-d\\TH:i:sP", ($l["edittime"] ?? $l["starttime"])) . '</updated>
    <author>
      <name>' . htmlspecialchars($l["name"], ENT_NOQUOTES) . '</name>
    </author>
    <content>' . htmlspecialchars($l["content"], ENT_NOQUOTES) . '</content>
  </entry>');
}

echo('

</feed>');

?>
