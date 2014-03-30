<?php

namespace Oro\Bundle\MigrationBundle\Event;

class MigrationEvents
{
    /**
     * This event is raised before a list of migrations are built.
     * You can use it to add additional migrations to the beginning of the migration list.
     *
     * @var string
     */
    const PRE_UP = 'oro_migration.pre_up';

    /**
     * This event is raised after a list of migrations are built.
     * You can use it to add additional migrations to the end of the migration list.
     *
     * @var string
     */
    const POST_UP = 'oro_migration.post_up';
}
