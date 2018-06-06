<?php
/**
 * Read Time plugin for Craft CMS 3.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/lukeyouell
 * @copyright Copyright (c) 2018 Luke Youell
 */

namespace lukeyouell\readtime\models;

use lukeyouell\readtime\ReadTime;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $wordsPerMinute = 200;

    // Public Methods
    // =========================================================================

    public function rules()
    {
        return [
            [['wordsPerMinute'], 'required'],
            [['wordsPerMinute'], 'number', 'integerOnly' => true]
        ];
    }
}
