<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

use benf\neo\Field as NeoField;
use craft\base\ElementInterface;
use craft\fields\Matrix;
use craft\fields\PlainText;
use jalendport\readtime\fieldhandlers\NeoHandler;

/**
 * Unit coverage for {@see NeoHandler}.
 *
 * Mirrors {@see MatrixHandlerTest}: the handler unwraps blocks through the
 * service and hands each one to the stubbed `wordsForElement()` walk, so no
 * booted Craft application (or installed Neo plugin) is needed — the real
 * `benf\neo\Field` from `require-dev` is only instantiated, constructor-free,
 * for the `instanceof` check.
 */

it('handles Neo fields and nothing else — not even Matrix', function() {
    $handler = new NeoHandler();

    expect($handler->canHandle(fieldInstance(NeoField::class)))->toBeTrue();
    expect($handler->canHandle(fieldInstance(Matrix::class)))->toBeFalse();
    expect($handler->canHandle(fieldInstance(PlainText::class)))->toBeFalse();
});

it('sums the walk of every block element', function() {
    $field = fieldInstance(NeoField::class);
    $field->handle = 'neoField';

    $blockA = $this->createMock(ElementInterface::class);
    $blockB = $this->createMock(ElementInterface::class);

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->with('neoField')->willReturn([$blockA, $blockB]);

    $service = readTimeServiceWithStubbedWalk(200);
    $words = (new NeoHandler())->getWordCount($element, $field, $service);

    expect($words)->toBe(400);
    expect($service->walkedElements)->toBe([$blockA, $blockB]);
});

it('returns zero when the field has no blocks', function() {
    $field = fieldInstance(NeoField::class);
    $field->handle = 'neoField';

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->willReturn([]);

    $service = readTimeServiceWithStubbedWalk(200);

    expect((new NeoHandler())->getWordCount($element, $field, $service))->toBe(0);
    expect($service->walkedElements)->toBe([]);
});
