<?php

namespace Oro\Bundle\MigrationBundle\Event;

/**
 * Migration events list
 */
class MigrationEvents
{
    /**
     * This event is raised before a list of migrations are built.
     * You can use it to add additional migrations to the beginning of the migration list.
     *
     * @var string
     */
    public const PRE_UP = 'oro_migration.pre_up';

    /**
     * This event is raised after a list of migrations are built.
     * You can use it to add additional migrations to the end of the migration list.
     *
     * @var string
     */
    public const POST_UP = 'oro_migration.post_up';

    /**
     * This event is raised after migrations` 'up' method call.
     */
    public const MIGRATION_POST_UP = 'oro_migration.migration.post_up';

    /**
     * This event is raised before data fixtures are loaded.
     *
     * @var string
     */
    public const DATA_FIXTURES_PRE_LOAD = 'oro_migration.data_fixtures.pre_load';

    /**
     * This event is raised after data fixtures are loaded.
     *
     * @var string
     */
    public const DATA_FIXTURES_POST_LOAD = 'oro_migration.data_fixtures.post_load';
}
