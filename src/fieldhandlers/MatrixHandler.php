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

namespace jalendport\readtime\fieldhandlers;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\fields\Matrix;
use jalendport\readtime\base\FieldHandlerInterface;
use jalendport\readtime\services\ReadTime;

/**
 * Native Matrix field handler.
 *
 * In Craft 5 Matrix was "entrified": blocks are now {@see \craft\elements\Entry}
 * elements with their own field layouts, rather than the old `MatrixBlock`
 * elements. We walk each block entry's custom fields recursively, which also
 * covers nested-block fields inside a Matrix block.
 */
class MatrixHandler implements FieldHandlerInterface
{
    public function canHandle(FieldInterface $field): bool
    {
        return $field instanceof Matrix;
    }

    public function getReadTimeSeconds(ElementInterface $element, FieldInterface $field, ReadTime $service): int
    {
        $seconds = 0;

        foreach ($service->getBlocks($element, $field) as $block) {
            $seconds += $service->secondsForElement($block);
        }

        return $seconds;
    }
}
