<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

use jalendport\readtime\models\Settings;

/**
 * Unit coverage for {@see Settings} default attribute values.
 *
 * Only default-value reads are covered — `validateOutputLocale()` needs
 * `Craft::$app->getI18n()` and is intentionally left out of these app-free unit
 * tests.
 */

it('defaults wordsPerMinute to 200', function() {
    expect((new Settings())->wordsPerMinute)->toBe(200);
});

it('defaults minimumReadTime to 0', function() {
    expect((new Settings())->minimumReadTime)->toBe(0);
});

it('defaults outputLocale to null', function() {
    expect((new Settings())->outputLocale)->toBeNull();
});

it('exposes the site keyword constant as "site"', function() {
    expect(Settings::OUTPUT_LOCALE_SITE)->toBe('site');
});
