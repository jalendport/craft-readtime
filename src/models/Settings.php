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

namespace jalendport\readtime\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    /**
     * The reserved keyword that pins the human-readable output to the language
     * of the site the content belongs to.
     */
    public const OUTPUT_LOCALE_SITE = 'site';

    /**
     * @var int Average reading speed, in words per minute.
     */
    public int $wordsPerMinute = 200;

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
     */
    public ?string $outputLocale = null;

    public function rules(): array
    {
        return [
            [['wordsPerMinute'], 'required'],
            [['wordsPerMinute'], 'number', 'integerOnly' => true],
            [['outputLocale'], 'validateOutputLocale'],
        ];
    }

    /**
     * Allows `null`/empty, the literal `'site'` keyword, or any locale ID that
     * Craft knows about. Validating against the full known-locale list (rather
     * than just the install's site languages) lets a power user pin an
     * off-list locale via `config/read-time.php` even though the CP dropdown
     * only lists configured site languages.
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
}
