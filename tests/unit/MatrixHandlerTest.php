<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\fields\Matrix;
use craft\fields\PlainText;
use jalendport\readtime\fieldhandlers\MatrixHandler;

/**
 * Unit coverage for {@see MatrixHandler}.
 *
 * The handler only unwraps the field value through the service's
 * `getBlocks()`/`toElements()` normaliser and hands each block element back to
 * `secondsForElement()`, which is stubbed here — so interface doubles are all
 * that's needed and no booted Craft application is involved.
 */

it('handles Matrix fields and nothing else', function() {
    $handler = new MatrixHandler();

    expect($handler->canHandle(fieldInstance(Matrix::class)))->toBeTrue();
    expect($handler->canHandle(fieldInstance(PlainText::class)))->toBeFalse();
});

it('sums the walk of every block element', function() {
    $field = fieldInstance(Matrix::class);
    $field->handle = 'matrixField';

    $blockA = $this->createMock(ElementInterface::class);
    $blockB = $this->createMock(ElementInterface::class);

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->with('matrixField')->willReturn([$blockA, $blockB]);

    $service = readTimeServiceWithStubbedWalk(60);
    $seconds = (new MatrixHandler())->getReadTimeSeconds($element, $field, $service);

    expect($seconds)->toBe(120);
    expect($service->walkedElements)->toBe([$blockA, $blockB]);
});

it('resolves an unexecuted block query before walking', function() {
    $field = fieldInstance(Matrix::class);
    $field->handle = 'matrixField';

    $block = $this->createMock(ElementInterface::class);
    $query = $this->createMock(ElementQueryInterface::class);
    $query->method('all')->willReturn([$block]);

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->willReturn($query);

    $service = readTimeServiceWithStubbedWalk(60);
    $seconds = (new MatrixHandler())->getReadTimeSeconds($element, $field, $service);

    expect($seconds)->toBe(60);
    expect($service->walkedElements)->toBe([$block]);
});

it('returns zero when the field has no blocks', function() {
    $field = fieldInstance(Matrix::class);
    $field->handle = 'matrixField';

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->willReturn([]);

    $service = readTimeServiceWithStubbedWalk(60);

    expect((new MatrixHandler())->getReadTimeSeconds($element, $field, $service))->toBe(0);
    expect($service->walkedElements)->toBe([]);
});
