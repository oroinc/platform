<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * This trait can be used in migrations that remove "workflowItem" and "workflowStep" fields.
 */
trait RemoveWorkflowFieldsTrait
{
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

    /**
     * @param string   $entityClass
     * @param QueryBag $queries
     */
    protected function removeConfigsForWorkflowFields($entityClass, QueryBag $queries)
    {
        $queries->addPostQuery(new RemoveFieldQuery($entityClass, 'workflowItem'));
        $queries->addPostQuery(new RemoveFieldQuery($entityClass, 'workflowStep'));
    }
}
