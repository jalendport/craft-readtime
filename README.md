<img src="src/icon.svg" alt="icon" width="100" height="100">

# Reading Time plugin for Craft CMS 3

Calculate the estimated reading time for content.

## Installation

#### Requirements

This plugin requires Craft CMS 3.0.0, or later.

#### Plugin Store

Log into your control panel and click on 'Plugin Store'. Search for 'Reading Time'.

#### Composer

1. Open your terminal and go to your Craft project:

```bash
cd /path/to/project
```

2. Then tell Composer to load the plugin:

```bash
composer require lukeyouell/craft-readingtime
```

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Reading Time.

## Configuration

The average user reading speed is set at 200 words per minute by default, this can be changed in the plugin settings.

## Using the Filter

The `|readingTime` filter returns a human time duration of how long it takes the average user to read the provided content. The value provided can be a string or an array of values.

Seconds are included by default, but can be disabled by using `|readingTime(false)`

#### Example

```twig
{{ 'Sed scelerisque tellus vel faucibus luctus.'|readingTime }}

Returns: 1 second
```

Brought to you by [Luke Youell](https://github.com/lukeyouell)
