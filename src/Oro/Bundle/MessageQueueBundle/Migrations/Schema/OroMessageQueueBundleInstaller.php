<?php

namespace Oro\Bundle\MessageQueueBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Component\MessageQueue\Job\Schema as UniqueJobSchema;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSchema;

class OroMessageQueueBundleInstaller implements Installation, DatabasePlatformAwareInterface, ConnectionAwareInterface
{
    use DatabasePlatformAwareTrait;
    use ConnectionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_11';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createDbalQueueTable($schema);
        $this->createJobTable($schema, $queries);
        $this->createUniqueJobTable($schema);
        $this->createStateTable($schema);
        $this->initializeCacheState($queries);
    }

    private function createDbalQueueTable(Schema $schema): void
    {
        $queueSchema = new DbalSchema($this->connection, 'oro_message_queue');
        $queueSchema->addToSchema($schema);
    }

    private function createUniqueJobTable(Schema $schema): void
    {
        $uniqueJobSchema = new UniqueJobSchema($this->connection, 'oro_message_queue_job_unique');
        $uniqueJobSchema->addToSchema($schema);
    }

    private function createJobTable(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->createTable('oro_message_queue_job');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('root_job_id', 'integer', ['notnull' => false]);
        $table->addColumn('owner_id', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('status', 'string', ['length' => 255]);
        $table->addColumn('interrupted', 'boolean');
        $table->addColumn('`unique`', 'boolean');
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('started_at', 'datetime', ['notnull' => false]);
        $table->addColumn('last_active_at', 'datetime', ['notnull' => false]);
        $table->addColumn('stopped_at', 'datetime', ['notnull' => false]);
        $table->addColumn('data', 'json_array', [
            'notnull' => false,
            'comment' => '(DC2Type:json_array)',
        ]);
        $table->addColumn('job_progress', 'percent', ['notnull' => false, 'precision' => 0]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['status'], 'idx_status');
        $table->addIndex(['root_job_id', 'name', 'owner_id'], 'oro_message_queue_job_inx');

        $table->addForeignKeyConstraint(
            $table,
            ['root_job_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        if ($this->platform instanceof PostgreSQL94Platform) {
            $queries->addQuery('ALTER TABLE oro_message_queue_job ALTER COLUMN data TYPE jsonb USING data::jsonb');
        }
    }

    /**
     * Adds the oro_message_queue_state table structure.
     */
    private function createStateTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_message_queue_state');
        $table->addColumn('id', 'string', ['length' => 15, 'notnull' => true]);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Adds the initial cache state data to oro_message_queue_state table.
     */
    private function initializeCacheState(QueryBag $queries): void
    {
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'INSERT INTO oro_message_queue_state (id, updated_at) VALUES (:id, :updated_at)',
                ['id' => 'cache', 'updated_at' => null],
                ['id' => Types::STRING, 'updated_at' => Types::DATETIME_MUTABLE]
            )
        );
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'INSERT INTO oro_message_queue_state (id, updated_at) VALUES (:id, :updated_at)',
                ['id' => 'consumers', 'updated_at' => new \DateTime('2000-01-01')],
                ['id' => Types::STRING, 'updated_at' => Types::DATETIME_MUTABLE]
            )
        );
    }
}
