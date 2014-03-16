<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWorkflowBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // removed field "enabled"
        // added fields "system", "created_at", "updated_at"
        $workflowDefinition = $schema->getTable('oro_workflow_definition');
        $workflowDefinition->dropIndex('oro_workflow_definition_enabled_idx');
        $workflowDefinition->dropColumn('enabled');
        $workflowDefinition->addColumn('system', 'boolean');
        $workflowDefinition->addColumn('created_at', 'datetime');
        $workflowDefinition->addColumn('updated_at', 'datetime');

        // added field "final"
        $workflowStep = $schema->getTable('oro_workflow_step');
        $workflowStep->addColumn('final', 'boolean', array());

        // removed field "closed"
        // added "ON DELETE SET NULL" to workflow step relation "currentStep"
        $workflowItem = $schema->getTable('oro_workflow_item');
        $workflowItem->dropColumn('closed');
        $workflowItem->removeForeignKey('FK_169789AED9BF9B19');
        $workflowItem->addForeignKeyConstraint(
            $workflowStep,
            array('current_step_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null),
            'FK_169789AED9BF9B19'
        );

        // added "ON DELETE SET NULL" to workflow step relations "stepFrom" and "stepTo"
        $workflowTransitionLog = $schema->getTable('oro_workflow_transition_log');
        $workflowTransitionLog->removeForeignKey('FK_B3D57B30C8335FE4');
        $workflowTransitionLog->addForeignKeyConstraint(
            $workflowStep,
            array('step_from_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null),
            'FK_B3D57B30C8335FE4'
        );
        $workflowTransitionLog->removeForeignKey('FK_B3D57B302C17BD9A');
        $workflowTransitionLog->addForeignKeyConstraint(
            $workflowStep,
            array('step_to_id'),
            array('id'),
            array('onDelete' => 'SET NULL', 'onUpdate' => null),
            'FK_B3D57B302C17BD9A'
        );
    }
}
