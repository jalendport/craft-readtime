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
use craft\fields\Addresses;
use craft\fields\BaseOptionsField;
use craft\fields\BaseRelationField;
use craft\fields\Color;
use craft\fields\Country;
use craft\fields\Date;
use craft\fields\Email;
use craft\fields\Icon;
use craft\fields\Json;
use craft\fields\Lightswitch;
use craft\fields\Link;
use craft\fields\Money;
use craft\fields\Number;
use craft\fields\Range;
use craft\fields\Time;
use jalendport\readtime\base\WordCountHandlerInterface;
use jalendport\readtime\services\ReadTime;

/**
 * Handler for native field types whose values are never readable prose.
 *
 * Without it these fall through to plain-text counting, where a Lightswitch
 * stringifies to "1", a Link to its URL, a Number to its digits and a relation
 * field to the titles of every related element — none of which a reader reads.
 * A page builder with dozens of blocks carrying toggles, links and images would
 * otherwise gain real seconds of noise.
 *
 * Only Craft's own field types are listed. Third-party fields still fall back
 * to plain-text counting, so an unknown rich-text field keeps working.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 3.3.0
 */
class NonTextHandler implements WordCountHandlerInterface
{
    // Const Properties
    // =========================================================================

    /**
     * Native field classes whose values must count as zero words. Base classes
     * cover their whole family: `BaseRelationField` is Assets, Categories,
     * Entries, Tags and Users; `BaseOptionsField` is Checkboxes, Dropdown,
     * Multi-select, Radio Buttons and Button Group. `Url` is an alias of `Link`.
     *
     * Checked with `instanceof`, which is safe for classes that don't exist on
     * older Craft 5 releases (Icon, Json, Link, Range) — the check is just false.
     *
     * @var class-string[]
     * @since 3.3.0
     */
    public const FIELD_TYPES = [
        Addresses::class,
        BaseOptionsField::class,
        BaseRelationField::class,
        Color::class,
        Country::class,
        Date::class,
        Email::class,
        Icon::class,
        Json::class,
        Lightswitch::class,
        Link::class,
        Money::class,
        Number::class,
        Range::class,
        Time::class,
    ];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.3.0
     */
    public function canHandle(FieldInterface $field): bool
    {
        foreach (self::FIELD_TYPES as $class) {
            if ($field instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.3.0
     */
    public function getWordCount(ElementInterface $element, FieldInterface $field, ReadTime $service): int
    {
        return 0;
    }
}
