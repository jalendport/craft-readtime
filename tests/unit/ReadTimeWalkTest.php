<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\elements\Entry;
use craft\fields\ContentBlock;
use craft\fields\Lightswitch;
use craft\fields\PlainText;
use craft\models\EntryType;
use craft\models\FieldLayout;
use jalendport\readtime\base\FieldHandlerInterface;
use jalendport\readtime\events\RegisterFieldHandlersEvent;
use jalendport\readtime\services\ReadTime;

/**
 * Unit coverage for the element walk in {@see ReadTime::wordsForElement()}.
 *
 * The walk only needs a field layout that answers `getCustomFields()` and an
 * element that answers `getFieldLayout()`/`getFieldValue()`. A constructor-free
 * `FieldLayout` with its memoized field list pre-filled stands in for the
 * former, PHPUnit doubles of `ElementInterface` (or `Entry`, for the title
 * rules) for the latter, so no booted Craft application is needed. The built-in
 * handlers are real; only the words-per-minute lookup is stubbed.
 */

function layoutOf(array $fields): FieldLayout
{
    $layout = (new ReflectionClass(FieldLayout::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(FieldLayout::class, '_customFields'))->setValue($layout, $fields);

    return $layout;
}

function fieldOf(string $class, string $handle): FieldInterface
{
    $field = fieldInstance($class);
    $field->handle = $handle;

    return $field;
}

it('sums words across every field and converts to seconds once', function() {
    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldLayout')->willReturn(layoutOf([
        fieldOf(PlainText::class, 'a'),
        fieldOf(PlainText::class, 'b'),
        fieldOf(PlainText::class, 'c'),
    ]));
    $element->method('getFieldValue')->willReturn('one two three');

    $service = readTimeServiceWithWpm(200);

    expect($service->wordsForElement($element))->toBe(9);
    expect($service->secondsForElement($element))->toBe(2);
});

it('walks a Content Block field into its nested element', function() {
    $nested = $this->createMock(ElementInterface::class);
    $nested->method('getFieldLayout')->willReturn(layoutOf([fieldOf(PlainText::class, 'body')]));
    $nested->method('getFieldValue')->willReturn('alpha beta gamma delta');

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldLayout')->willReturn(layoutOf([fieldOf(ContentBlock::class, 'contentBlock')]));
    $element->method('getFieldValue')->willReturn($nested);

    expect(readTimeServiceWithWpm(200)->wordsForElement($element))->toBe(4);
});

it('skips native non-text fields', function() {
    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldLayout')->willReturn(layoutOf([fieldOf(Lightswitch::class, 'toggle')]));
    $element->method('getFieldValue')->willReturn(true);

    expect(readTimeServiceWithWpm(200)->wordsForElement($element))->toBe(0);
});

it('counts an entry title when its type has a title field', function() {
    $type = fieldInstance(EntryType::class);
    $type->hasTitleField = true;

    $entry = $this->createMock(Entry::class);
    $entry->method('getType')->willReturn($type);
    $entry->method('getFieldLayout')->willReturn(layoutOf([]));
    $entry->title = 'Five words in this title';

    expect(readTimeServiceWithWpm(200)->wordsForElement($entry))->toBe(5);
});

it('ignores a generated title when the entry type has no title field', function() {
    $type = fieldInstance(EntryType::class);
    $type->hasTitleField = false;

    $entry = $this->createMock(Entry::class);
    $entry->method('getType')->willReturn($type);
    $entry->method('getFieldLayout')->willReturn(layoutOf([]));
    $entry->title = 'Generated from a title format';

    expect(readTimeServiceWithWpm(200)->wordsForElement($entry))->toBe(0);
});

it('returns zero for an element without a field layout', function() {
    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldLayout')->willReturn(null);

    expect(readTimeServiceWithWpm(200)->wordsForElement($element))->toBe(0);
});

it('converts a legacy seconds-based handler back into words', function() {
    $legacy = new class() implements FieldHandlerInterface {
        public function canHandle(FieldInterface $field): bool
        {
            return true;
        }

        public function getReadTimeSeconds(ElementInterface $element, FieldInterface $field, ReadTime $service): int
        {
            return 30;
        }
    };

    $service = readTimeServiceWithWpm(200);
    $service->on(ReadTime::EVENT_REGISTER_FIELD_HANDLERS, static function(RegisterFieldHandlersEvent $event) use ($legacy): void {
        $event->handlers = [$legacy];
    });

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldLayout')->willReturn(layoutOf([fieldOf(PlainText::class, 'a')]));
    $element->method('getFieldValue')->willReturn('ignored by the legacy handler');

    expect($service->wordsForElement($element))->toBe(100);
});
