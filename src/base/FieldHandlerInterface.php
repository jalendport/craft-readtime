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
 * A field handler knows how to count the read time of a single, specific field
 * type. Handlers for nested-block field types (Matrix, Neo, Vizy, CKEditor)
 * recurse back into the service via {@see ReadTime::secondsForElement()}.
 *
 * Adding support for a new field type means adding one handler and registering
 * it — no new branch in a giant conditional.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 3.0.0
 */
interface FieldHandlerInterface
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
     * @since 3.0.0
     */
    public function canHandle(FieldInterface $field): bool;

    /**
     * Returns the read time, in seconds, for the field's value on the element.
     *
     * @param ElementInterface $element the element the field belongs to
     * @param FieldInterface $field the field to count
     * @param ReadTime $service the read time service, for recursing into nested elements
     * @return int the read time, in seconds
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    public function getReadTimeSeconds(ElementInterface $element, FieldInterface $field, ReadTime $service): int;
}
