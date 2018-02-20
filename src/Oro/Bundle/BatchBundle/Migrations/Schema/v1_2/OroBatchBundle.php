<?php

namespace Oro\Bundle\BatchBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroBatchBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createAkeneoBatchWarningTable($schema);
        $this->updateAkeneoBatchStepExecutionTable($schema);
        $this->updateAkeneoBatchJobExecutionTable($schema);

        $this->addAkeneoBatchWarningForeignKeys($schema);
    }

    /**
     * Create akeneo_batch_warning table
     *
     * @param Schema $schema
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
     * Update akeneo_batch_step_execution table
     *
     * @param Schema $schema
     */
    protected function updateAkeneoBatchStepExecutionTable(Schema $schema)
    {
        $table = $schema->getTable('akeneo_batch_step_execution');
        $table->dropColumn('warnings');
    }

    /**
     * Update akeneo_batch_job_execution table
     *
     * @param Schema $schema
     */
    protected function updateAkeneoBatchJobExecutionTable(Schema $schema)
    {
        $table = $schema->getTable('akeneo_batch_job_execution');
        $table->addColumn('pid', 'integer', ['notnull' => false]);
        $table->addColumn('user', 'string', ['notnull' => false, 'length' => 255]);
    }

    /**
     * Add akeneo_batch_warning foreign keys.
     *
     * @param Schema $schema
     */
    protected function addAkeneoBatchWarningForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('akeneo_batch_warning');
        $table->addForeignKeyConstraint(
            $schema->getTable('akeneo_batch_step_execution'),
            ['step_execution_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_8EE0AE736C7DA296'
        );
    }
}
