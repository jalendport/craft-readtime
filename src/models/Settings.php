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

namespace jalendport\readtime\models;

use craft\base\Model;

class Settings extends Model
{
    /**
     * @var int Average reading speed, in words per minute.
     */
    public int $wordsPerMinute = 200;

    public function rules(): array
    {
        return [
            [['wordsPerMinute'], 'required'],
            [['wordsPerMinute'], 'number', 'integerOnly' => true],
        ];
    }
}
