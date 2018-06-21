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
use craft\helpers\StringHelper;

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
			new \Twig_SimpleFunction('readTimeMin', [$this, 'readTimeMinFunction']),
        ];
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('readTime', [$this, 'readTimeFilter']),
        ];
    }

	private function calcReadTime($element)
	{
		$totalSeconds = 0;
        $vals = '';

        foreach ($element->getFieldLayout()->getFields() as $field) {
            try {
                // If field is a matrix then loop through fields in block
                if ($field instanceof craft\fields\Matrix) {
                    foreach($element->getFieldValue($field->handle)->all() as $block) {
                        $blockFields = $block->getFieldLayout()->getFields();

                        foreach($blockFields as $blockField){
                            $value = $block->getFieldValue($blockField->handle);
                            $seconds = $this->valToSeconds($value);
                            $totalSeconds = $totalSeconds + $seconds;
                        }
                    }
                } else {
                  $value = $element->getFieldValue($field->handle);
                  $seconds = $this->valToSeconds($value);
                  $totalSeconds = $totalSeconds + $seconds;
                }
            } catch (ErrorException $e) {
                continue;
            }
        }

		return $totalSeconds;
	}

    public function readTimeFunction($element, $showSeconds = true)
    {
        $totalSeconds = $this->calcReadTime($element);

        $duration = DateTimeHelper::secondsToHumanTimeDuration($totalSeconds, $showSeconds);

        return $duration;
    }

	public function readTimeMinFunction($element)
	{
		$totalSeconds = $this->calcReadTime($element);
		return round($totalSeconds / 60);
	}

    public function readTimeFilter($value = null, $showSeconds = true)
    {
        $seconds = $this->valToSeconds($value);
        $duration = DateTimeHelper::secondsToHumanTimeDuration($seconds, $showSeconds);

        return $duration;
    }

    // Private Methods
    // =========================================================================

    private function valToSeconds($value)
    {
        $settings = ReadTime::$plugin->getSettings();
        $wpm = $settings->wordsPerMinute;

        $string = StringHelper::toString($value);
        $wordCount = StringHelper::countWords($string);
        $seconds = floor($wordCount / $wpm * 60);

        return $seconds;
    }
}
