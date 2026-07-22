<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

use craft\base\ElementInterface;
use craft\fields\PlainText;
use jalendport\readtime\fieldhandlers\VizyHandler;
use verbb\vizy\elements\Block as VizyBlockElement;
use verbb\vizy\fields\VizyField;
use verbb\vizy\nodes\VizyBlock;

/**
 * Unit coverage for {@see VizyHandler}.
 *
 * The handler duck-types against Vizy's value objects with `method_exists()`,
 * so plain anonymous classes stand in for the node collection and rich-text
 * nodes — no booted Craft application (or installed Vizy plugin) is needed.
 * Only `canHandle()` and the block branch touch real Vizy classes, which
 * `require-dev` provides: field instances are created without constructors so
 * nothing reaches for the app, and `VizyBlock` is a PHPUnit mock because the
 * `instanceof` branch can't be satisfied by a hand-rolled fake.
 */

function vizyFieldValue(array $nodes): object
{
    return new class($nodes) {
        public function __construct(private array $nodes)
        {
        }

        public function getNodes(): array
        {
            return $this->nodes;
        }
    };
}

function vizyTextNode(string $ownText, string $renderedHtml = ''): object
{
    return new class($ownText, $renderedHtml) {
        public function __construct(private string $ownText, private string $renderedHtml)
        {
        }

        public function getText(): string
        {
            return $this->ownText;
        }

        public function renderNode(): string
        {
            return $this->renderedHtml;
        }
    };
}

it('handles Vizy fields and nothing else', function() {
    $handler = new VizyHandler();

    expect($handler->canHandle(fieldInstance(VizyField::class)))->toBeTrue();
    expect($handler->canHandle(fieldInstance(PlainText::class)))->toBeFalse();
});

it('falls back to the rendered node when getText() is empty, so paragraphs are counted', function() {
    // Regression: container nodes like paragraphs keep their words in child
    // text nodes, so their own `getText()` is always empty. The handler used
    // to return that empty result as final, counting every paragraph — and
    // therefore most real-world Vizy content — as zero seconds.
    $field = fieldInstance(VizyField::class);
    $field->handle = 'vizyField';

    $value = vizyFieldValue([
        vizyTextNode('', '<p>' . str_repeat('word ', 20) . '</p>'),
    ]);

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->with('vizyField')->willReturn($value);

    $seconds = (new VizyHandler())->getReadTimeSeconds($element, $field, readTimeServiceWithWpm(200));

    expect($seconds)->toBe(6);
});

it('counts a node from its own text when getText() has content', function() {
    $field = fieldInstance(VizyField::class);
    $field->handle = 'vizyField';

    $value = vizyFieldValue([
        vizyTextNode(str_repeat('word ', 10)),
    ]);

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->willReturn($value);

    $seconds = (new VizyHandler())->getReadTimeSeconds($element, $field, readTimeServiceWithWpm(200));

    expect($seconds)->toBe(3);
});

it('sums every rich-text node in the collection', function() {
    $field = fieldInstance(VizyField::class);
    $field->handle = 'vizyField';

    $value = vizyFieldValue([
        vizyTextNode('', '<p>' . str_repeat('word ', 20) . '</p>'),
        vizyTextNode('', '<p>' . str_repeat('word ', 40) . '</p>'),
    ]);

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->willReturn($value);

    $seconds = (new VizyHandler())->getReadTimeSeconds($element, $field, readTimeServiceWithWpm(200));

    expect($seconds)->toBe(18);
});

it('walks block nodes as elements and adds them to the rich-text total', function() {
    $field = fieldInstance(VizyField::class);
    $field->handle = 'vizyField';

    $blockElement = $this->createMock(VizyBlockElement::class);
    $block = $this->createMock(VizyBlock::class);
    $block->method('getBlockElement')->willReturn($blockElement);

    $value = vizyFieldValue([
        $block,
        vizyTextNode('', '<p>' . str_repeat('word ', 20) . '</p>'),
    ]);

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->willReturn($value);

    $service = readTimeServiceWithStubbedWalk(60);
    $seconds = (new VizyHandler())->getReadTimeSeconds($element, $field, $service);

    expect($seconds)->toBe(66);
    expect($service->walkedElements)->toBe([$blockElement]);
});

it('returns zero for an empty or missing field value', function() {
    $field = fieldInstance(VizyField::class);
    $field->handle = 'vizyField';

    $handler = new VizyHandler();
    $service = readTimeServiceWithWpm(200);

    $empty = $this->createMock(ElementInterface::class);
    $empty->method('getFieldValue')->willReturn(vizyFieldValue([]));

    $missing = $this->createMock(ElementInterface::class);
    $missing->method('getFieldValue')->willReturn(null);

    expect($handler->getReadTimeSeconds($empty, $field, $service))->toBe(0);
    expect($handler->getReadTimeSeconds($missing, $field, $service))->toBe(0);
});
