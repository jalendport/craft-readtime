<?php
/**
 * Read Time plugin for Craft CMS 4.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use jalendport\readtime\models\Settings;
use jalendport\readtime\twigextensions\ReadTimeTwigExtension;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;

class ReadTime extends Plugin
{
    // Public Properties
    // =========================================================================

    public string $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        Craft::$app->view->registerTwigExtension(new ReadTimeTwigExtension());

        Craft::info(
            Craft::t(
                'read-time',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

	/**
	 * @throws SyntaxError
	 * @throws RuntimeError
	 * @throws Exception
	 * @throws LoaderError
	 */
	protected function settingsHtml(): ?string
    {
        // Get and pre-validate the settings
        $settings = $this->getSettings();
        $settings->validate();

        // Get the settings that are being defined by the config file
        $overrides = Craft::$app->getConfig()->getConfigFromFile(strtolower($this->handle));

        return Craft::$app->view->renderTemplate(
            'read-time/settings',
            [
                'settings' => $settings,
                'overrides' => array_keys($overrides)
            ]
        );
    }
}
