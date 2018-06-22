<?php
/**
 * Read Time plugin for Craft CMS 3.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/lukeyouell
 * @copyright Copyright (c) 2018 Luke Youell
 */

namespace lukeyouell\readtime\models;

use lukeyouell\readtime\ReadTime;

use Craft;
use craft\base\Model;
use craft\helpers\DateTimeHelper;

class TimeModel extends Model
{
    // Public Properties
    // =========================================================================

    public $seconds = 0;

    public $showSeconds = true;

    // Public Methods
    // =========================================================================

    public function __toString()
    {
        return (string) $this->human();
    }

    public function human()
    {
        return DateTimeHelper::secondsToHumanTimeDuration($this->seconds, $this->showSeconds);
    }

    public function interval($format = '%h hours, %i minutes, %s seconds')
    {
        $currentTimeStamp = DateTimeHelper::currentTimeStamp();
        $datetimeStart = DateTimeHelper::toDateTime($currentTimeStamp);
        $datetimeEnd = DateTimeHelper::toDateTime(DateTimeHelper::currentTimeStamp() + $this->seconds);

        $interval = $datetimeStart->diff($datetimeEnd);

        return $interval->format($format);
    }

    public function seconds()
    {
        return $this->seconds;
    }

    public function minutes()
    {
        return floor($this->seconds / 60);
    }

    public function hours()
    {
        return floor(($this->seconds /  60) / 60);
    }
}
