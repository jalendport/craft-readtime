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
                'outputLocaleOptions' => $this->outputLocaleOptions(),
            ]
        );
    }

    /**
     * Builds the option list for the "Output Locale" dropdown: a blank
     * "Current language" entry, the "Content's site language" keyword, then one
     * option per configured site language. A power user can still pin an
     * off-list locale via `config/read-time.php`; the model validates against
     * Craft's full known-locale list.
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function outputLocaleOptions(): array
    {
        $options = [
            ['label' => Craft::t('read-time', 'Current language'), 'value' => ''],
            ['label' => Craft::t('read-time', 'Content’s site language'), 'value' => Settings::OUTPUT_LOCALE_SITE],
        ];

        foreach (Craft::$app->getI18n()->getSiteLocales() as $locale) {
            $options[] = [
                'label' => $locale->getDisplayName(Craft::$app->language) . ' (' . $locale->id . ')',
                'value' => $locale->id,
            ];
        }

        return $options;
    }
}
