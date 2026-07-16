<?php
/**
 * Read Time plugin for Craft CMS 5.x
 *
 * Calculate the estimated read time for content.
 *
 * @link      https://github.com/jalendport
 * @copyright Copyright (c) 2018 Jalen Davenport
 */

namespace jalendport\readtime\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use jalendport\readtime\models\TimeModel;
use jalendport\readtime\ReadTime as ReadTimePlugin;

/**
 * The `ReadTime` GraphQL object type, mirroring {@see TimeModel}. It is resolved
 * from a {@see TimeModel} source produced by the read time service, so GraphQL
 * and Twig results stay in sync.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 3.1.0
 */
class ReadTimeType extends ObjectType
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the GraphQL type name. Registered with the schema via
     * {@see \jalendport\readtime\ReadTime} so it can be referenced and
     * introspected.
     *
     * @return string the GraphQL type name
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.1.0
     */
    public static function getName(): string
    {
        return 'ReadTime';
    }

    /**
     * Returns the singleton GraphQL type, creating and registering it on first
     * use. Used both when registering the type and when building the `readTime`
     * field on entry types.
     *
     * @return mixed the registered GraphQL type
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.1.0
     */
    public static function getType(): mixed
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        return GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'description' => 'The estimated read time for an entry.',
            'fields' => [
                'seconds' => [
                    'name' => 'seconds',
                    'type' => Type::int(),
                    'description' => 'The total read time, in seconds.',
                ],
                'minutes' => [
                    'name' => 'minutes',
                    'type' => Type::int(),
                    'description' => 'The total read time, in whole minutes.',
                ],
                'hours' => [
                    'name' => 'hours',
                    'type' => Type::int(),
                    'description' => 'The total read time, in whole hours.',
                ],
                'humanReadable' => [
                    'name' => 'humanReadable',
                    'type' => Type::string(),
                    'description' => 'A human-readable read time duration (e.g. “2 minutes, 40 seconds”).',
                ],
            ],
        ]));
    }

    /**
     * Returns the field definition for the `readTime` field added to entry
     * types. The resolver delegates to the read time service so no counting
     * logic is duplicated for GraphQL.
     *
     * Read time is computed by walking the entry's field layout, so it is
     * resolved on demand here — entries that do not request the field pay no
     * cost. Selecting `readTime` across a large entry query will compute it per
     * entry; callers should be mindful of that on broad queries.
     *
     * @return array<string, mixed> the `readTime` field definition
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.1.0
     */
    public static function getEntryFieldDefinition(): array
    {
        return [
            'name' => 'readTime',
            'type' => self::getType(),
            'description' => 'The estimated read time for this entry, calculated from its field layout.',
            'args' => [
                'showSeconds' => [
                    'name' => 'showSeconds',
                    'type' => Type::boolean(),
                    'defaultValue' => true,
                    'description' => 'Whether seconds are included in the `humanReadable` value.',
                ],
            ],
            'resolve' => static function(mixed $source, array $arguments): TimeModel {
                $showSeconds = $arguments['showSeconds'] ?? true;

                return ReadTimePlugin::$plugin->readTime->calculateForElement($source, $showSeconds);
            },
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * Resolves each `ReadTime` field from the {@see TimeModel} source.
     *
     * @param mixed $source the TimeModel being resolved
     * @param array<string, mixed> $arguments the field arguments
     * @param mixed $context the query context
     * @param ResolveInfo $resolveInfo the resolve info
     * @return mixed the resolved field value
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 3.1.0
     */
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var TimeModel $source */
        return match ($resolveInfo->fieldName) {
            'seconds' => $source->seconds(),
            'minutes' => $source->minutes(),
            'hours' => $source->hours(),
            'humanReadable' => $source->human(),
            default => null,
        };
    }
}
