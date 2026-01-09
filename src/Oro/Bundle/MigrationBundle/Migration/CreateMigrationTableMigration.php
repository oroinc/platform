<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

/**
 * Creates the migration tracking table in the database.
 *
 * This migration creates the `oro_migrations` table that is used to track which migrations
 * have been applied to each bundle. The table stores the bundle name, migration version,
 * and the timestamp when the migration was loaded. This is a core migration that is
 * executed first to initialize the migration tracking system.
 */
class CreateMigrationTableMigration implements Migration
{
    public const MIGRATION_TABLE = 'oro_migrations';

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable(self::MIGRATION_TABLE);
        $table->addColumn('id', 'integer', ['notnull' => true, 'autoincrement' => true]);
        $table->addColumn('bundle', 'string', ['notnull' => true, 'length' => 250]);
        $table->addColumn('version', 'string', ['notnull' => true, 'length' => 250]);
        $table->addColumn('loaded_at', 'datetime', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['bundle'], 'idx_oro_migrations', []);
    }
}
