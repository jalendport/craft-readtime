<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

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
use jalendport\readtime\models\Settings;
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
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 3.0.0
 */
class ReadTime extends Component
{
    // Const Properties
    // =========================================================================

    /**
     * @event RegisterFieldHandlersEvent The event that is triggered when the
     * list of field handlers is built.
     * @since 3.0.0
     */
    public const EVENT_REGISTER_FIELD_HANDLERS = 'registerFieldHandlers';

    /**
     * Guards against pathological self-referencing content (e.g. an entry
     * embedded in its own CKEditor field).
     *
     * @since 3.0.0
     */
    private const MAX_DEPTH = 10;

    // Private Properties
    // =========================================================================

    /**
     * @var int The current recursion depth of the field walk.
     * @since 3.0.0
     */
    private int $_depth = 0;

    /**
     * @var FieldHandlerInterface[]|null The memoized field handlers.
     * @see _getFieldHandlers()
     * @since 3.0.0
     */
    private ?array $_fieldHandlers = null;

    // Public Methods
    // =========================================================================

    /**
     * Calculates the read time for an element (or an array/query of elements),
     * walking its field layout. Backs the `readTime()` Twig function.
     *
     * @param mixed $element the element, element query, or raw value to count
     * @param bool $showSeconds whether seconds are included in the human-readable duration
     * @return TimeModel the resulting read time
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    public function calculateForElement(mixed $element, bool $showSeconds = true): TimeModel
    {
        // Resolve the output-locale mode against the element when we have one,
        // so 'site' mode picks up that element's site language.
        $context = $element instanceof ElementInterface ? $element : null;

        return $this->_makeTimeModel($this->_secondsForValue($element), $showSeconds, $context);
    }

    /**
     * Calculates the read time for a raw value (string, or array of values).
     * Backs the `|readTime` Twig filter.
     *
     * @param mixed $value the value to count
     * @param bool $showSeconds whether seconds are included in the human-readable duration
     * @return TimeModel the resulting read time
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    public function calculateForValue(mixed $value, bool $showSeconds = true): TimeModel
    {
        // The filter path has no element; 'site' mode falls back to the current
        // site's language inside _resolveOutputLocale().
        return $this->_makeTimeModel($this->secondsForString($value), $showSeconds, null);
    }

    /**
     * Normalises a nested-block field value (entry query, element collection or
     * array) into a list of elements. Used by the block-based field handlers.
     *
     * @param ElementInterface $element the element the field belongs to
     * @param FieldInterface $field the block-based field
     * @return ElementInterface[] the field's block elements
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    public function getBlocks(ElementInterface $element, FieldInterface $field): array
    {
        return $this->toElements($element->getFieldValue($field->handle));
    }

    /**
     * Returns the total read time, in seconds, for every custom field in the
     * element's field layout.
     *
     * @param ElementInterface $element the element to walk
     * @return int the read time, in seconds
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    public function secondsForElement(ElementInterface $element): int
    {
        $layout = $element->getFieldLayout();

        if ($layout === null || $this->_depth >= self::MAX_DEPTH) {
            return 0;
        }

        $this->_depth++;
        $seconds = 0;

        try {
            foreach ($layout->getCustomFields() as $field) {
                try {
                    $seconds += $this->_secondsForField($element, $field);
                } catch (Throwable $e) {
                    // Never let a single field break read time on the front end.
                    ReadTimePlugin::warning("Skipped field “{$field->handle}”: {$e->getMessage()}");
                    continue;
                }
            }
        } finally {
            $this->_depth--;
        }

        return $seconds;
    }

    /**
     * Returns the read time, in seconds, for a plain text/HTML value.
     *
     * @param mixed $value the value to count
     * @return int the read time, in seconds
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    public function secondsForString(mixed $value): int
    {
        return $this->_wordsToSeconds($this->_countWords($value));
    }

    /**
     * Filters a value (element query, collection or array) down to the elements
     * it contains.
     *
     * @param mixed $value the value to normalise
     * @return ElementInterface[] the elements the value contains
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
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

    // Protected Methods
    // =========================================================================

    /**
     * Returns the configured reading speed, in words per minute, falling back to
     * 200 when the setting is missing or non-positive.
     *
     * `protected` rather than `private` so the words-per-minute lookup — the one
     * piece of {@see _wordsToSeconds()} that reaches the plugin singleton (and a
     * booted Craft app) — can be overridden in unit tests, keeping the
     * surrounding arithmetic testable without an application.
     *
     * @return int the reading speed, in words per minute
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    protected function getWordsPerMinute(): int
    {
        $wpm = ReadTimePlugin::$plugin->getSettings()->wordsPerMinute;

        return $wpm > 0 ? $wpm : 200;
    }

    // Private Methods
    // =========================================================================

    /**
     * @param mixed $value the value to count
     * @return int the number of words
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    private function _countWords(mixed $value): int
    {
        return StringHelper::countWords($this->_htmlToText(StringHelper::toString($value)));
    }

    /**
     * @return FieldHandlerInterface[] the registered field handlers, in priority order
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    private function _getFieldHandlers(): array
    {
        if ($this->_fieldHandlers === null) {
            $event = new RegisterFieldHandlersEvent([
                'handlers' => [
                    new MatrixHandler(),
                    new NeoHandler(),
                    new VizyHandler(),
                    new CkeditorHandler(),
                ],
            ]);

            $this->trigger(self::EVENT_REGISTER_FIELD_HANDLERS, $event);

            $this->_fieldHandlers = $event->handlers;
        }

        return $this->_fieldHandlers;
    }

    /**
     * Returns the configured minimum read time, in whole minutes.
     *
     * @return int the minimum read time, in minutes
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.1.0
     */
    private function _getMinimumReadTime(): int
    {
        $minimum = ReadTimePlugin::$plugin->getSettings()->minimumReadTime;

        return $minimum > 0 ? $minimum : 0;
    }

    /**
     * Cleans a (possibly HTML) string so markup doesn't inflate its word count.
     * Rich-text fields (CKEditor, Vizy, etc.) and raw strings passed to the
     * `readTime` Twig filter both route through here via {@see _countWords()},
     * so the fix applies to every counting path.
     *
     * Behaviour:
     * - HTML tags are replaced with a single space (not removed) so words on
     *   either side of a tag boundary stay separate — `<p>foo</p><p>bar</p>`
     *   counts as two words, not one. Removing tags outright would merge them.
     * - HTML entities are decoded *after* tags are stripped, so text that is
     *   legitimately encoded as `&lt;`/`&gt;` isn't mistaken for a tag and
     *   over-stripped. Decoding also keeps entities like `&amp;` from being
     *   counted as words and turns `&nbsp;` into whitespace.
     * - Runs of whitespace are collapsed, including the spaces introduced above
     *   and Unicode whitespace such as the non-breaking space (U+00A0) produced
     *   by `&nbsp;`. The `(*UCP)` verb makes `\s` match Unicode whitespace.
     *
     * Plain (non-HTML) text has no tags or entities, so it passes through
     * unchanged apart from whitespace trimming, leaving its word count intact.
     *
     * @param string $text the text to clean
     * @return string the cleaned text
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.1.0
     */
    private function _htmlToText(string $text): string
    {
        // Order matters: strip tags first, then decode entities.
        $text = preg_replace('/<[^>]+>/', ' ', $text) ?? $text;
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('/(*UCP)\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Builds a {@see TimeModel} from a raw second count, applying the configured
     * minimum read time and resolving the output locale. This is the single place
     * the floor is enforced, so every output path — `human()`, `__toString()`,
     * `seconds()`/`minutes()`/`hours()`, the Twig function and filter, and every
     * other consumer of the returned `TimeModel` — agrees.
     *
     * @param int $seconds the raw read time, in seconds
     * @param bool $showSeconds whether seconds are included in the human-readable duration
     * @param ElementInterface|null $context the element the read time was calculated for, if any
     * @return TimeModel the resulting read time
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.1.0
     */
    private function _makeTimeModel(int $seconds, bool $showSeconds, ?ElementInterface $context = null): TimeModel
    {
        $minimum = $this->_getMinimumReadTime();

        if ($minimum > 0) {
            $seconds = max($seconds, $minimum * 60);
        }

        return new TimeModel([
            'seconds' => $seconds,
            'showSeconds' => $showSeconds,
            'outputLocale' => $this->_resolveOutputLocale($context),
        ]);
    }

    /**
     * Resolves the configured `outputLocale` mode to a concrete locale ID (or
     * `null`) for the model. Keeping this in the service means {@see TimeModel}
     * never sees the `'site'` keyword, the settings, or the element.
     *
     * - empty/null → `null` (the model follows the current application language).
     * - `'site'` → the element's site language, or — on the element-less filter
     *   path — the current site's language (preserving the "a site's language"
     *   semantic rather than falling back to the CP user's language).
     * - a specific locale ID → that locale, verbatim.
     *
     * @param ElementInterface|null $element the element the read time was calculated for, if any
     * @return string|null the resolved locale ID, or null to follow the current language
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.1.0
     */
    private function _resolveOutputLocale(?ElementInterface $element): ?string
    {
        $mode = ReadTimePlugin::$plugin->getSettings()->outputLocale;

        if ($mode === null || $mode === '') {
            return null;
        }

        if ($mode === Settings::OUTPUT_LOCALE_SITE) {
            if ($element !== null) {
                return $element->getSite()->language;
            }

            return Craft::$app->getSites()->getCurrentSite()->language;
        }

        return $mode;
    }

    /**
     * @param ElementInterface $element the element the field belongs to
     * @param FieldInterface $field the field to count
     * @return int the read time, in seconds
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    private function _secondsForField(ElementInterface $element, FieldInterface $field): int
    {
        foreach ($this->_getFieldHandlers() as $handler) {
            if ($handler->canHandle($field)) {
                return $handler->getReadTimeSeconds($element, $field, $this);
            }
        }

        return $this->secondsForString($element->getFieldValue($field->handle));
    }

    /**
     * @param mixed $element the element, element query, or raw value to count
     * @return int the read time, in seconds
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    private function _secondsForValue(mixed $element): int
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

    /**
     * @param int $words the number of words
     * @return int the read time, in seconds
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    private function _wordsToSeconds(int $words): int
    {
        return (int)floor($words / $this->getWordsPerMinute() * 60);
    }
}
