<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

declare(strict_types=1);

/**
 * Unit coverage for the read-time arithmetic: the private
 * {@see jalendport\readtime\services\ReadTime::wordsToSeconds()} and the public
 * {@see jalendport\readtime\services\ReadTime::secondsForString()}.
 *
 * Both are exercised through the `wordsToSeconds()` / `secondsForString()`
 * helpers in tests/Pest.php, which stub only the words-per-minute rate so the
 * real `floor($words / $wpm * 60)` arithmetic — and the HTML-stripping word
 * count behind `secondsForString()` — is what runs.
 */

it('returns zero seconds for zero words', function () {
    expect(wordsToSeconds(0, 200))->toBe(0);
});

it('returns 60 seconds for one full minute of words (200 @ 200 wpm)', function () {
    expect(wordsToSeconds(200, 200))->toBe(60);
});

it('floors a half minute of words to 30 seconds (100 @ 200 wpm)', function () {
    expect(wordsToSeconds(100, 200))->toBe(30);
});

it('returns 90 seconds for 300 words @ 200 wpm', function () {
    expect(wordsToSeconds(300, 200))->toBe(90);
});

it('counts plain text and converts it to seconds', function () {
    // 5 words @ 200 wpm => floor(5 / 200 * 60) = floor(1.5) = 1.
    expect(secondsForString('one two three four five', 200))->toBe(1);
});

it('strips HTML before counting, matching the equivalent plain text', function () {
    // 20 "word" tokens => floor(20 / 200 * 60) = 6 seconds, regardless of markup.
    $plain = str_repeat('word ', 20);
    $html = str_repeat('<span class="reading">word</span> ', 20);

    expect(secondsForString($plain, 200))->toBe(6);
    expect(secondsForString($html, 200))->toBe(6)
        ->and(secondsForString($html, 200))->toBe(secondsForString($plain, 200));
});
