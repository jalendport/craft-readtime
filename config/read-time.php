<?php
/**
 * Read Time config example.
 *
 * Copy this file to your project's `config/` folder and rename it to
 * `read-time.php` to override the plugin's settings. The file is fully
 * multi-environment aware.
 *
 * @see https://craftcms.com/docs/5.x/configure.html#config-files
 */

return [
    // The average reading speed, in words per minute.
    'wordsPerMinute' => 200,

    // Minimum read time, in whole minutes. When greater than 0, read times are
    // rounded up to at least this many minutes, so sub-minute content displays
    // as e.g. "1 minute" instead of "less than a minute". 0 keeps the default
    // behaviour.
    'minimumReadTime' => 0,
];
