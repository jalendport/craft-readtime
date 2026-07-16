<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

return [
    'Content’s site language' => 'Content’s site language',
    'Current language' => 'Current language',
    'Minimum Read Time' => 'Minimum Read Time',
    'Output Locale' => 'Output Locale',
    'Output Locale must be empty, “{site}”, or a valid locale ID.' => 'Output Locale must be empty, “{site}”, or a valid locale ID.',
    'Round read times up to at least this many minutes. Leave at 0 to keep the default “less than a minute” output for very short content.' => 'Round read times up to at least this many minutes. Leave at 0 to keep the default “less than a minute” output for very short content.',
    'This setting is being overridden by the {setting} setting in your {file} config file.' => 'This setting is being overridden by the {setting} setting in your {file} config file.',
    'The language used for the human-readable read-time string (e.g. “2 minutes” vs “2 Minuten”). “Current language” keeps the existing behaviour (the output follows the active control panel, site, or console language). “Content’s site language” formats each element in the language of the site it belongs to — recommended for multi-site installs. Choosing a specific language forces it everywhere. This only affects the human-readable string; numeric values are unaffected.' => 'The language used for the human-readable read-time string (e.g. “2 minutes” vs “2 Minuten”). “Current language” keeps the existing behaviour (the output follows the active control panel, site, or console language). “Content’s site language” formats each element in the language of the site it belongs to — recommended for multi-site installs. Choosing a specific language forces it everywhere. This only affects the human-readable string; numeric values are unaffected.',
    'This is used to calculate the average reading time. Average readers reach around 200 wpm.' => 'This is used to calculate the average reading time. Average readers reach around 200 wpm.',
    'Words per Minute' => 'Words per Minute',
];
