<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

use craft\base\ElementInterface;
use craft\ckeditor\Field as CkeditorField;
use craft\fields\PlainText;
use jalendport\readtime\fieldhandlers\CkeditorHandler;

/**
 * Unit coverage for {@see CkeditorHandler}.
 *
 * The handler duck-types against CKEditor's `FieldData` with `method_exists()`
 * (`getRawContent()`, `getEntries()`, `getChunks()`), so anonymous classes
 * stand in for the field value and its chunks — no booted Craft application
 * is needed. Only `canHandle()` touches the real field class, created without
 * its constructor so nothing reaches for the app.
 */

function ckeditorValue(string $rawContent, array $entries = []): object
{
    return new class($rawContent, $entries) {
        public function __construct(private string $rawContent, private array $entries)
        {
        }

        public function getRawContent(): string
        {
            return $this->rawContent;
        }

        public function getEntries(): array
        {
            return $this->entries;
        }
    };
}

function ckeditorChunkValue(string $rawContent, array $chunks): object
{
    return new class($rawContent, $chunks) {
        public function __construct(private string $rawContent, private array $chunks)
        {
        }

        public function getRawContent(): string
        {
            return $this->rawContent;
        }

        public function getChunks(): array
        {
            return $this->chunks;
        }
    };
}

function ckeditorEntryChunk(ElementInterface $entry): object
{
    return new class($entry) {
        public function __construct(private ElementInterface $entry)
        {
        }

        public function getEntry(): ElementInterface
        {
            return $this->entry;
        }
    };
}

it('handles CKEditor fields and nothing else', function() {
    $handler = new CkeditorHandler();

    expect($handler->canHandle(fieldInstance(CkeditorField::class)))->toBeTrue();
    expect($handler->canHandle(fieldInstance(PlainText::class)))->toBeFalse();
});

it('counts the raw content with its markup stripped', function() {
    $field = fieldInstance(CkeditorField::class);
    $field->handle = 'ckeditorField';

    $value = ckeditorValue('<p>' . str_repeat('word ', 20) . '</p>');

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->with('ckeditorField')->willReturn($value);

    $words = (new CkeditorHandler())->getWordCount($element, $field, readTimeServiceWithWpm(200));

    expect($words)->toBe(20);
});

it('casts a value without getRawContent() to string', function() {
    $field = fieldInstance(CkeditorField::class);
    $field->handle = 'ckeditorField';

    $value = new class() {
        public function __toString(): string
        {
            return '<p>' . str_repeat('word ', 20) . '</p>';
        }
    };

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->willReturn($value);

    $words = (new CkeditorHandler())->getWordCount($element, $field, readTimeServiceWithWpm(200));

    expect($words)->toBe(20);
});

it('walks embedded entries on top of the raw content', function() {
    $field = fieldInstance(CkeditorField::class);
    $field->handle = 'ckeditorField';

    $entryA = $this->createMock(ElementInterface::class);
    $entryB = $this->createMock(ElementInterface::class);
    $value = ckeditorValue('<p>' . str_repeat('word ', 20) . '</p>', [$entryA, $entryB]);

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->willReturn($value);

    $service = readTimeServiceWithStubbedWalk(200);
    $words = (new CkeditorHandler())->getWordCount($element, $field, $service);

    expect($words)->toBe(420);
    expect($service->walkedElements)->toBe([$entryA, $entryB]);
});

it('falls back to walking chunk entries when getEntries() is unavailable', function() {
    $field = fieldInstance(CkeditorField::class);
    $field->handle = 'ckeditorField';

    $entry = $this->createMock(ElementInterface::class);
    $markupChunk = new class() {
    };
    $value = ckeditorChunkValue('<p>' . str_repeat('word ', 20) . '</p>', [ckeditorEntryChunk($entry), $markupChunk]);

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->willReturn($value);

    $service = readTimeServiceWithStubbedWalk(200);
    $words = (new CkeditorHandler())->getWordCount($element, $field, $service);

    expect($words)->toBe(220);
    expect($service->walkedElements)->toBe([$entry]);
});

it('returns zero for a missing field value', function() {
    $field = fieldInstance(CkeditorField::class);
    $field->handle = 'ckeditorField';

    $element = $this->createMock(ElementInterface::class);
    $element->method('getFieldValue')->willReturn(null);

    $words = (new CkeditorHandler())->getWordCount($element, $field, readTimeServiceWithWpm(200));

    expect($words)->toBe(0);
});
