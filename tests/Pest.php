<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

use craft\base\ElementInterface;
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
    $method = new ReflectionMethod(ReadTime::class, '_htmlToText');

    return $method->invoke(new ReadTime(), $text);
}

function countWords(mixed $value): int
{
    $method = new ReflectionMethod(ReadTime::class, '_countWords');

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
    $method = new ReflectionMethod(ReadTime::class, '_wordsToSeconds');

    return $method->invoke(readTimeServiceWithWpm($wordsPerMinute), $words);
}

function secondsForString(mixed $value, int $wordsPerMinute = 200): int
{
    return readTimeServiceWithWpm($wordsPerMinute)->secondsForString($value);
}

/*
| The field handlers hand nested block elements back to the service via
| `secondsForElement()`, which walks a real field layout and therefore needs a
| booted Craft app. For handler delegation tests we stub that walk with a
| fixed per-element cost and record which elements were handed over — the
| handler's own logic (value unwrapping, node/chunk filtering) is what runs.
*/

function readTimeServiceWithStubbedWalk(int $secondsPerElement = 60, int $wordsPerMinute = 200): ReadTime
{
    return new class($secondsPerElement, $wordsPerMinute) extends ReadTime {
        /**
         * @var ElementInterface[]
         */
        public array $walkedElements = [];

        public function __construct(
            public int $secondsPerElement,
            public int $fixedWordsPerMinute,
        ) {
            parent::__construct();
        }

        public function secondsForElement(ElementInterface $element): int
        {
            $this->walkedElements[] = $element;

            return $this->secondsPerElement;
        }

        protected function getWordsPerMinute(): int
        {
            return $this->fixedWordsPerMinute;
        }
    };
}

/*
| Handler `canHandle()` checks are plain `instanceof` tests against concrete
| field classes, so the tests only need instances — never initialised,
| configured fields. Reflection skips the constructor (and its `init()` chain,
| which may reach for a booted Craft app or a plugin singleton).
*/

function fieldInstance(string $class): object
{
    return (new ReflectionClass($class))->newInstanceWithoutConstructor();
}
