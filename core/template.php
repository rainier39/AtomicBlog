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

// template.php
// Custom PHP template engine.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

// Render a given template.
function render_template($filename, $variables, $echo=true) {
    global $lang;

    if (!is_file("templates/{$filename}")) {
        return false;
    }
    
    $template = file_get_contents("templates/{$filename}");
    
    if ($template === false) {
        return false;
    }
    
    // Don't display HTML comments.
    $template = preg_replace("/<!-- .+ -->/", "", $template);
    
    // Template variables.
    foreach ($variables as $k=>$v) {
        $template = preg_replace("/{{{{ ({$k}) }}}}/", format($v), $template);
        $template = preg_replace("/{{{ ({$k}) }}}/", htmlspecialchars($v), $template);
        $template = preg_replace("/{{ ({$k}) }}/", $v, $template);
    }
    foreach ($lang as $k=>$v) {
        // Double square brackets for language strings.
        $template = preg_replace("/\[\[ ({$k}) \]\]/", $v, $template);
    }
    
    // Remove any extra whitespace.
    $template = trim($template);
    
    // We may only want to return the result as a string if this is something like a post tile.
    // I.E. not a final result, full page, whatever else.
    if ($echo) {
        echo($template);
        return true;
    }
    else {
        return $template;
    }
}

?>
