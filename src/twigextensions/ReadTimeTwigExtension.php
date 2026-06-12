<?php
/**
 * Read Time plugin for Craft CMS 4.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime\twigextensions;

use benf\neo\elements\Block as NeoBlock;
use benf\neo\Field as NeoField;
use Craft;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\errors\InvalidFieldException;
use craft\fields\Matrix;
use craft\helpers\StringHelper;
use jalendport\readtime\models\Settings;
use jalendport\readtime\ReadTime;
use jalendport\readtime\models\TimeModel;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use verbb\supertable\fields\SuperTableField;
use yii\base\ErrorException;

class ReadTimeTwigExtension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    public function getName(): string
	{
        return 'readTime';
    }

    public function getFunctions(): array
	{
        return [
            new TwigFunction('readTime', [$this, 'readTimeFunction']),
        ];
    }

    public function getFilters(): array
	{
        return [
            new TwigFilter('readTime', [$this, 'readTimeFilter']),
        ];
    }

	public function readTimeFunction($element, $showSeconds = true): TimeModel
	{
        $totalSeconds = 0;

        if ($element instanceof Entry) {
            // Provided value is an entry

            foreach ($element->getFieldLayout()->getCustomFields() as $field) {
                try {
                    // If field is a matrix or neo field then loop through fields in block
                    if ($field instanceof Matrix || $field instanceof NeoField) {
                        foreach($element->getFieldValue($field->handle)->all() as $block) {
                            $blockFields = $block->getFieldLayout()->getCustomFields();

                            foreach ($blockFields as $blockField) {
                                $value = $block->getFieldValue($blockField->handle);
                                $seconds = $this->valToSeconds($value);
                                $totalSeconds = $totalSeconds + $seconds;
                            }
                        }
                    } elseif($field instanceof SuperTableField) {
                        foreach($element->getFieldValue($field->handle)->all() as $block) {
                            $blockFields = $block->getFieldLayout()->getCustomFields();

                            foreach ($blockFields as $blockField) {
                                if ($blockField instanceof Matrix) {
                                    foreach($block->getFieldValue($blockField->handle)->all() as $matrix) {
                                        $matrixFields = $matrix->getFieldLayout()->getCustomFields();

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
                } catch (ErrorException | InvalidFieldException $e) {
                    continue;
                }
            }
        } elseif(is_array($element)) {
            // Provided value is a matrix or neo field
            Craft::info('matrix or neo field provided', 'readtime');

            foreach ($element as $block) {
                if ($block instanceof MatrixBlock || $block instanceof NeoBlock) {
                    $blockFields = $block->getFieldLayout()->getCustomFields();

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

    public function readTimeFilter($value = null, $showSeconds = true): TimeModel
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

    private function valToSeconds($value): float
	{
		/** @var Settings $settings */
        $settings = ReadTime::getInstance()->getSettings();
        $wpm = $settings->wordsPerMinute;

        $string = StringHelper::toString($value);
        $wordCount = StringHelper::countWords($string);
        $seconds = floor($wordCount / $wpm * 60);

        return $seconds;
    }
}
