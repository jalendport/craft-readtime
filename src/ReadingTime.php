<?php
/**
 * Reading Time plugin for Craft CMS 3.x
 *
 * Calculate the estimated reading time for content.
 *
 * @link      https://github.com/lukeyouell
 * @copyright Copyright (c) 2018 Luke Youell
 */

namespace lukeyouell\readingtime;

use lukeyouell\readingtime\models\Settings;
use lukeyouell\readingtime\twigextensions\ReadingTimeTwigExtension;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;

use yii\base\Event;

class ReadingTime extends Plugin
{
    // Static Properties
    // =========================================================================

    public static $plugin;

    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Craft::$app->view->registerTwigExtension(new ReadingTimeTwigExtension());

        Craft::info(
            Craft::t(
                'reading-time',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel()
    {
        return new Settings();
    }

    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'reading-time/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
