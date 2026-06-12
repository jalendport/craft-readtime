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

    public function __toString(): string
    {
        return $this->human();
    }

    public function human(): string
    {
        return DateTimeHelper::humanDuration($this->seconds, $this->showSeconds);
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
