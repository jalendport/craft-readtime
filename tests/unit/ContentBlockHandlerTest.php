<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

use craft\base\ElementInterface;
use craft\fields\ContentBlock;
use craft\fields\Matrix;
use craft\fields\PlainText;
use jalendport\readtime\fieldhandlers\ContentBlockHandler;

/**
 * Unit coverage for {@see ContentBlockHandler}.
 *
 * A Content Block field's value is a single nested element, not a query, so
 * the handler hands it straight to the stubbed `wordsForElement()` walk — no
 * booted Craft application is needed.
 */

it('handles Content Block fields and nothing else — not even Matrix', function() {
    $handler = new ContentBlockHandler();

    expect($handler->canHandle(fieldInstance(ContentBlock::class)))->toBeTrue();
    expect($handler->canHandle(fieldInstance(Matrix::class)))->toBeFalse();
    expect($handler->canHandle(fieldInstance(PlainText::class)))->toBeFalse();
});

it('walks the single nested element', function() {
    $field = fieldInstance(ContentBlock::class);
    $field->handle = 'contentBlockField';

    $nested = $this->createMock(ElementInterface::class);

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->with('contentBlockField')->willReturn($nested);

    $service = readTimeServiceWithStubbedWalk(200);
    $words = (new ContentBlockHandler())->getWordCount($element, $field, $service);

    expect($words)->toBe(200);
    expect($service->walkedElements)->toBe([$nested]);
});

it('returns zero when the field is empty', function() {
    $field = fieldInstance(ContentBlock::class);
    $field->handle = 'contentBlockField';

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->willReturn(null);

    $service = readTimeServiceWithStubbedWalk(200);

    expect((new ContentBlockHandler())->getWordCount($element, $field, $service))->toBe(0);
    expect($service->walkedElements)->toBe([]);
});
