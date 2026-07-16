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
use craft\helpers\DateTimeHelper;
use Exception;

/**
 * A calculated read time, returned by both the `readTime()` Twig function and
 * the `|readTime` filter.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.3.0
 */
class TimeModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var int Total read time, in seconds.
     * @since 1.3.0
     */
    public int $seconds = 0;

    /**
     * @var bool Whether seconds are included in the human-readable duration.
     * @since 1.3.0
     */
    public bool $showSeconds = true;

    /**
     * @var string|null The concrete locale to format the human-readable
     * duration in, or `null` to follow the current application language.
     *
     * This is always a resolved locale ID (never the `'site'` keyword): the
     * {@see \jalendport\readtime\services\ReadTime} service resolves the
     * configured mode against the element/site before building the model, so
     * this value object stays free of element or settings logic.
     * @since 3.1.0
     */
    public ?string $outputLocale = null;

    // Public Methods
    // =========================================================================

    /**
     * Returns the human-readable duration, so the model can be output directly.
     *
     * @return string the human-readable duration
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.3.0
     */
    public function __toString(): string
    {
        return $this->human();
    }

    /**
     * Returns the read time as a human-readable duration (e.g. “2 minutes,
     * 40 seconds”).
     *
     * @return string the human-readable duration
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.3.0
     */
    public function human(): string
    {
        if ($this->outputLocale === null) {
            return DateTimeHelper::humanDuration($this->seconds, $this->showSeconds);
        }

        // DateTimeHelper::humanDuration() takes no locale argument — it formats
        // using Craft::$app->language. Temporarily swap the application language
        // to the resolved locale around the call so the wording is translated
        // consistently regardless of context (CP request, front-end, or a
        // `php craft resave/entries` console run), then always restore it.
        $app = Craft::$app;
        $originalLanguage = $app->language;
        $app->language = $this->outputLocale;

        try {
            return DateTimeHelper::humanDuration($this->seconds, $this->showSeconds);
        } finally {
            $app->language = $originalLanguage;
        }
    }

    /**
     * Returns the read time formatted as a {@see \DateInterval}.
     *
     * @param string $format the DateInterval format string
     * @return string the formatted interval
     * @throws Exception if the current timestamp can't be resolved to a date
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.3.0
     */
    public function interval(string $format = '%h hours, %i minutes, %s seconds'): string
    {
        $currentTimeStamp = DateTimeHelper::currentTimeStamp();
        $datetimeStart = DateTimeHelper::toDateTime($currentTimeStamp);
        $datetimeEnd = DateTimeHelper::toDateTime(DateTimeHelper::currentTimeStamp() + $this->seconds);

        $interval = $datetimeStart->diff($datetimeEnd);

        return $interval->format($format);
    }

    /**
     * Returns the total read time, in whole hours.
     *
     * @return int the read time, in hours
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.3.0
     */
    public function hours(): int
    {
        return (int)floor(($this->seconds / 60) / 60);
    }

    /**
     * Returns the total read time, in whole minutes.
     *
     * @return int the read time, in minutes
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.3.0
     */
    public function minutes(): int
    {
        return (int)floor($this->seconds / 60);
    }

    /**
     * Returns the total read time, in seconds.
     *
     * @return int the read time, in seconds
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.3.0
     */
    public function seconds(): int
    {
        return $this->seconds;
    }
}
