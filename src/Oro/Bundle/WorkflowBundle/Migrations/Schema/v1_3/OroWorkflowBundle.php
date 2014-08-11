<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWorkflowBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Generate table oro_process_definition **/
        $definitionTable = $schema->createTable('oro_process_definition');
        $definitionTable->addColumn('name', 'string', ['length' => 255]);
        $definitionTable->addColumn('label', 'string', ['length' => 255]);
        $definitionTable->addColumn('enabled', 'boolean', []);
        $definitionTable->addColumn('related_entity', 'string', ['length' => 255]);
        $definitionTable->addColumn('execution_order', 'smallint', []);
        $definitionTable->addColumn('actions_configuration', 'array', ['comment' => '(DC2Type:array)']);
        $definitionTable->addColumn('created_at', 'datetime', []);
        $definitionTable->addColumn('updated_at', 'datetime', []);
        $definitionTable->setPrimaryKey(['name']);
        /** End of generate table oro_process_definition **/

        /** Generate table oro_process_trigger **/
        $triggerTable = $schema->createTable('oro_process_trigger');
        $triggerTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $triggerTable->addColumn('definition_name', 'string', ['notnull' => false, 'length' => 255]);
        $triggerTable->addColumn('event', 'string', ['length' => 255]);
        $triggerTable->addColumn('field', 'string', ['notnull' => false, 'length' => 255]);
        $triggerTable->addColumn('queued', 'boolean', []);
        $triggerTable->addColumn('time_shift', 'integer', ['notnull' => false]);
        $triggerTable->addColumn('created_at', 'datetime', []);
        $triggerTable->addColumn('updated_at', 'datetime', []);
        $triggerTable->setPrimaryKey(['id']);
        $triggerTable->addUniqueIndex(['event', 'field', 'definition_name'], 'process_trigger_unique_idx');
        $triggerTable->addIndex(['definition_name'], 'IDX_48B327BCCB9D81D2', []);
        $triggerTable->addForeignKeyConstraint(
            $definitionTable,
            ['definition_name'],
            ['name'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate table oro_process_trigger **/

        /** Generate table oro_process_job **/
        $jobTable = $schema->createTable('oro_process_job');
        $jobTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $jobTable->addColumn('process_trigger_id', 'integer', ['notnull' => false]);
        $jobTable->addColumn('entity_id', 'integer', ['notnull' => false]);
        $jobTable->addColumn('entity_hash', 'string', ['notnull' => false, 'length' => 255]);
        $jobTable->addColumn('serialized_data', 'text', []);
        $jobTable->setPrimaryKey(['id']);
        $jobTable->addIndex(['process_trigger_id'], 'IDX_1957630F196FFDE', []);
        $jobTable->addIndex(['entity_hash'], 'process_job_entity_hash_idx', []);
        $jobTable->addForeignKeyConstraint(
            $triggerTable,
            ['process_trigger_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate table oro_process_job **/
    }
}
