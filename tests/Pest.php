<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

declare(strict_types=1);

use jalendport\readtime\services\ReadTime;

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
|
| The counting helpers on the ReadTime service are private, so they're
| exercised through reflection. `htmlToText()` is pure string-cleaning (no
| Craft application needed) and `countWords()` only delegates to
| `craft\helpers\StringHelper`, so neither requires a booted Craft instance.
*/

function htmlToText(string $text): string
{
    $method = new ReflectionMethod(ReadTime::class, 'htmlToText');
    $method->setAccessible(true);

    return $method->invoke(new ReadTime(), $text);
}

function countWords(mixed $value): int
{
    $method = new ReflectionMethod(ReadTime::class, 'countWords');
    $method->setAccessible(true);

    return $method->invoke(new ReadTime(), $value);
}

/*
| `wordsToSeconds()` and the public `secondsForString()` both read the
| configured words-per-minute through `ReadTime::getWordsPerMinute()`, which
| normally reaches the plugin singleton (and therefore a booted Craft app). To
| keep these unit tests app-free we use a tiny subclass that overrides that one
| seam with a fixed rate — the "subclass that overrides getWordsPerMinute()"
| approach. The real `wordsToSeconds()`/`secondsForString()` arithmetic is what
| runs; only the rate lookup is stubbed.
*/

function readTimeServiceWithWpm(int $wordsPerMinute): ReadTime
{
    return new class($wordsPerMinute) extends ReadTime {
        public function __construct(public int $fixedWordsPerMinute)
        {
            parent::__construct();
        }

        protected function getWordsPerMinute(): int
        {
            return $this->fixedWordsPerMinute;
        }
    };
}

function wordsToSeconds(int $words, int $wordsPerMinute = 200): int
{
    $method = new ReflectionMethod(ReadTime::class, 'wordsToSeconds');
    $method->setAccessible(true);

    return $method->invoke(readTimeServiceWithWpm($wordsPerMinute), $words);
}

function secondsForString(mixed $value, int $wordsPerMinute = 200): int
{
    return readTimeServiceWithWpm($wordsPerMinute)->secondsForString($value);
}
