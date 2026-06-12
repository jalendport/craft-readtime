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

namespace jalendport\readtime\twigextensions;

use jalendport\readtime\models\TimeModel;
use jalendport\readtime\ReadTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Thin Twig wrapper. All counting/field-walking logic lives in the
 * {@see \jalendport\readtime\services\ReadTime} service.
 */
class ReadTimeTwigExtension extends AbstractExtension
{
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

    public function readTimeFunction(mixed $element, bool $showSeconds = true): TimeModel
    {
        return ReadTime::getInstance()->getReadTime()->calculateForElement($element, $showSeconds);
    }

    public function readTimeFilter(mixed $value = null, bool $showSeconds = true): TimeModel
    {
        return ReadTime::getInstance()->getReadTime()->calculateForValue($value, $showSeconds);
    }
}
