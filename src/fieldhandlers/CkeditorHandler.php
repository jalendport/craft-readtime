<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime\fieldhandlers;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\ckeditor\Field as CkeditorField;
use jalendport\readtime\base\WordCountHandlerInterface;
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
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 3.0.0
 */
class CkeditorHandler implements WordCountHandlerInterface
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    public function canHandle(FieldInterface $field): bool
    {
        return $field instanceof CkeditorField;
    }

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.3.0
     */
    public function getWordCount(ElementInterface $element, FieldInterface $field, ReadTime $service): int
    {
        $value = $element->getFieldValue($field->handle);

        if ($value === null) {
            return 0;
        }

        $raw = method_exists($value, 'getRawContent') ? $value->getRawContent() : (string)$value;
        $words = $service->wordsForString($raw);

        foreach ($this->_getNestedEntries($value) as $entry) {
            $words += $service->wordsForElement($entry);
        }

        return $words;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the entries embedded inside a CKEditor field value.
     *
     * @param object $value the CKEditor field value
     * @return ElementInterface[] the embedded entries
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    private function _getNestedEntries(object $value): array
    {
        // CKEditor 4.x for Craft 5 exposes the embedded entries directly.
        if (method_exists($value, 'getEntries')) {
            $entries = $value->getEntries();

            return is_iterable($entries) ? $this->_onlyElements($entries) : [];
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
     * Filters an iterable down to the elements it contains.
     *
     * @param iterable<mixed> $items the items to filter
     * @return ElementInterface[] the elements the iterable contains
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    private function _onlyElements(iterable $items): array
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
