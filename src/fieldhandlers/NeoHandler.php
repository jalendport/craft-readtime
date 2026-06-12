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

use benf\neo\Field as NeoField;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use jalendport\readtime\base\FieldHandlerInterface;
use jalendport\readtime\services\ReadTime;

/**
 * Neo field handler (spicyweb/craft-neo).
 *
 * Neo is an optional, soft dependency: the `NeoField` reference below resolves
 * to false via `instanceof` when Neo is not installed, so the plugin loads and
 * computes read time fine without it. Neo blocks are elements with their own
 * field layouts, so we recurse into each block.
 */
class NeoHandler implements FieldHandlerInterface
{
    public function canHandle(FieldInterface $field): bool
    {
        return $field instanceof NeoField;
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
