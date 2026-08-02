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

// configuration.php
// Provides an interface for conveniently changing the blog's config.

// Only load the page if it's being requested via the index file.
if (!defined('INDEX')) exit;

if (!checkPerm(PERM_MANAGE_BLOG)) {
    $messages[] = error("You don't have permission to do this.");
    render_page("", array(), $title);
    exit();
}

$title = "Blog Configuration";

// Timezone stuff.
// Every timezone that PHP supports as of July 31st, 2026.
// Source: https://www.php.net/manual/en/timezones.php
$timezones = array('America/Adak', 'America/Anchorage', 'America/Anguilla', 'America/Antigua', 'America/Araguaina', 'America/Argentina/Buenos_Aires', 'America/Argentina/Catamarca', 'America/Argentina/Cordoba', 'America/Argentina/Jujuy', 'America/Argentina/La_Rioja', 'America/Argentina/Mendoza', 'America/Argentina/Rio_Gallegos', 'America/Argentina/Salta', 'America/Argentina/San_Juan', 'America/Argentina/San_Luis', 'America/Argentina/Tucuman', 'America/Argentina/Ushuaia', 'America/Aruba', 'America/Asuncion', 'America/Atikokan', 'America/Bahia', 'America/Bahia_Banderas', 'America/Barbados', 'America/Belem', 'America/Belize', 'America/Blanc-Sablon', 'America/Boa_Vista', 'America/Bogota', 'America/Boise', 'America/Cambridge_Bay', 'America/Campo_Grande', 'America/Cancun', 'America/Caracas', 'America/Cayenne', 'America/Cayman', 'America/Chicago', 'America/Chihuahua', 'America/Ciudad_Juarez', 'America/Costa_Rica', 'America/Coyhaique', 'America/Creston', 'America/Cuiaba', 'America/Curacao', 'America/Danmarkshavn', 'America/Dawson', 'America/Dawson_Creek', 'America/Denver', 'America/Detroit', 'America/Dominica', 'America/Edmonton', 'America/Eirunepe', 'America/El_Salvador', 'America/Fort_Nelson', 'America/Fortaleza', 'America/Glace_Bay', 'America/Goose_Bay', 'America/Grand_Turk', 'America/Grenada', 'America/Guadeloupe', 'America/Guatemala', 'America/Guayaquil', 'America/Guyana', 'America/Halifax', 'America/Havana', 'America/Hermosillo', 'America/Indiana/Indianapolis', 'America/Indiana/Knox', 'America/Indiana/Marengo', 'America/Indiana/Petersburg', 'America/Indiana/Tell_City', 'America/Indiana/Vevay', 'America/Indiana/Vincennes', 'America/Indiana/Winamac', 'America/Inuvik', 'America/Iqaluit', 'America/Jamaica', 'America/Juneau', 'America/Kentucky/Louisville', 'America/Kentucky/Monticello', 'America/Kralendijk', 'America/La_Paz', 'America/Lima', 'America/Los_Angeles', 'America/Lower_Princes', 'America/Maceio', 'America/Managua', 'America/Manaus', 'America/Marigot', 'America/Martinique', 'America/Matamoros', 'America/Mazatlan', 'America/Menominee', 'America/Merida', 'America/Metlakatla', 'America/Mexico_City', 'America/Miquelon', 'America/Moncton', 'America/Monterrey', 'America/Montevideo', 'America/Montserrat', 'America/Nassau', 'America/New_York', 'America/Nome', 'America/Noronha', 'America/North_Dakota/Beulah', 'America/North_Dakota/Center', 'America/North_Dakota/New_Salem', 'America/Nuuk', 'America/Ojinaga', 'America/Panama', 'America/Paramaribo', 'America/Phoenix', 'America/Port-au-Prince', 'America/Port_of_Spain', 'America/Porto_Velho', 'America/Puerto_Rico', 'America/Punta_Arenas', 'America/Rankin_Inlet', 'America/Recife', 'America/Regina', 'America/Resolute', 'America/Rio_Branco', 'America/Santarem', 'America/Santiago', 'America/Santo_Domingo', 'America/Sao_Paulo', 'America/Scoresbysund', 'America/Sitka', 'America/St_Barthelemy', 'America/St_Johns', 'America/St_Kitts', 'America/St_Lucia', 'America/St_Thomas', 'America/St_Vincent', 'America/Swift_Current', 'America/Tegucigalpa', 'America/Thule', 'America/Tijuana', 'America/Toronto', 'America/Tortola', 'America/Vancouver', 'America/Whitehorse', 'America/Winnipeg', 'America/Yakutat',
'Europe/Amsterdam', 'Europe/Andorra', 'Europe/Astrakhan', 'Europe/Athens', 'Europe/Belgrade', 'Europe/Berlin', 'Europe/Bratislava', 'Europe/Brussels', 'Europe/Bucharest', 'Europe/Budapest', 'Europe/Busingen', 'Europe/Chisinau', 'Europe/Copenhagen', 'Europe/Dublin', 'Europe/Gibraltar', 'Europe/Guernsey', 'Europe/Helsinki', 'Europe/Isle_of_Man', 'Europe/Istanbul', 'Europe/Jersey', 'Europe/Kaliningrad', 'Europe/Kirov', 'Europe/Kyiv', 'Europe/Lisbon', 'Europe/Ljubljana', 'Europe/London', 'Europe/Luxembourg', 'Europe/Madrid', 'Europe/Malta', 'Europe/Mariehamn', 'Europe/Minsk', 'Europe/Monaco', 'Europe/Moscow', 'Europe/Oslo', 'Europe/Paris', 'Europe/Podgorica', 'Europe/Prague', 'Europe/Riga', 'Europe/Rome', 'Europe/Samara', 'Europe/San_Marino', 'Europe/Sarajevo', 'Europe/Saratov', 'Europe/Simferopol', 'Europe/Skopje', 'Europe/Sofia', 'Europe/Stockholm', 'Europe/Tallinn', 'Europe/Tirane', 'Europe/Ulyanovsk', 'Europe/Vaduz', 'Europe/Vatican', 'Europe/Vienna', 'Europe/Vilnius', 'Europe/Volgograd', 'Europe/Warsaw', 'Europe/Zagreb', 'Europe/Zurich',
'Australia/Adelaide', 'Australia/Brisbane', 'Australia/Broken_Hill', 'Australia/Darwin', 'Australia/Eucla', 'Australia/Hobart', 'Australia/Lindeman', 'Australia/Lord_Howe', 'Australia/Melbourne', 'Australia/Perth', 'Australia/Sydney',
'Asia/Aden', 'Asia/Almaty', 'Asia/Amman', 'Asia/Anadyr', 'Asia/Aqtau', 'Asia/Aqtobe', 'Asia/Ashgabat', 'Asia/Atyrau', 'Asia/Baghdad', 'Asia/Bahrain', 'Asia/Baku', 'Asia/Bangkok', 'Asia/Barnaul', 'Asia/Beirut', 'Asia/Bishkek', 'Asia/Brunei', 'Asia/Chita', 'Asia/Colombo', 'Asia/Damascus', 'Asia/Dhaka', 'Asia/Dili', 'Asia/Dubai', 'Asia/Dushanbe', 'Asia/Famagusta', 'Asia/Gaza', 'Asia/Hebron', 'Asia/Ho_Chi_Minh', 'Asia/Hong_Kong', 'Asia/Hovd', 'Asia/Irkutsk', 'Asia/Jakarta', 'Asia/Jayapura', 'Asia/Jerusalem', 'Asia/Kabul', 'Asia/Kamchatka', 'Asia/Karachi', 'Asia/Kathmandu', 'Asia/Khandyga', 'Asia/Kolkata', 'Asia/Krasnoyarsk', 'Asia/Kuala_Lumpur', 'Asia/Kuching', 'Asia/Kuwait', 'Asia/Macau', 'Asia/Magadan', 'Asia/Makassar', 'Asia/Manila', 'Asia/Muscat', 'Asia/Nicosia', 'Asia/Novokuznetsk', 'Asia/Novosibirsk', 'Asia/Omsk', 'Asia/Oral', 'Asia/Phnom_Penh', 'Asia/Pontianak', 'Asia/Pyongyang', 'Asia/Qatar', 'Asia/Qostanay', 'Asia/Qyzylorda', 'Asia/Riyadh', 'Asia/Sakhalin', 'Asia/Samarkand', 'Asia/Seoul', 'Asia/Shanghai', 'Asia/Singapore', 'Asia/Srednekolymsk', 'Asia/Taipei', 'Asia/Tashkent', 'Asia/Tbilisi', 'Asia/Tehran', 'Asia/Thimphu', 'Asia/Tokyo', 'Asia/Tomsk', 'Asia/Ulaanbaatar', 'Asia/Urumqi', 'Asia/Ust-Nera', 'Asia/Vientiane', 'Asia/Vladivostok', 'Asia/Yakutsk', 'Asia/Yangon', 'Asia/Yekaterinburg', 'Asia/Yerevan',
'Pacific/Apia', 'Pacific/Auckland', 'Pacific/Bougainville', 'Pacific/Chatham', 'Pacific/Chuuk', 'Pacific/Easter', 'Pacific/Efate', 'Pacific/Fakaofo', 'Pacific/Fiji', 'Pacific/Funafuti', 'Pacific/Galapagos', 'Pacific/Gambier', 'Pacific/Guadalcanal', 'Pacific/Guam', 'Pacific/Honolulu', 'Pacific/Kanton', 'Pacific/Kiritimati', 'Pacific/Kosrae', 'Pacific/Kwajalein', 'Pacific/Majuro', 'Pacific/Marquesas', 'Pacific/Midway', 'Pacific/Nauru', 'Pacific/Niue', 'Pacific/Norfolk', 'Pacific/Noumea', 'Pacific/Pago_Pago', 'Pacific/Palau', 'Pacific/Pitcairn', 'Pacific/Pohnpei', 'Pacific/Port_Moresby', 'Pacific/Rarotonga', 'Pacific/Saipan', 'Pacific/Tahiti', 'Pacific/Tarawa', 'Pacific/Tongatapu', 'Pacific/Wake', 'Pacific/Wallis',
'Africa/Abidjan', 'Africa/Accra', 'Africa/Addis_Ababa', 'Africa/Algiers', 'Africa/Asmara', 'Africa/Bamako', 'Africa/Bangui', 'Africa/Banjul', 'Africa/Bissau', 'Africa/Blantyre', 'Africa/Brazzaville', 'Africa/Bujumbura', 'Africa/Cairo', 'Africa/Casablanca', 'Africa/Ceuta', 'Africa/Conakry', 'Africa/Dakar', 'Africa/Dar_es_Salaam', 'Africa/Djibouti', 'Africa/Douala', 'Africa/El_Aaiun', 'Africa/Freetown', 'Africa/Gaborone', 'Africa/Harare', 'Africa/Johannesburg', 'Africa/Juba', 'Africa/Kampala', 'Africa/Khartoum', 'Africa/Kigali', 'Africa/Kinshasa', 'Africa/Lagos', 'Africa/Libreville', 'Africa/Lome', 'Africa/Luanda', 'Africa/Lubumbashi', 'Africa/Lusaka', 'Africa/Malabo', 'Africa/Maputo', 'Africa/Maseru', 'Africa/Mbabane', 'Africa/Mogadishu', 'Africa/Monrovia', 'Africa/Nairobi', 'Africa/Ndjamena', 'Africa/Niamey', 'Africa/Nouakchott', 'Africa/Ouagadougou', 'Africa/Porto-Novo', 'Africa/Sao_Tome', 'Africa/Tripoli', 'Africa/Tunis', 'Africa/Windhoek',
'Atlantic/Azores', 'Atlantic/Bermuda', 'Atlantic/Canary', 'Atlantic/Cape_Verde', 'Atlantic/Faroe', 'Atlantic/Madeira', 'Atlantic/Reykjavik', 'Atlantic/South_Georgia', 'Atlantic/St_Helena', 'Atlantic/Stanley',
'Indian/Antananarivo', 'Indian/Chagos', 'Indian/Christmas', 'Indian/Cocos', 'Indian/Comoro', 'Indian/Kerguelen', 'Indian/Mahe', 'Indian/Maldives', 'Indian/Mauritius', 'Indian/Mayotte', 'Indian/Reunion',
'Arctic/Longyearbyen',
'Antarctica/Casey', 'Antarctica/Davis', 'Antarctica/DumontDUrville', 'Antarctica/Macquarie', 'Antarctica/Mawson', 'Antarctica/McMurdo', 'Antarctica/Palmer', 'Antarctica/Rothera', 'Antarctica/Syowa', 'Antarctica/Troll', 'Antarctica/Vostok');
$timezonesHTML = "";
// Use the user-supplied timezone if it's valid, otherwise default to the config.
if (isset($_POST["timezone"]) and in_array($_POST["timezone"], $timezones)) {
    $currentTimezone = $_POST["timezone"];
}
else {
    $currentTimezone = $config["timezone"];
}
foreach ($timezones as $t) {
    if ($t == $currentTimezone) {
        $s = " selected";
    }
    else {
        $s = "";
    }
    $timezonesHTML .= "<option value='$t'$s>$t</option>";
}

// Language stuff.
$languageHTML = "";
// Use the user-supplied language if it's valid, otherwise default to the config.
if (isset($_POST["clanguage"]) and in_array($_POST["clanguage"], $languages)) {
    $currentLanguage = $_POST["clanguage"];
}
else {
    $currentLanguage = $language;
}
foreach ($languages as $l) {
    if ($l == $currentLanguage) {
        $s = " selected";
    }
    else {
        $s = "";
    }
    $languageHTML .= "<option value='$l'$s>$l</option>";
}

// Theme stuff.
$themeHTML = "";
// Use the user-supplied language if it's valid, otherwise default to the config.
if (isset($_POST["theme"]) and in_array($_POST["theme"], $themes)) {
    $currentTheme = $_POST["theme"];
}
else {
    $currentTheme = $config["theme"];
}
foreach ($themes as $t) {
    if ($t == $currentTheme) {
        $s = " selected";
    }
    else {
        $s = "";
    }
    $themeHTML .= "<option value='$t'$s>$t</option>";
}

// Registration mode stuff.
$registerHTML = "";
$modes = array("approval", "open");
// Use the user-supplied mode if it's valid, otherwise default to the config.
if (isset($_POST["registrationMode"]) and in_array($_POST["registrationMode"], $modes)) {
    $currentMode = $_POST["registrationMode"];
}
else {
    $currentMode = $config["registrationMode"];
}
foreach ($modes as $m) {
    if ($m == $currentMode) {
        $s = " selected";
    }
    else {
        $s = "";
    }
    $registerHTML .= "<option value='$m'$s>$m</option>";
}

// Handle requests.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // If the CSRF token is sent and valid.
    if ((isset($_POST["csrf_token"])) and ($_POST["csrf_token"] === $_SESSION["csrf_token"])) {
        // Generate a new token.
        generateCSRFToken();
        
        $errors = array();
        $changes = 0;
        
        if (isset($_POST["ctitle"])) {
            if (strlen($_POST["ctitle"]) < 1) {
                $errors[] = "Title cannot be blank.";
            }
            elseif (strlen($_POST["ctitle"]) > 32) {
                $errors[] = "Title cannot be longer than 32 characters.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["ctitle"] != $config["title"]) {
                $config["title"] = $_POST["ctitle"];
                $changes++;
            }
        }
        if (isset($_POST["cdescription"])) {
            if (strlen($_POST["cdescription"]) < 1) {
                $errors[] = "Description cannot be blank.";
            }
            elseif (strlen($_POST["cdescription"]) > 128) {
                $errors[] = "Description cannot be longer than 128 characters.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["cdescription"] != $config["description"]) {
                $config["description"] = $_POST["cdescription"];
                $changes++;
            }
        }
        if (isset($_POST["cfooter"])) {
            if (strlen($_POST["cfooter"]) < 1) {
                $errors[] = "Footer cannot be blank.";
            }
            elseif (strlen($_POST["cfooter"]) > 2048) {
                $errors[] = "Footer cannot be longer than 2048 characters.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["cfooter"] != $config["footer"]) {
                $config["footer"] = $_POST["cfooter"];
                $changes++;
            }
        }
        if (isset($_POST["timezone"])) {
            if (!in_array($_POST["timezone"], $timezones)) {
                $errors[] = "Invalid timezone.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["timezone"] != $config["timezone"]) {
                $config["timezone"] = $_POST["timezone"];
                $changes++;
            }
        }
        if (isset($_POST["clanguage"])) {
            if (!in_array($_POST["clanguage"], $languages)) {
                $errors[] = "Invalid language.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["clanguage"] != $config["language"]) {
                $config["language"] = $_POST["clanguage"];
                $language = $_POST["clanguage"];
                updateLang();
                $changes++;
            }
        }
        if (isset($_POST["theme"])) {
            if (!in_array($_POST["theme"], $themes)) {
                $errors[] = "Invalid theme.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["theme"] != $config["theme"]) {
                $config["theme"] = $_POST["theme"];
                $changes++;
            }
        }
        if (($_POST["registration"] ?? "") == "on") {
            $tz = true;
        }
        else {
            $tz = false;
        }
        // Only write to the config if the value is actually being changed.
        if ($tz != $config["allowRegistration"]) {
            $config["allowRegistration"] = $tz;
            $changes++;
        }
        if (isset($_POST["registrationMode"])) {
            if (!in_array($_POST["registrationMode"], $modes)) {
                $errors[] = "Invalid registration mode.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($_POST["registrationMode"] != $config["registrationMode"]) {
                $config["registrationMode"] = $_POST["registrationMode"];
                $changes++;
            }
        }
        if (($_POST["comments"] ?? "") == "on") {
            $ec = true;
        }
        else {
            $ec = false;
        }
        // Only write to the config if the value is actually being changed.
        if ($ec != $config["enableComments"]) {
            $config["enableComments"] = $ec;
            $changes++;
        }
        if (isset($_POST["logins"])) {
            $logins = (int)$_POST["logins"];
            if ($logins < 1) {
                $errors[] = "Logins per hour cannot be less than 1.";
            }
            elseif ($logins > 32767) {
                $errors[] = "Logins per hour cannot be greater than 32,767.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($logins != $config["loginsPerHour"]) {
                $config["loginsPerHour"] = $logins;
                $changes++;
            }
        }
        if (isset($_POST["accounts"])) {
            $accounts = (int)$_POST["accounts"];
            if ($accounts < 1) {
                $errors[] = "Accounts per IP cannot be less than 1.";
            }
            elseif ($accounts > 32767) {
                $errors[] = "Accounts per IP cannot be greater than 32,767.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($accounts != $config["accountsPerIP"]) {
                $config["accountsPerIP"] = $accounts;
                $changes++;
            }
        }
        if (isset($_POST["accountcooldown"])) {
            $accountcooldown = (int)$_POST["accountcooldown"];
            if ($accountcooldown < 1) {
                $errors[] = "Account cooldown cannot be less than 1.";
            }
            elseif ($accountcooldown > 32767) {
                $errors[] = "Account cooldown cannot be greater than 32,767.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($accountcooldown != $config["accountCooldown"]) {
                $config["accountCooldown"] = $accountcooldown;
                $changes++;
            }
        }
        if (isset($_POST["postdelay"])) {
            $postdelay = (int)$_POST["postdelay"];
            if ($postdelay < 1) {
                $errors[] = "Post delay cannot be less than 1.";
            }
            elseif ($postdelay > 32767) {
                $errors[] = "Post delay cannot be greater than 32,767.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($postdelay != $config["postDelay"]) {
                $config["postDelay"] = $postdelay;
                $changes++;
            }
        }
        if (isset($_POST["editdelay"])) {
            $editdelay = (int)$_POST["editdelay"];
            if ($editdelay < 1) {
                $errors[] = "Edit delay cannot be less than 1.";
            }
            elseif ($editdelay > 32767) {
                $errors[] = "Edit delay cannot be greater than 32,767.";
            }
            // Only write to the config if the value is actually being changed.
            elseif ($editdelay != $config["editDelay"]) {
                $config["editDelay"] = $editdelay;
                $changes++;
            }
        }
        
        // If there are errors, display them.
        if (count($errors) > 0) {
            foreach ($errors as $e) {
                $messages[] = error($e);
            }
        }
        // Display a message if we changed the config.
        if ($changes > 0) {
            flushConfig();
            $messages[] = success("Successfully updated the blog configuration.");
        }
        else {
            $messages[] = success("Successfully did nothing.");
        }
    }
}

// Display the config form.
$configvars = array("token" => $_SESSION["csrf_token"],
"title" => $_POST["ctitle"] ?? $config["title"],
"description" => $_POST["cdescription"] ?? $config["description"],
"footer" => $_POST["cfooter"] ?? $config["footer"],
"timezone" => $timezonesHTML,
"language" => $languageHTML,
"theme" => $themeHTML,
"allowregistration" => $config["allowRegistration"] ? " checked" : "",
"registrationmode" => $registerHTML,
"comments" => $config["enableComments"] ? " checked" : "",
"loginsperhour" => $_POST["logins"] ?? $config["loginsPerHour"],
"accountsperip" => $_POST["accounts"] ?? $config["accountsPerIP"],
"accountcooldown" => $_POST["accountcooldown"] ?? $config["accountCooldown"],
"postdelay" => $_POST["postdelay"] ?? $config["postDelay"],
"editdelay" => $_POST["editdelay"] ?? $config["editDelay"]);

render_page("panel/configuration.html", $configvars, $title);

?>
