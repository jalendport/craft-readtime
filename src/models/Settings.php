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

    /**
     * @var int Minimum read time, in whole minutes. When greater than 0, read
     * times are rounded up to at least this many minutes. 0 (the default)
     * preserves Craft's "less than a minute" output for sub-minute content.
     */
    public int $minimumReadTime = 0;

    public function rules(): array
    {
        return [
            [['wordsPerMinute'], 'required'],
            [['wordsPerMinute'], 'number', 'integerOnly' => true],
            [['minimumReadTime'], 'number', 'integerOnly' => true, 'min' => 0],
        ];
    }
}
