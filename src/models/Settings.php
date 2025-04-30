<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime\models;

use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public int $wordsPerMinute = 200;

    // Public Methods
    // =========================================================================

    public function rules(): array
    {
        return [
            [['wordsPerMinute'], 'required'],
            [['wordsPerMinute'], 'number', 'integerOnly' => true]
        ];
    }
}
