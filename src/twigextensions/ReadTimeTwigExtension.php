<?php
/**
 * Read Time plugin for Craft CMS 3.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/lukeyouell
 * @copyright Copyright (c) 2018 Luke Youell
 */

namespace lukeyouell\readtime\twigextensions;

use lukeyouell\readtime\ReadTime;

use Craft;
use craft\helpers\DateTimeHelper;

class ReadTimeTwigExtension extends \Twig_Extension
{
    // Public Methods
    // =========================================================================

    public function getName()
    {
        return 'readTime';
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('readTime', [$this, 'readTime']),
        ];
    }

    public function readTime($value = null, $showSeconds = true)
    {
        $settings = ReadTime::$plugin->getSettings();

        $value = is_array($value) ? implode(' ', $value) : (string)$value;
        $wpm = $settings->wordsPerMinute;

        $words = str_word_count(strip_tags($value));
        $seconds = floor($words / $wpm * 60);

        $duration = DateTimeHelper::secondsToHumanTimeDuration($seconds, $showSeconds);

        return $duration;
    }
}
