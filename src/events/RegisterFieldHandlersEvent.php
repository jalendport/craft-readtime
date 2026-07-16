<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime\events;

use jalendport\readtime\base\FieldHandlerInterface;
use yii\base\Event;

/**
 * Fired so other plugins/modules can register read time handlers for additional
 * field types.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 3.0.0
 */
class RegisterFieldHandlersEvent extends Event
{
    /**
     * @var FieldHandlerInterface[] The registered field handlers, in priority order.
     * @since 3.0.0
     */
    public array $handlers = [];
}
