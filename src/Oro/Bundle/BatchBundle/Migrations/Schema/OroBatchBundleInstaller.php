<?php

namespace Oro\Bundle\BatchBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroBatchBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_4';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createAkeneoBatchJobExecutionTable($schema);
        $this->createAkeneoBatchJobInstanceTable($schema);
        $this->createAkeneoBatchStepExecutionTable($schema);
        $this->createAkeneoBatchWarningTable($schema);

        /** Foreign keys generation **/
        $this->addAkeneoBatchJobExecutionForeignKeys($schema);
        $this->addAkeneoBatchStepExecutionForeignKeys($schema);
        $this->addAkeneoBatchWarningForeignKeys($schema);
    }

    /**
     * Create akeneo_batch_job_execution table
     */
    protected function createAkeneoBatchJobExecutionTable(Schema $schema)
    {
        $table = $schema->createTable('akeneo_batch_job_execution');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('job_instance_id', 'integer', []);
        $table->addColumn('status', 'integer', []);
        $table->addColumn('start_time', 'datetime', ['notnull' => false]);
        $table->addColumn('end_time', 'datetime', ['notnull' => false]);
        $table->addColumn('create_time', 'datetime', ['notnull' => false]);
        $table->addColumn('updated_time', 'datetime', ['notnull' => false]);
        $table->addColumn('exit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('exit_description', 'text', ['notnull' => false]);
        $table->addColumn('failure_exceptions', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('log_file', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pid', 'integer', ['notnull' => false]);
        $table->addColumn('user', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['job_instance_id'], 'IDX_66BCFEA7593D6954', []);
    }

    /**
     * Create akeneo_batch_job_instance table
     */
    protected function createAkeneoBatchJobInstanceTable(Schema $schema)
    {
        $table = $schema->createTable('akeneo_batch_job_instance');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 100]);
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('alias', 'string', ['length' => 50]);
        $table->addColumn('status', 'integer', []);
        $table->addColumn('connector', 'string', ['length' => 255]);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('rawConfiguration', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_35B1ECC777153098');
    }

    /**
     * Create akeneo_batch_step_execution table
     */
    protected function createAkeneoBatchStepExecutionTable(Schema $schema)
    {
        $table = $schema->createTable('akeneo_batch_step_execution');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('job_execution_id', 'integer', ['notnull' => false]);
        $table->addColumn('step_name', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('status', 'integer', []);
        $table->addColumn('read_count', 'integer', []);
        $table->addColumn('write_count', 'integer', []);
        $table->addColumn('start_time', 'datetime', ['notnull' => false]);
        $table->addColumn('end_time', 'datetime', ['notnull' => false]);
        $table->addColumn('exit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('exit_description', 'text', ['notnull' => false]);
        $table->addColumn('terminate_only', 'boolean', ['notnull' => false]);
        $table->addColumn('failure_exceptions', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('errors', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('summary', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['job_execution_id'], 'IDX_3B30CD3C5871C06B', []);
    }

    /**
     * Create akeneo_batch_warning table
     */
    protected function createAkeneoBatchWarningTable(Schema $schema)
    {
        $table = $schema->createTable('akeneo_batch_warning');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('step_execution_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('reason', 'text', ['notnull' => false]);
        $table->addColumn('reason_parameters', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('item', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['step_execution_id'], 'IDX_8EE0AE736C7DA296', []);
    }

    /**
     * Add akeneo_batch_job_execution foreign keys.
     */
    protected function addAkeneoBatchJobExecutionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('akeneo_batch_job_execution');
        $table->addForeignKeyConstraint(
            $schema->getTable('akeneo_batch_job_instance'),
            ['job_instance_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add akeneo_batch_mapping_field foreign keys.
     */
    protected function addAkeneoBatchStepExecutionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('akeneo_batch_step_execution');
        $table->addForeignKeyConstraint(
            $schema->getTable('akeneo_batch_job_execution'),
            ['job_execution_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add akeneo_batch_warning foreign keys.
     */
    protected function addAkeneoBatchWarningForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('akeneo_batch_warning');
        $table->addForeignKeyConstraint(
            $schema->getTable('akeneo_batch_step_execution'),
            ['step_execution_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
