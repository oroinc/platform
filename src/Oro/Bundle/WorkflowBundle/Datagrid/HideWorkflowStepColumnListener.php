<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

/**
 * Hide workflow step column
 */
class HideWorkflowStepColumnListener
{
    public function onBuildBefore(BuildBefore $event): void
    {
        $config = $event->getConfig();
        $columns = $config->offsetGetByPath('[columns]', []);
        if (!\array_key_exists(WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN, $columns)) {
            return;
        }

        $columns[WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN]['renderable'] = false;
        $config->offsetSetByPath('[columns]', $columns);
    }
}
