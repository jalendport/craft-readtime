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

use yii\base\ErrorException;

class ReadTimeTwigExtension extends \Twig_Extension
{
    // Public Methods
    // =========================================================================

    public function getName()
    {
        return 'readTime';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('readTime', [$this, 'readTimeFunction']),
        ];
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('readTime', [$this, 'readTimeFilter']),
        ];
    }

    public function readTimeFunction($element, $showSeconds = true)
    {
        $settings = ReadTime::$plugin->getSettings();
        $wpm = $settings->wordsPerMinute;
        $totalSeconds = 0;

        foreach ($element->getFieldLayout()->getFields() as $field) {
            try {
                $fieldVal = $element->getFieldValue($field->handle);

                $value = is_array($fieldVal) ? implode(' ', $fieldVal) : (string)$fieldVal;
                $words = str_word_count(strip_tags($value));
                $seconds = floor($words / $wpm * 60);

                $totalSeconds = $totalSeconds + $seconds;
            } catch (ErrorException $e) {
                continue;
            }
        }

        $duration = DateTimeHelper::secondsToHumanTimeDuration($seconds, $showSeconds);

        return $duration;
    }

    public function readTimeFilter($value = null, $showSeconds = true)
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
