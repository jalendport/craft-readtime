<h1><img src="src/icon.svg" alt="icon" width="25" height="25" hspace="5">Read Time</h1>

_Calculate the estimated read time for content._

## Installation

### Requirements

This plugin requires **Craft CMS 5.0.0 or later** and **PHP 8.2 or later**.

> Using Craft 4? Use the [2.x](https://github.com/jalendport/craft-readtime/tree/craft4) line, which is the Craft 4 release of this plugin.

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

The `readTime()` function returns a [TimeModel](#timemodel) for the whole entry (based on its field layout) or for a block field passed directly.

Seconds are included by default, but can be disabled by passing `false` as a second parameter — this only affects the human time format.

```twig
{{ readTime(entry) }}
{{ readTime(entry.matrixField.all()) }}

{{ readTime(entry, false) }}
{{ readTime(entry.matrixField.all(), false) }}
```

### Using GraphQL

A `readTime` field is available on entry types in Craft's [GraphQL](https://craftcms.com/docs/5.x/development/graphql.html) API. It returns a `ReadTime` object type mirroring the [TimeModel](#timemodel), so GraphQL and Twig report the same read time for a given entry.

```graphql
{
  entries(section: "blog", limit: 1) {
    title
    readTime {
      seconds
      minutes
      hours
      humanReadable
    }
  }
}
```

```json
{
  "data": {
    "entries": [
      {
        "title": "Hello World",
        "readTime": {
          "seconds": 160,
          "minutes": 2,
          "hours": 0,
          "humanReadable": "2 minutes, 40 seconds"
        }
      }
    ]
  }
}
```

| Field | Returns |
| --- | --- |
| `seconds` | The total number of seconds. |
| `minutes` | The total number of whole minutes. |
| `hours` | The total number of whole hours. |
| `humanReadable` | The human-readable duration. |

Seconds are included in `humanReadable` by default. Pass `showSeconds: false` to the `readTime` field to omit them — this only affects `humanReadable`:

```graphql
{
  entries(section: "blog", limit: 1) {
    readTime(showSeconds: false) {
      humanReadable
    }
  }
}
```

The field resolves on demand from the same read time service used by the Twig function and filter, so it counts all supported field types (Matrix, Neo, Vizy, CKEditor). Read time is computed by walking an entry's field layout, so selecting `readTime` across a large entry query computes it per entry — request it only where you need it.

### Supported Field Types

When you pass an entry to `readTime()`, the plugin walks its field layout and counts the content of each field, recursing into nested-block fields:

| Field type | Notes |
| --- | --- |
| Plain text / rich text (e.g. Redactor, Plain Text) | Counted directly. |
| **Matrix** (native) | On Craft 5, Matrix blocks are entrified — each block is an `Entry` element. Their nested fields are walked recursively. |
| **Neo** ([`spicyweb/craft-neo`](https://github.com/spicywebau/craft-neo)) | Each Neo block's fields are walked recursively. |
| **Vizy** ([`verbb/vizy`](https://verbb.io/craft-plugins/vizy)) | Rich-text content is counted and Vizy blocks' nested fields are walked recursively. |
| **CKEditor** ([`craft/ckeditor`](https://github.com/craftcms/ckeditor)) | The editor's rich-text content is counted, plus the content of any entries embedded inside the field. |

Neo, Vizy, and CKEditor are treated as optional, soft dependencies — the plugin loads and computes read time fine on sites that don't have them installed.

> **Super Table is no longer supported.** It does not exist for Craft 5, so it has been removed from the Craft 5 code path. Super Table support remains in the Craft 4 (2.x) line.

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

### Settings

| Setting | Default | Description |
| --- | --- | --- |
| `wordsPerMinute` | `200` | The average reading speed, in words per minute, used to calculate the read time. |
| `outputLocale` | _(empty)_ | The language used for the human-readable string. Empty follows the current application language; `site` formats each element in its own site's language; a locale ID (e.g. `de-DE`) forces one language everywhere. See [Output Locale](#output-locale) below. Only the human-readable string is affected. |
| `minimumReadTime` | `0` | Minimum read time, in whole minutes. When greater than `0`, read times are rounded **up** to at least this many minutes, so sub-minute (and empty) content displays as e.g. "1 minute" instead of "less than a minute". `0` keeps the default behaviour. |

The minimum is applied at the source, so it is reflected consistently everywhere — `time.human`, `time.seconds`, `time.minutes`, `time.hours`, the `readTime()` function, and the `|readTime` filter all agree, as does any other consumer of the returned `TimeModel`.

### Output Locale

By default the human-readable string follows the **current application language**, which depends on context: a logged-in user's preferred language in the Control Panel, the requested site's language on the front end, and the system/default language in console commands (e.g. `php craft resave/entries`). That means content pre-parsed into a custom field can end up in a different language depending on where it was saved.

The `outputLocale` setting lets you pin the language of the human-readable string so it stays consistent. It is a single setting with three modes:

| Value | Behaviour |
| --- | --- |
| _(empty)_ / `null` | **Current language** (default). The output follows the active application language — the existing behaviour, unchanged. |
| `site` | **Content's site language.** The output is formatted in the language of the site the content belongs to. Recommended for multi-site installs: each site's content formats in its own language, including under `resave/entries`. On the `|readTime` filter (which has no element), this falls back to the current site's language. |
| a locale ID, e.g. `de-DE` | **Force that locale everywhere.** Useful for single-site installs, or anyone who wants uniform output across all sites. |

This affects **only** the human-readable string (`time` / `time.human`, and the GraphQL human-readable field). Numeric values (`time.seconds`, `time.minutes`, `time.hours`) are locale-independent and never change.

In the Control Panel the dropdown lists _Current language_, _Content's site language_, and your configured site languages. Via the config file you can also set any locale ID Craft recognises, even if it isn't one of your site languages.

### Overriding Plugin Settings

These settings can be changed in the plugin settings in the Control Panel, or overridden with a config file.

If you create a [config file](https://craftcms.com/docs/5.x/configure.html#config-files) in your `config` folder called `read-time.php`, you can override the plugin's settings in the Control Panel. Since that config file is fully [multi-environment](https://craftcms.com/docs/5.x/configure.html#multi-environment-configs) aware, this is a handy way to have different settings across multiple environments. An example is included at [`config/read-time.php`](config/read-time.php).

```php
<?php

return [
    'wordsPerMinute' => 200,
    'outputLocale' => null, // null | 'site' | a locale ID such as 'de-DE'
    'minimumReadTime' => 0,
];
```

## Found a Bug? Need Support?

Please open an [issue](https://github.com/jalendport/craft-readtime/issues) describing what's going wrong.
