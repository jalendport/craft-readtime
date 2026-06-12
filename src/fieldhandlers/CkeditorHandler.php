<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

declare(strict_types=1);

namespace jalendport\readtime\fieldhandlers;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\ckeditor\Field as CkeditorField;
use jalendport\readtime\base\FieldHandlerInterface;
use jalendport\readtime\services\ReadTime;

/**
 * CKEditor field handler (craft/ckeditor).
 *
 * CKEditor is an optional, soft dependency. We count the editor's own rich-text
 * content and, on Craft 5, the content of any entries embedded inside the field.
 *
 * The field's own words are counted from its raw stored markup so the nested
 * entry placeholder tags aren't expanded into rendered entry HTML and counted
 * twice — each embedded entry is instead walked recursively as an element.
 */
class CkeditorHandler implements FieldHandlerInterface
{
    public function canHandle(FieldInterface $field): bool
    {
        return $field instanceof CkeditorField;
    }

    public function getReadTimeSeconds(ElementInterface $element, FieldInterface $field, ReadTime $service): int
    {
        $value = $element->getFieldValue($field->handle);

        if ($value === null) {
            return 0;
        }

        $raw = method_exists($value, 'getRawContent') ? $value->getRawContent() : (string)$value;
        $seconds = $service->secondsForString($raw);

        foreach ($this->getNestedEntries($value) as $entry) {
            $seconds += $service->secondsForElement($entry);
        }

        return $seconds;
    }

    /**
     * @return ElementInterface[]
     */
    private function getNestedEntries(object $value): array
    {
        // CKEditor 4.x for Craft 5 exposes the embedded entries directly.
        if (method_exists($value, 'getEntries')) {
            $entries = $value->getEntries();

            return is_iterable($entries) ? $this->onlyElements($entries) : [];
        }

        // Fall back to walking the field's content chunks.
        if (method_exists($value, 'getChunks')) {
            $entries = [];

            foreach ($value->getChunks() as $chunk) {
                if (method_exists($chunk, 'getEntry')) {
                    $entry = $chunk->getEntry();

                    if ($entry instanceof ElementInterface) {
                        $entries[] = $entry;
                    }
                }
            }

            return $entries;
        }

        return [];
    }

    /**
     * @param iterable<mixed> $items
     * @return ElementInterface[]
     */
    private function onlyElements(iterable $items): array
    {
        $elements = [];

        foreach ($items as $item) {
            if ($item instanceof ElementInterface) {
                $elements[] = $item;
            }
        }

        return $elements;
    }
}
