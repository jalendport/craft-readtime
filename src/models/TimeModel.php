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
use craft\helpers\DateTimeHelper;
use Exception;

class TimeModel extends Model
{
    /**
     * @var int Total read time, in seconds.
     */
    public int $seconds = 0;

    /**
     * @var bool Whether seconds are included in the human-readable duration.
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
     */
    public ?string $outputLocale = null;

    public function __toString(): string
    {
        return $this->human();
    }

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
     * @throws Exception
     */
    public function interval(string $format = '%h hours, %i minutes, %s seconds'): string
    {
        $currentTimeStamp = DateTimeHelper::currentTimeStamp();
        $datetimeStart = DateTimeHelper::toDateTime($currentTimeStamp);
        $datetimeEnd = DateTimeHelper::toDateTime(DateTimeHelper::currentTimeStamp() + $this->seconds);

        $interval = $datetimeStart->diff($datetimeEnd);

        return $interval->format($format);
    }

    public function seconds(): int
    {
        return $this->seconds;
    }

    public function minutes(): int
    {
        return (int)floor($this->seconds / 60);
    }

    public function hours(): int
    {
        return (int)floor(($this->seconds / 60) / 60);
    }
}
