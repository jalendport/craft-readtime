<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime\twigextensions;

use jalendport\readtime\models\TimeModel;
use jalendport\readtime\ReadTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Thin Twig wrapper. All counting/field-walking logic lives in the
 * {@see \jalendport\readtime\services\ReadTime} service.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class ReadTimeTwigExtension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('readTime', [$this, 'readTimeFilter']),
        ];
    }

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('readTime', [$this, 'readTimeFunction']),
        ];
    }

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function getName(): string
    {
        return 'readTime';
    }

    /**
     * Backs the `|readTime` Twig filter.
     *
     * @param mixed $value the value to count
     * @param bool $showSeconds whether seconds are included in the human-readable duration
     * @return TimeModel the resulting read time
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function readTimeFilter(mixed $value = null, bool $showSeconds = true): TimeModel
    {
        return ReadTime::$plugin->readTime->calculateForValue($value, $showSeconds);
    }

    /**
     * Backs the `readTime()` Twig function.
     *
     * @param mixed $element the element, element query, or raw value to count
     * @param bool $showSeconds whether seconds are included in the human-readable duration
     * @return TimeModel the resulting read time
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function readTimeFunction(mixed $element, bool $showSeconds = true): TimeModel
    {
        return ReadTime::$plugin->readTime->calculateForElement($element, $showSeconds);
    }
}
