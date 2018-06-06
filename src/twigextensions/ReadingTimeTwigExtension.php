<?php
/**
 * Reading Time plugin for Craft CMS 3.x
 *
 * Calculate the estimated reading time for content.
 *
 * @link      https://github.com/lukeyouell
 * @copyright Copyright (c) 2018 Luke Youell
 */

namespace lukeyouell\readingtime\twigextensions;

use lukeyouell\readingtime\ReadingTime;

use Craft;
use craft\helpers\DateTimeHelper;

class ReadingTimeTwigExtension extends \Twig_Extension
{
    // Public Methods
    // =========================================================================

    public function getName()
    {
        return 'readingTime';
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('readingTime', [$this, 'readingTime']),
        ];
    }

    public function readingTime($value = null, $showSeconds = true)
    {
        $settings = ReadingTime::$plugin->getSettings();

        $value = is_array($value) ? implode(' ', $value) : (string)$value;
        $wpm = $settings->wordsPerMinute;

        $words = str_word_count(strip_tags($value));
        $seconds = floor($words / $wpm * 60);

        $duration = DateTimeHelper::secondsToHumanTimeDuration($seconds, $showSeconds);

        return $duration;
    }
}
