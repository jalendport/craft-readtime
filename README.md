<img src="src/icon.svg" alt="icon" width="100" height="100">

# Read Time plugin for Craft CMS 3

Calculate the estimated read time for content.

## Installation

#### Requirements

This plugin requires Craft CMS 3.0.0, or later.

#### Plugin Store

Log into your control panel and click on 'Plugin Store'. Search for 'Read Time'.

#### Composer

1. Open your terminal and go to your Craft project:

```bash
cd /path/to/project
```

2. Then tell Composer to load the plugin:

```bash
composer require jalendport/craft-readtime
```

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Read Time.

## Configuration

The average user read speed is set at 200 words per minute by default, this can be changed in the plugin settings.

## Using the Filter

The `|readTime` filter returns a [TimeModel](#timemodel) of how long it takes the average user to read the provided content. The value provided can be a string or an array of values.

Seconds are included by default, but can be disabled by using `|readTime(false)` - this only affects the human time format.

#### Examples

```twig
{{ string|readTime }}

Returns: 30 seconds
```

```twig
{{ richTextField|readTime }}

Returns: 2 minutes, 40 seconds
```

```twig
{{ richTextField|readTime(false) }}

Returns: 3 minutes
```

## Using the Function

The `readTime()` function returns a [TimeModel](#timemodel) for matrix fields or the whole entry based on it's field layout.

Seconds are included by default, but can be disabled by adding a second parameter of `false` - this only affects the human time format.

#### Examples

```twig
{{ readTime(entry) }} or {{ readTime(entry.matrixField.all()) }}
```

```twig
{{ readTime(entry, false) }} or {{ readTime(entry.matrixField.all(), false) }}
```

## TimeModel

Whenever you're dealing with the read time in your template, you're actually working with a TimeModel object.

### Simple Output

Outputting a TimeModel object without attaching a property or method will return the time in the form of a human time duration.

```
{{ string|readTime }}

{{ readTime(entry) }}

{{ readTime(entry.matrixField.all()) }}
```

### Properties

#### `human`

The human time duration.

#### `interval(format)`

A `DateInterval` object. You'll need to set the [format](http://php.net/manual/en/dateinterval.format.php) as a parameter:

```twig
{% set time = readTime(entry) %}

{{ time.interval('%h hours, %i minutes, %s seconds') }}
```

#### `seconds`

The total number of seconds.

#### `minutes`

The total number of minutes.

#### `hours`

The total number of hours.

## Overriding Plugin Settings

If you create a [config file](https://docs.craftcms.com/v3/configuration.html) in your `config` folder called `read-time.php`, you can override the plugin’s settings in the Control Panel. Since that config file is fully [multi-environment](https://docs.craftcms.com/v3/configuration.html) aware, this is a handy way to have different settings across multiple environments.

Here’s what that config file might look like along with a list of all of the possible values you can override.

```php
<?php

return [
    'wordsPerMinute' => 200
];
```

## Roadmap

Some things to do, and ideas for potential features:

Brought to you by [Luke Youell](https://github.com/lukeyouell)
