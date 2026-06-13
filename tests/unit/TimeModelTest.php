<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

declare(strict_types=1);

use jalendport\readtime\models\TimeModel;

/**
 * Unit coverage for {@see TimeModel}'s pure-arithmetic accessors.
 *
 * `seconds()`, `minutes()`, and `hours()` only divide and floor the `seconds`
 * attribute, so they need no booted Craft application. The locale-aware
 * `human()`/`interval()`/`__toString()` methods (which require `Craft::$app`)
 * are intentionally not covered here.
 */

function timeModel(int $seconds): TimeModel
{
    return new TimeModel(['seconds' => $seconds]);
}

it('reports zero across the board for empty content', function () {
    $model = timeModel(0);

    expect($model->seconds())->toBe(0);
    expect($model->minutes())->toBe(0);
    expect($model->hours())->toBe(0);
});

it('floors sub-minute durations to zero minutes', function () {
    $model = timeModel(59);

    expect($model->seconds())->toBe(59);
    expect($model->minutes())->toBe(0);
    expect($model->hours())->toBe(0);
});

it('counts exactly one minute at the 60-second boundary', function () {
    $model = timeModel(60);

    expect($model->seconds())->toBe(60);
    expect($model->minutes())->toBe(1);
    expect($model->hours())->toBe(0);
});

it('counts exactly one hour (and 60 minutes) at the 3600-second boundary', function () {
    $model = timeModel(3600);

    expect($model->seconds())->toBe(3600);
    expect($model->minutes())->toBe(60);
    expect($model->hours())->toBe(1);
});

it('floors a one-hour-one-minute-one-second duration correctly', function () {
    $model = timeModel(3661);

    expect($model->seconds())->toBe(3661);
    expect($model->minutes())->toBe(61);
    expect($model->hours())->toBe(1);
});
