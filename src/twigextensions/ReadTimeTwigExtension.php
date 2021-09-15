<?php
/**
 * Read Time plugin for Craft CMS 3.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime\twigextensions;

use jalendport\readtime\ReadTime;
use jalendport\readtime\models\TimeModel;

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
        $totalSeconds = 0;
        $vals = '';

        if ($element instanceof \craft\elements\Entry) {
            // Provided value is an entry

            foreach ($element->getFieldLayout()->getFields() as $field) {
                try {
                    // If field is a matrix then loop through fields in block
                    if ($field instanceof \craft\fields\Matrix || $field instanceof \benf\neo\Field) {
                        foreach($element->getFieldValue($field->handle)->all() as $block) {
                            $blockFields = $block->getFieldLayout()->getFields();

                            foreach ($blockFields as $blockField) {
                                $value = $block->getFieldValue($blockField->handle);
                                $seconds = $this->valToSeconds($value);
                                $totalSeconds = $totalSeconds + $seconds;
                            }
                        }
                    } elseif($field instanceof \verbb\supertable\fields\SuperTableField) {
                        foreach($element->getFieldValue($field->handle)->all() as $block) {
                            $blockFields = $block->getFieldLayout()->getFields();

                            foreach ($blockFields as $blockField) {
                                if ($blockField instanceof \craft\fields\Matrix) {
                                    foreach($block->getFieldValue($blockField->handle)->all() as $matrix) {
                                        $matrixFields = $matrix->getFieldLayout()->getFields();

                                        foreach ($matrixFields as $matrixField) {
                                            $value = $matrix->getFieldValue($matrixField->handle);
                                            $seconds = $this->valToSeconds($value);
                                            $totalSeconds = $totalSeconds + $seconds;
                                        }
                                    }
                                } else {
                                    $value = $block->getFieldValue($blockField->handle);
                                    $seconds = $this->valToSeconds($value);
                                    $totalSeconds = $totalSeconds + $seconds;
                                }
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
        } elseif(is_array($element)) {
            // Provided value is a matrix or neo field
            Craft::info('matrix or neo field provided', 'readtime');

            foreach ($element as $block) {
                if ($block instanceof \craft\elements\MatrixBlock || $block instanceof \benf\neo\elements\Block) {
                    $blockFields = $block->getFieldLayout()->getFields();

                    foreach ($blockFields as $blockField) {
                        $value = $block->getFieldValue($blockField->handle);
                        $seconds = $this->valToSeconds($value);
                        $totalSeconds = $totalSeconds + $seconds;
                    }
                }
            }
        }

        $data = [
            'seconds'     => $totalSeconds,
            'showSeconds' => $showSeconds,
        ];

        return new TimeModel($data);
    }

    public function readTimeFilter($value = null, $showSeconds = true)
    {
        $seconds = $this->valToSeconds($value);

        $data = [
            'seconds'     => $seconds,
            'showSeconds' => $showSeconds,
        ];

        return new TimeModel($data);
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
