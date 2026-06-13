# Reading Time Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased

### Added
- GraphQL support. A `readTime` field is now available on entry types in the GraphQL schema, returning a `ReadTime` object type with `seconds`, `minutes`, `hours`, and `humanReadable`. The resolver delegates to the existing read time service, so GraphQL and Twig report the same read time for a given entry, including content stored in Matrix, Neo, Vizy, and CKEditor fields. An optional `showSeconds` argument controls the `humanReadable` output ([#28](https://github.com/jalendport/craft-readtime/issues/28)).
- An `outputLocale` setting to control the language of the human-readable read-time string ([#20](https://github.com/jalendport/craft-readtime/issues/20)). Leave it empty to follow the current application language (unchanged default), set it to `site` to format each element in its own site's language (consistent across CP, front-end, and `resave/entries` on multi-site installs), or set a specific locale ID (e.g. `de-DE`) to force one language everywhere. Configurable in the plugin settings or via `config/read-time.php`. Only the human-readable string is affected; numeric values are unchanged.
- A `minimumReadTime` setting (in whole minutes). When greater than `0`, read times are rounded up to at least this many minutes, so sub-minute and empty content displays as e.g. "1 minute" instead of "less than a minute". Defaults to `0`, which preserves the existing behaviour. The floor is applied once when the service builds the `TimeModel`, so it is reflected consistently across `human()`, `__toString()`, `seconds()`/`minutes()`/`hours()`, the Twig function, and the Twig filter — and any other consumer of the returned `TimeModel`. Can be set in `config/read-time.php` ([#26](https://github.com/jalendport/craft-readtime/issues/26)).
- Added comprehensive Pest test coverage for TimeModel, ReadTime service, and Settings model.

### Fixed
- HTML markup is now stripped before content is word-counted, so read-time estimates are no longer inflated by tag names, attribute names, and attribute values such as URLs ([#14](https://github.com/jalendport/craft-readtime/issues/14)). The fix is applied centrally in the word counter, so both the `readTime` filter (raw strings) and `readTime()` (field-walking over rich-text fields like CKEditor) are corrected. Tags are replaced with whitespace (not removed) so words across tag boundaries stay separate, and HTML entities are decoded so `&nbsp;` counts as whitespace and `&amp;` isn't counted as a word. Plain (non-HTML) text counts are unchanged.
- The settings template referenced the wrong config filename (`reading-time.php`) and translation category (`reading-time`); both are now `read-time`, so the config-override warning points at the correct file.

## 3.0.0 - 2026-06-12

> Craft 5 release. The 2.x line remains the Craft 4 line.

### Added
- Craft 5 support. This release requires Craft CMS 5.0.0+ and PHP 8.2+ ([#29](https://github.com/jalendport/craft-readtime/issues/29)).
- [Vizy](https://verbb.io/craft-plugins/vizy) field support. Rich-text content is counted and Vizy blocks' nested fields are walked recursively.
- [CKEditor](https://github.com/craftcms/ckeditor) field support. The editor's rich-text content is counted, plus the content of any entries embedded inside the field.
- A `RegisterFieldHandlersEvent` so other plugins/modules can add read time support for further field types.
- An example config file at `config/read-time.php`.

### Changed
- **Full rewrite.** The counting and field-walking logic now lives in a dedicated `jalendport\readtime\services\ReadTime` service, registered as a plugin component; the Twig extension is a thin wrapper that delegates to it. The nested `if/else` field walking has been replaced with a single recursive routine that dispatches each field to a small, per-field-type handler, so adding a new field type is a localised change.
- Matrix content is now counted correctly on Craft 5. Matrix was "entrified" in Craft 5 — blocks are now `craft\elements\Entry` elements with their own field layouts — and their nested fields are walked accordingly. The previous code referenced the removed `craft\elements\MatrixBlock` class and silently miscounted entrified Matrix content.
- Neo, Vizy, and CKEditor are treated as optional, soft dependencies — the plugin loads and computes read time on sites that don't have them installed.
- Modernised to the current Craft 5 plugin spec and Yii guidelines: `declare(strict_types=1)`, typed properties and signatures, a `config()` method for component registration, and a settings config model.

### Removed
- **Super Table support.** Super Table does not exist for Craft 5, so it has been removed from the Craft 5 code path. Super Table support remains in the Craft 4 (2.x) line.
- The committed `composer.lock` file (a plugin must not commit a lock file); it is now ignored.

## 2.1.0 - 2026-06-12

### Added
- Neo field support. `readTime` now calculates read time for content stored in [Neo](https://github.com/spicywebau/craft-neo) fields, both when a Neo field is passed directly to the function/filter and when an entry containing a Neo field is passed ([#15](https://github.com/jalendport/craft-readtime/issues/15)). Original contribution by Matt Jones ([@icreatestuff](https://github.com/icreatestuff)) in [#21](https://github.com/jalendport/craft-readtime/pull/21). Neo is treated as an optional, soft dependency — the plugin continues to load and compute read time on sites without Neo installed.

## 2.0.0 - 2026-06-12

> Stable Craft 4 release.

### Fixed
- Calling `readTime` on an entry containing Matrix or Super Table fields no longer throws a Twig error on Craft 4. The deprecated `FieldLayout::getFields()` calls in the nested Matrix, Super Table, and Matrix-in-Super-Table loops are now `getCustomFields()` ([#25](https://github.com/jalendport/craft-readtime/issues/25)).
- Widened the exception handling in `readTimeFunction()` to also catch `craft\errors\InvalidFieldException` (thrown by `getFieldValue()`), which previously could surface as an uncaught Twig error.

## 2.0.0-beta.1 - 2023-03-07

### Added
- Initial Craft 4 release

## 1.6.0 - 2019-11-16

Transfer of ownership 👀

## 1.5.0 - 2019-02-21

### Added
- Matrix field support

## 1.4.0 - 2018-07-31

### Added
- Super Table support 🎉

## 1.3.0 - 2018-06-22

### Added
- Format the time as a `DateInterval`
- Output the total seconds, minutes and hours

### Changed
- Both the filter and function now return a `TimeModel`

## 1.2.1 - 2018-06-19

### Fixed
- `readTime()` function now includes matrix fields when calculating the read time

## 1.2.0 - 2018-06-18

### Added
- Calculate the read time of the whole entry based on it's field layout

## 1.1.0 - 2018-06-06

### Changed
- Plugin name changed to `Read Time`

## 1.0.0 - 2018-06-06

### Added
- Initial release
