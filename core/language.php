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

// language.php
// Loads languages from their JSON files.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

$languages = array();
$langdir = scandir("languages");
$lang = array();

foreach ($langdir as $entry) {
    // Only consider .json files.
    if (str_ends_with($entry, ".json")) {
        $languages[] = substr($entry, 0, -5);
    }
}

if (count($languages) < 1) {
    $messages[] = error("No valid language files detected.");
    $language = "";
}
// If a valid language is selected.
elseif (in_array($config["language"], $languages)) {
    $language = $config["language"];
}
// Otherwise try defaulting to American English.
elseif (in_array("English (US)", $languages)) {
    $language = "English (US)";
}
// Otherwise just use the first available language.
else {
    $language = $languages[0];
}

function updateLang() {
    global $messages, $language, $lang;

    // Get the language.
    if ($language != "") {
        $json = file_get_contents("languages/" . $language . ".json");
    
        if ($json === false) {
            $messages[] = error("Failed to read language file.");
        }
        else {
            $lang = json_decode($json, true);
        }
    }

    if ($lang == NULL) {
        $lang = array();
        $messages[] = error("Invalid JSON in language file.");
    }
}

updateLang();

function lang($identifier) {
    global $lang;
    
    if (count($lang) < 1) {
        return "MISSING";
    }
    elseif (array_key_exists($identifier, $lang)) {
        return $lang[$identifier];
    }
    // Special case: ISO lang code should be empty string if unknown.
    elseif ($identifier == "code") {
        return "";
    }
    else {
        return "MISSING";
    }
}

?>
