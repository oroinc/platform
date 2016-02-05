<?php

namespace Oro\Bundle\CronBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCronBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_3';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createJmsJobDependenciesTable($schema);
        $this->createJmsJobRelatedEntitiesTable($schema);
        $this->createJmsJobStatisticsTable($schema);
        $this->createJmsJobsTable($schema);
        $this->createOroCronScheduleTable($schema);

        /** Foreign keys generation **/
        $this->addJmsJobDependenciesForeignKeys($schema);
        $this->addJmsJobRelatedEntitiesForeignKeys($schema);
        $this->addJmsJobsForeignKeys($schema);
    }

    /**
     * Create jms_job_dependencies table
     *
     * @param Schema $schema
     */
    protected function createJmsJobDependenciesTable(Schema $schema)
    {
        $table = $schema->createTable('jms_job_dependencies');
        $table->addColumn('source_job_id', 'bigint', ['unsigned' => true]);
        $table->addColumn('dest_job_id', 'bigint', ['unsigned' => true]);
        $table->setPrimaryKey(['source_job_id', 'dest_job_id']);
        $table->addIndex(['source_job_id'], 'IDX_8DCFE92CBD1F6B4F', []);
        $table->addIndex(['dest_job_id'], 'IDX_8DCFE92C32CF8D4C', []);
    }

    /**
     * Create jms_job_related_entities table
     *
     * @param Schema $schema
     */
    protected function createJmsJobRelatedEntitiesTable(Schema $schema)
    {
        $table = $schema->createTable('jms_job_related_entities');
        $table->addColumn('job_id', 'bigint', ['unsigned' => true]);
        $table->addColumn('related_class', 'string', ['length' => 150]);
        $table->addColumn('related_id', 'string', ['length' => 100]);
        $table->setPrimaryKey(['job_id', 'related_class', 'related_id']);
        $table->addIndex(['job_id'], 'IDX_E956F4E2BE04EA9', []);
    }

    /**
     * Create jms_job_statistics table
     *
     * @param Schema $schema
     */
    protected function createJmsJobStatisticsTable(Schema $schema)
    {
        $table = $schema->createTable('jms_job_statistics');
        $table->addColumn('job_id', 'bigint', ['unsigned' => true]);
        $table->addColumn('characteristic', 'string', ['length' => 30]);
        $table->addColumn('createdAt', 'datetime', []);
        $table->addColumn('charValue', 'float', []);
        $table->setPrimaryKey(['job_id', 'characteristic', 'createdAt']);
    }

    /**
     * Create jms_jobs table
     *
     * @param Schema $schema
     */
    protected function createJmsJobsTable(Schema $schema)
    {
        $table = $schema->createTable('jms_jobs');
        $table->addColumn('id', 'bigint', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('state', 'string', ['length' => 15]);
        $table->addColumn('createdAt', 'datetime', []);
        $table->addColumn('startedAt', 'datetime', ['notnull' => false]);
        $table->addColumn('checkedAt', 'datetime', ['notnull' => false]);
        $table->addColumn('executeAfter', 'datetime', ['notnull' => false]);
        $table->addColumn('closedAt', 'datetime', ['notnull' => false]);
        $table->addColumn('command', 'string', ['length' => 255]);
        $table->addColumn('args', 'json_array', []);
        $table->addColumn('output', 'text', ['notnull' => false]);
        $table->addColumn('errorOutput', 'text', ['notnull' => false]);
        $table->addColumn('exitCode', 'smallint', ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('maxRuntime', 'smallint', ['unsigned' => true]);
        $table->addColumn('maxRetries', 'smallint', ['unsigned' => true]);
        $table->addColumn('stackTrace', 'jms_job_safe_object', ['notnull' => false]);
        $table->addColumn('runtime', 'smallint', ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('memoryUsage', 'integer', ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('memoryUsageReal', 'integer', ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('originalJob_id', 'bigint', ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('queue', 'string', ['length' => Job::MAX_QUEUE_LENGTH]);
        $table->addColumn('priority', 'smallint', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['originalJob_id'], 'IDX_704ADB9349C447F1', []);
        $table->addIndex(['command'], 'cmd_search_index', []);
        $table->addIndex(['state', 'priority', 'id'], 'sorting_index', []);
    }

    /**
     * Create oro_cron_schedule table
     *
     * @param Schema $schema
     */
    protected function createOroCronScheduleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cron_schedule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('command', 'string', ['length' => 255]);
        $table->addColumn('args', 'json_array', []);
        $table->addColumn('args_hash', 'string', ['length' => 32]);
        $table->addColumn('definition', 'string', ['notnull' => false, 'length' => 100]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['command', 'args_hash', 'definition'], 'UQ_COMMAND');
    }

    /**
     * Add jms_job_dependencies foreign keys.
     *
     * @param Schema $schema
     */
    protected function addJmsJobDependenciesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('jms_job_dependencies');
        $table->addForeignKeyConstraint(
            $schema->getTable('jms_jobs'),
            ['dest_job_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('jms_jobs'),
            ['source_job_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add jms_job_related_entities foreign keys.
     *
     * @param Schema $schema
     */
    protected function addJmsJobRelatedEntitiesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('jms_job_related_entities');
        $table->addForeignKeyConstraint(
            $schema->getTable('jms_jobs'),
            ['job_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add jms_jobs foreign keys.
     *
     * @param Schema $schema
     */
    protected function addJmsJobsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('jms_jobs');
        $table->addForeignKeyConstraint(
            $schema->getTable('jms_jobs'),
            ['originalJob_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
