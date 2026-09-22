<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime\base;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use jalendport\readtime\services\ReadTime;

/**
 * A word count handler knows how to count the words of a single, specific
 * field type. Handlers for nested-block field types (Matrix, Neo, Vizy,
 * CKEditor, Content Block) recurse back into the service via
 * {@see ReadTime::wordsForElement()}.
 *
 * Handlers count words rather than seconds so the service can sum the whole
 * walk first and convert to seconds once. Converting per field would floor
 * each short field (a heading, a button label) to zero on its own.
 *
 * Adding support for a new field type means adding one handler and registering
 * it via {@see ReadTime::EVENT_REGISTER_FIELD_HANDLERS} — no new branch in a
 * giant conditional.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 3.3.0
 */
interface WordCountHandlerInterface
{
    /**
     * Whether this handler is responsible for counting the given field.
     *
     * Implementations should use `instanceof` against the field's class, which
     * is safe even when the owning third-party plugin is not installed (the
     * class simply fails to autoload and `instanceof` evaluates to false).
     *
     * @param FieldInterface $field the field to test
     * @return bool whether this handler counts the field
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.3.0
     */
    public function canHandle(FieldInterface $field): bool;

    /**
     * Returns the number of words in the field's value on the element.
     *
     * @param ElementInterface $element the element the field belongs to
     * @param FieldInterface $field the field to count
     * @param ReadTime $service the read time service, for recursing into nested elements
     * @return int the number of words
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.3.0
     */
    public function getWordCount(ElementInterface $element, FieldInterface $field, ReadTime $service): int;
}
