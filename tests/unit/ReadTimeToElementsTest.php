<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

declare(strict_types=1);

use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use jalendport\readtime\services\ReadTime;

/**
 * Unit coverage for the public {@see ReadTime::toElements()} normaliser.
 *
 * `toElements()` only depends on the `ElementInterface`/`ElementQueryInterface`
 * type contracts and `is_iterable()`, so no booted Craft application is needed.
 * Interface doubles are produced with PHPUnit's `createMock()` (a Pest
 * dependency — not an extra library) because the interfaces are too large to
 * hand-implement; only `ElementQueryInterface::all()` needs behaviour.
 */

it('returns an empty array for a non-iterable, non-query value', function () {
    $service = new ReadTime();

    expect($service->toElements('just a string'))->toBe([]);
    expect($service->toElements(42))->toBe([]);
    expect($service->toElements(null))->toBe([]);
});

it('returns the same elements for an array of ElementInterface', function () {
    $service = new ReadTime();

    $a = $this->createMock(ElementInterface::class);
    $b = $this->createMock(ElementInterface::class);

    expect($service->toElements([$a, $b]))->toBe([$a, $b]);
});

it('keeps only the ElementInterface items from a mixed iterable', function () {
    $service = new ReadTime();

    $a = $this->createMock(ElementInterface::class);
    $b = $this->createMock(ElementInterface::class);

    $mixed = [$a, 'not an element', 99, new stdClass(), $b];

    expect($service->toElements($mixed))->toBe([$a, $b]);
});

it('resolves an ElementQueryInterface via all() and returns its elements', function () {
    $service = new ReadTime();

    $a = $this->createMock(ElementInterface::class);
    $b = $this->createMock(ElementInterface::class);

    $query = $this->createMock(ElementQueryInterface::class);
    $query->method('all')->willReturn([$a, $b]);

    expect($service->toElements($query))->toBe([$a, $b]);
});

it('returns an empty array for an empty iterable', function () {
    $service = new ReadTime();

    expect($service->toElements([]))->toBe([]);
    expect($service->toElements(new ArrayIterator([])))->toBe([]);
});
