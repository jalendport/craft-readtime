# Reading Time Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

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
