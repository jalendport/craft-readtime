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
