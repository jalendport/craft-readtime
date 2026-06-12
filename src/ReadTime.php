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

namespace jalendport\readtime;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use jalendport\readtime\models\Settings;
use jalendport\readtime\services\ReadTime as ReadTimeService;
use jalendport\readtime\twigextensions\ReadTimeTwigExtension;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;

/**
 * @property-read ReadTimeService $readTime
 * @method Settings getSettings()
 */
class ReadTime extends Plugin
{
    public string $schemaVersion = '1.0.0';

    public bool $hasCpSettings = true;

    /**
     * Registers the plugin's components per the Craft 5 plugin spec.
     */
    public static function config(): array
    {
        return [
            'components' => [
                'readTime' => ReadTimeService::class,
            ],
        ];
    }

    public function init(): void
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

    /**
     * Returns the read time service.
     */
    public function getReadTime(): ReadTimeService
    {
        return $this->get('readTime');
    }

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
                'overrides' => array_keys($overrides),
            ]
        );
    }
}
