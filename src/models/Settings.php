<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime\models;

use Craft;
use craft\base\Model;

/**
 * The plugin's settings.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class Settings extends Model
{
    // Const Properties
    // =========================================================================

    /**
     * The reserved keyword that pins the human-readable output to the language
     * of the site the content belongs to.
     *
     * @since 3.1.0
     */
    public const OUTPUT_LOCALE_SITE = 'site';

    // Public Properties
    // =========================================================================

    /**
     * @var int Minimum read time, in whole minutes. When greater than 0, read
     * times are rounded up to at least this many minutes. 0 (the default)
     * preserves Craft's "less than a minute" output for sub-minute content.
     * @since 3.1.0
     */
    public int $minimumReadTime = 0;

    /**
     * @var string|null The locale used to format the human-readable read-time
     * string. Encodes three modes:
     *
     * - `null` / empty → "Current language": the output follows
     *   `Craft::$app->language` (the historical behaviour).
     * - `'site'` → "Content's site language": the output is formatted in the
     *   language of the site the content belongs to.
     * - a specific locale ID (e.g. `'de-DE'`) → that locale is forced
     *   everywhere.
     *
     * Only the human-readable string is affected; numeric outputs are
     * locale-independent.
     * @since 3.1.0
     */
    public ?string $outputLocale = null;

    /**
     * @var int Average reading speed, in words per minute.
     * @since 1.0.0
     */
    public int $wordsPerMinute = 200;

    // Public Methods
    // =========================================================================

    /**
     * Builds the option list for the "Output Locale" dropdown: a blank
     * "Current language" entry, the "Content's site language" keyword, then one
     * option per configured site language. A power user can still pin an
     * off-list locale via `config/read-time.php`; {@see validateOutputLocale()}
     * validates against Craft's full known-locale list.
     *
     * @return array<int, array{label: string, value: string}>
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.2.0
     */
    public function getOutputLocaleOptions(): array
    {
        $options = [
            ['label' => Craft::t('read-time', 'Current language'), 'value' => ''],
            ['label' => Craft::t('read-time', 'Content’s site language'), 'value' => self::OUTPUT_LOCALE_SITE],
        ];

        $language = Craft::$app->language;

        foreach (Craft::$app->getI18n()->getSiteLocales() as $locale) {
            $displayName = $locale->getDisplayName($language);

            $options[] = [
                'label' => "{$displayName} ({$locale->id})",
                'value' => $locale->id,
            ];
        }

        return $options;
    }

    /**
     * Allows `null`/empty, the literal `'site'` keyword, or any locale ID that
     * Craft knows about. Validating against the full known-locale list (rather
     * than just the install's site languages) lets a power user pin an
     * off-list locale via `config/read-time.php` even though the CP dropdown
     * only lists configured site languages.
     *
     * @param string $attribute the attribute being validated
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.1.0
     */
    public function validateOutputLocale(string $attribute): void
    {
        $value = $this->$attribute;

        if ($value === null || $value === '' || $value === self::OUTPUT_LOCALE_SITE) {
            return;
        }

        if (!in_array($value, Craft::$app->getI18n()->getAllLocaleIds(), true)) {
            $this->addError($attribute, Craft::t('read-time', 'Output Locale must be empty, “{site}”, or a valid locale ID.', [
                'site' => self::OUTPUT_LOCALE_SITE,
            ]));
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['wordsPerMinute'], 'required'];
        $rules[] = [['wordsPerMinute'], 'number', 'integerOnly' => true];
        $rules[] = [['outputLocale'], 'validateOutputLocale'];
        $rules[] = [['minimumReadTime'], 'number', 'integerOnly' => true, 'min' => 0];

        return $rules;
    }
}
