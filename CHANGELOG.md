# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## Unreleased

### Added
- Added a per-plugin log file at `storage/logs/read-time-*.log`
- Added translation support for every control panel string

### Changed
- Changed the plugin to require `jalendport/craft-base`
- Changed the example config file's location to `src/config.php`

### Deprecated
- Deprecated `ReadTime::getReadTime()`; use `ReadTime::$plugin->readTime` instead

## 3.1.0 - 2026-06-12

### Added
- Added GraphQL support, with a `readTime` field on entry types ([#28](https://github.com/jalendport/craft-readtime/issues/28))
- Added an `outputLocale` setting to control the human-readable string's language ([#20](https://github.com/jalendport/craft-readtime/issues/20))
- Added a `minimumReadTime` setting to round read times up to a floor ([#26](https://github.com/jalendport/craft-readtime/issues/26))

### Fixed
- Fixed a bug where HTML markup inflated read-time estimates ([#14](https://github.com/jalendport/craft-readtime/issues/14))
- Fixed a bug where the settings page pointed at the wrong config filename

## 3.0.0 - 2026-06-12

> Craft 5 release. The 2.x line remains the Craft 4 line.

### Added
- Added Craft 5 support, requiring Craft CMS 5.0.0+ and PHP 8.2+ ([#29](https://github.com/jalendport/craft-readtime/issues/29))
- Added [Vizy](https://verbb.io/craft-plugins/vizy) field support
- Added [CKEditor](https://github.com/craftcms/ckeditor) field support, including nested entries
- Added a `RegisterFieldHandlersEvent` for registering further field types
- Added an example config file

### Changed
- Changed the counting and field-walking logic to live in a `ReadTime` service, with per-field-type handlers
- Changed Matrix counting to walk entrified blocks, fixing silently miscounted Matrix content on Craft 5
- Changed Neo, Vizy, and CKEditor to be optional soft dependencies

### Removed
- Removed Super Table support, which does not exist for Craft 5 (it remains in the 2.x line)

## 2.1.0 - 2026-06-12

### Added
- Added Neo field support ([#15](https://github.com/jalendport/craft-readtime/issues/15), [#21](https://github.com/jalendport/craft-readtime/pull/21))

## 2.0.0 - 2026-06-12

> Stable Craft 4 release.

### Fixed
- Fixed a bug where `readTime` threw a Twig error on entries with Matrix or Super Table fields ([#25](https://github.com/jalendport/craft-readtime/issues/25))
- Fixed an error that could occur when `getFieldValue()` threw an `InvalidFieldException`

## 2.0.0-beta.1 - 2023-03-07

### Added
- Added the initial Craft 4 release

## 1.6.0 - 2019-11-16

### Changed
- Changed ownership of the plugin 👀

## 1.5.0 - 2019-02-21

### Added
- Added Matrix field support

## 1.4.0 - 2018-07-31

### Added
- Added Super Table support 🎉

## 1.3.0 - 2018-06-22

### Added
- Added `DateInterval` formatting for the read time
- Added total seconds, minutes, and hours outputs

### Changed
- Changed the filter and function to both return a `TimeModel`

## 1.2.1 - 2018-06-19

### Fixed
- Fixed a bug where the `readTime()` function ignored Matrix fields

## 1.2.0 - 2018-06-18

### Added
- Added read time calculation for a whole entry, based on its field layout

## 1.1.0 - 2018-06-06

### Changed
- Changed the plugin name to Read Time

## 1.0.0 - 2018-06-06

### Added
- Added the initial release
