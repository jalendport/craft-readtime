<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime;

use Craft;
use craft\base\Model;
use craft\console\Application as ConsoleApplication;
use craft\events\DefineGqlTypeFieldsEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\TypeManager;
use craft\services\Gql;
use craft\web\Application as WebApplication;
use jalendport\base\Plugin;
use jalendport\readtime\gql\types\ReadTimeType;
use jalendport\readtime\models\Settings;
use jalendport\readtime\services\ReadTime as ReadTimeService;
use jalendport\readtime\twigextensions\ReadTimeTwigExtension;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Event;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Read Time plugin.
 *
 * @property-read ReadTimeService $readTime
 * @method Settings getSettings()
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class ReadTime extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var ReadTime The plugin instance.
     * @since 3.2.0
     */
    public static ReadTime $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var bool Whether the plugin has a settings page in the control panel.
     * @since 1.0.0
     */
    public bool $hasCpSettings = true;

    /**
     * @var string The plugin's schema version.
     * @since 3.0.0
     */
    public string $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * Registers the plugin's components per the Craft 5 plugin spec.
     *
     * @return array<string, mixed>
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    public static function config(): array
    {
        return [
            'components' => [
                'readTime' => ReadTimeService::class,
            ],
        ];
    }

    /**
     * Returns the read time service.
     *
     * @return ReadTimeService the read time service
     * @deprecated in 3.2.0. Use [[$plugin]]->readTime instead.
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.0.0
     */
    public function getReadTime(): ReadTimeService
    {
        /** @var ReadTimeService $service */
        $service = $this->get('readTime');

        return $service;
    }

    /**
     * @inheritdoc
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        $this->_registerTwigExtensions();
        $this->_registerGqlSupport();
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws InvalidConfigException if the settings model can't be created
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    /**
     * @inheritdoc
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws Exception
     * @throws LoaderError
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    protected function settingsHtml(): ?string
    {
        $settings = $this->getSettings();
        $settings->validate();

        /** @var WebApplication $app */
        $app = Craft::$app;

        return $app->getView()->renderTemplate('read-time/settings', [
            'settings' => $settings,
            'overrides' => $this->getConfigOverrides(),
            'outputLocaleOptions' => $settings->getOutputLocaleOptions(),
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Registers the `ReadTime` GraphQL type and adds a `readTime` field to entry
     * types, resolving from the read time service so GraphQL and Twig stay in
     * sync. Read time is computed on demand in the resolver, so entries that
     * don't request the field pay no cost.
     *
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.1.0
     */
    private function _registerGqlSupport(): void
    {
        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_TYPES,
            static function(RegisterGqlTypesEvent $event): void {
                $event->types[] = ReadTimeType::class;
            }
        );

        // Adding the field to the entry interface propagates it to every
        // concrete entry type.
        Event::on(
            TypeManager::class,
            TypeManager::EVENT_DEFINE_GQL_TYPE_FIELDS,
            static function(DefineGqlTypeFieldsEvent $event): void {
                if ($event->typeName !== EntryInterface::getName()) {
                    return;
                }

                $event->fields['readTime'] = ReadTimeType::getEntryFieldDefinition();
            }
        );
    }

    /**
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private function _registerTwigExtensions(): void
    {
        /** @var WebApplication|ConsoleApplication $app */
        $app = Craft::$app;

        $app->getView()->registerTwigExtension(new ReadTimeTwigExtension());
    }
}
