<?php

namespace Oro\Bundle\MigrationBundle\Event;

/**
 * Represents an event dispatched after migrations have been executed.
 *
 * This event is triggered after all migrations have completed, allowing listeners to perform
 * post-migration cleanup, validation, or other operations that depend on the migration process
 * being complete.
 */
class PostMigrationEvent extends MigrationEvent
{
}
