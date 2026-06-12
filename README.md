<h1><img src="src/icon.svg" alt="icon" width="25" height="25" hspace="5">Read Time</h1>

_Calculate the estimated read time for content._

## Installation

### Requirements

This plugin requires Craft CMS 4.0.0 or later.

### Plugin Store

Log into your control panel and click on 'Plugin Store'. Search for 'Read Time'.

### Composer

1. Open your terminal and go to your Craft project:

```bash
cd /path/to/project
```

2. Then tell Composer to load the plugin:

```bash
composer require jalendport/craft-readtime
```

3. In the Control Panel, go to Settings → Plugins and click the "Install" button for Read Time.

## Usage

### Using the Filter

The `|readTime` filter returns a [TimeModel](#timemodel) of how long it takes the average user to read the provided content. The value provided can be a string or an array of values.

Seconds are included by default, but can be disabled by using `|readTime(false)` — this only affects the human time format.

```twig
{{ string|readTime }}
{# Returns: 30 seconds #}

{{ richTextField|readTime }}
{# Returns: 2 minutes, 40 seconds #}

{{ richTextField|readTime(false) }}
{# Returns: 3 minutes #}
```

### Using the Function

The `readTime()` function returns a [TimeModel](#timemodel) for Matrix fields or the whole entry based on its field layout.

Seconds are included by default, but can be disabled by passing `false` as a second parameter — this only affects the human time format.

```twig
{{ readTime(entry) }}
{{ readTime(entry.matrixField.all()) }}

{{ readTime(entry, false) }}
{{ readTime(entry.matrixField.all(), false) }}
```

### TimeModel

Both the filter and the function return a `TimeModel`. Output it directly for a human-readable duration, or read one of its properties for a specific value:

```twig
{% set time = readTime(entry) %}

{{ time }}          {# 2 minutes, 40 seconds #}
{{ time.human }}    {# 2 minutes, 40 seconds #}
{{ time.seconds }}  {# 160 #}
{{ time.minutes }}  {# 2 #}
{{ time.hours }}    {# 0 #}
```

| Property | Returns |
| --- | --- |
| `time` / `time.human` | The human-readable duration. |
| `time.seconds` | The total number of seconds. |
| `time.minutes` | The total number of whole minutes. |
| `time.hours` | The total number of whole hours. |

You can also format the duration as a [`DateInterval`](https://www.php.net/manual/en/dateinterval.format.php) by passing a format string to `interval()`:

```twig
{{ time.interval('%h hours, %i minutes, %s seconds') }}  {# 0 hours, 2 minutes, 40 seconds #}
```

### Overriding Plugin Settings

The average user read speed is set at 200 words per minute by default. This can be changed in the plugin settings, or overridden with a config file.

If you create a [config file](https://craftcms.com/docs/4.x/config/) in your `config` folder called `read-time.php`, you can override the plugin's settings in the Control Panel. Since that config file is fully [multi-environment](https://craftcms.com/docs/4.x/config/#multi-environment-configs) aware, this is a handy way to have different settings across multiple environments.

```php
<?php

return [
    'wordsPerMinute' => 200,
];
```

## Found a Bug? Need Support?

Please open an [issue](https://github.com/jalendport/craft-readtime/issues) describing what's going wrong.
