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

namespace jalendport\readtime\services;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\StringHelper;
use jalendport\readtime\base\FieldHandlerInterface;
use jalendport\readtime\events\RegisterFieldHandlersEvent;
use jalendport\readtime\fieldhandlers\CkeditorHandler;
use jalendport\readtime\fieldhandlers\MatrixHandler;
use jalendport\readtime\fieldhandlers\NeoHandler;
use jalendport\readtime\fieldhandlers\VizyHandler;
use jalendport\readtime\models\TimeModel;
use jalendport\readtime\ReadTime as ReadTimePlugin;
use Throwable;

/**
 * The read time service holds all of the counting and field-walking logic. The
 * Twig extension is a thin wrapper that delegates here.
 *
 * Field walking is recursive: {@see secondsForElement()} walks an element's
 * custom fields, dispatching each to a {@see FieldHandlerInterface}. Handlers
 * for nested-block field types recurse back into {@see secondsForElement()},
 * which naturally supports arbitrary nesting (e.g. Matrix-in-Neo, entries
 * embedded in a CKEditor field that themselves contain a Matrix field).
 */
class ReadTime extends Component
{
    /**
     * @event RegisterFieldHandlersEvent Fired when the list of field handlers is built.
     */
    public const EVENT_REGISTER_FIELD_HANDLERS = 'registerFieldHandlers';

    /**
     * Guards against pathological self-referencing content (e.g. an entry
     * embedded in its own CKEditor field).
     */
    private const MAX_DEPTH = 10;

    /**
     * @var FieldHandlerInterface[]|null
     */
    private ?array $fieldHandlers = null;

    private int $depth = 0;

    /**
     * Calculates the read time for an element (or an array/query of elements),
     * walking its field layout. Backs the `readTime()` Twig function.
     */
    public function calculateForElement(mixed $element, bool $showSeconds = true): TimeModel
    {
        return new TimeModel([
            'seconds' => $this->secondsForValue($element),
            'showSeconds' => $showSeconds,
        ]);
    }

    /**
     * Calculates the read time for a raw value (string, or array of values).
     * Backs the `|readTime` Twig filter.
     */
    public function calculateForValue(mixed $value, bool $showSeconds = true): TimeModel
    {
        return new TimeModel([
            'seconds' => $this->secondsForString($value),
            'showSeconds' => $showSeconds,
        ]);
    }

    /**
     * Returns the total read time, in seconds, for every custom field in the
     * element's field layout.
     */
    public function secondsForElement(ElementInterface $element): int
    {
        $layout = $element->getFieldLayout();

        if ($layout === null || $this->depth >= self::MAX_DEPTH) {
            return 0;
        }

        $this->depth++;
        $seconds = 0;

        try {
            foreach ($layout->getCustomFields() as $field) {
                try {
                    $seconds += $this->secondsForField($element, $field);
                } catch (Throwable $e) {
                    // Never let a single field break read time on the front end.
                    Craft::warning(
                        "Read Time skipped field “{$field->handle}”: {$e->getMessage()}",
                        __METHOD__
                    );
                    continue;
                }
            }
        } finally {
            $this->depth--;
        }

        return $seconds;
    }

    /**
     * Returns the read time, in seconds, for a plain text/HTML value.
     */
    public function secondsForString(mixed $value): int
    {
        return $this->wordsToSeconds($this->countWords($value));
    }

    /**
     * Normalises a nested-block field value (entry query, element collection or
     * array) into a list of elements. Used by the block-based field handlers.
     *
     * @return ElementInterface[]
     */
    public function getBlocks(ElementInterface $element, FieldInterface $field): array
    {
        return $this->toElements($element->getFieldValue($field->handle));
    }

    /**
     * @return ElementInterface[]
     */
    public function toElements(mixed $value): array
    {
        if ($value instanceof ElementQueryInterface) {
            $value = $value->all();
        }

        if (!is_iterable($value)) {
            return [];
        }

        $elements = [];

        foreach ($value as $item) {
            if ($item instanceof ElementInterface) {
                $elements[] = $item;
            }
        }

        return $elements;
    }

    // Private Methods
    // =========================================================================

    private function secondsForValue(mixed $element): int
    {
        if ($element instanceof ElementInterface) {
            return $this->secondsForElement($element);
        }

        // A Matrix/Neo field value (query, collection or array of block elements)
        // passed straight to readTime() — e.g. readTime(entry.matrixField.all()).
        $elements = $this->toElements($element);

        if ($elements !== []) {
            $seconds = 0;

            foreach ($elements as $item) {
                $seconds += $this->secondsForElement($item);
            }

            return $seconds;
        }

        // Fall back to counting the value as plain content.
        return $this->secondsForString($element);
    }

    private function secondsForField(ElementInterface $element, FieldInterface $field): int
    {
        foreach ($this->getFieldHandlers() as $handler) {
            if ($handler->canHandle($field)) {
                return $handler->getReadTimeSeconds($element, $field, $this);
            }
        }

        return $this->secondsForString($element->getFieldValue($field->handle));
    }

    private function countWords(mixed $value): int
    {
        return StringHelper::countWords(StringHelper::toString($value));
    }

    private function wordsToSeconds(int $words): int
    {
        return (int)floor($words / $this->getWordsPerMinute() * 60);
    }

    private function getWordsPerMinute(): int
    {
        $wpm = ReadTimePlugin::getInstance()->getSettings()->wordsPerMinute;

        return $wpm > 0 ? $wpm : 200;
    }

    /**
     * @return FieldHandlerInterface[]
     */
    private function getFieldHandlers(): array
    {
        if ($this->fieldHandlers === null) {
            $event = new RegisterFieldHandlersEvent([
                'handlers' => [
                    new MatrixHandler(),
                    new NeoHandler(),
                    new VizyHandler(),
                    new CkeditorHandler(),
                ],
            ]);

            $this->trigger(self::EVENT_REGISTER_FIELD_HANDLERS, $event);

            $this->fieldHandlers = $event->handlers;
        }

        return $this->fieldHandlers;
    }
}
