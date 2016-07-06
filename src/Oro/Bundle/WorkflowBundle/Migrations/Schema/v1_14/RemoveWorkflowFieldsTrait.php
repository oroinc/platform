<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Table;

trait RemoveWorkflowFieldsTrait
{
    /**
     * @param Table $table
     */
    protected function removeWorkflowFields(Table $table)
    {
        $workflowTables = [
            'oro_workflow_item',
            'oro_workflow_step'
        ];

        foreach ($table->getForeignKeys() as $foreignKey) {
            if (!in_array($foreignKey->getForeignTableName(), $workflowTables, true)) {
                continue;
            }
            
            $table->removeForeignKey($foreignKey->getName());
            foreach ($foreignKey->getLocalColumns() as $column) {
                $table->dropColumn($column);
            }
        }
    }
}
