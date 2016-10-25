<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;

class WorkflowDatagridLabelListener
{
    public function __construct(WorkflowTranslationHelper $workflowTranslationHelper)
    {
        $this->workflowTranslationHelper = $workflowTranslationHelper;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $configuration = $event->getConfig();
        $columns = $this->getWorkflowLabelColumns($configuration);
        if (count($columns)) {
            foreach ($columns as $columnName) {
                $this->fixColumnDefinition($columnName, $configuration);
                $this->fixColumnFilter($columnName, $configuration);
                $this->fixColumnSorter($columnName, $configuration);
            }
        }
    }

    /**
     * @param DatagridConfiguration $configuration
     *
     * @return array
     */
    private function getWorkflowLabelColumns(DatagridConfiguration $configuration)
    {
        $columnAliases = $configuration->offsetGetByPath('[source][query_config][column_aliases]');
        $columns = [];
        if (count($columnAliases)) {
            foreach ($columnAliases as $key => $alias) {
                if (strstr($key, WorkflowStep::class . '::label') !== false) {
                    $columns[] = $alias;
                    continue;
                }
            }
        }

        return $columns;
    }

    private function fixColumnDefinition($columnName, DatagridConfiguration $configuration)
    {
        $path = sprintf('[columns][%s]', $columnName);
        $column = $configuration->offsetGetByPath($path);
        $column['frontend_type'] = 'html';
        $column['type'] = 'callback';
        $column['callable'] = [$this->workflowTranslationHelper, "findTranslation"];
        $column['params'] = [$columnName];
        $configuration->offsetSetByPath($path, $column);
    }

    private function fixColumnFilter($columnName, DatagridConfiguration $configuration)
    {
        $filters = $configuration->offsetGetByPath('[filters][columns]');
        if (isset($filters[$columnName])) {
            unset($filters[$columnName]);
        }
        $configuration->offsetSetByPath('[filters][columns]', $filters);
    }

    private function fixColumnSorter($columnName, DatagridConfiguration $configuration)
    {
        $sorters = $configuration->offsetGetByPath('[sorters][columns]');
        if (isset($sorters[$columnName])) {
            unset($sorters[$columnName]);
        }
        $configuration->offsetSetByPath('[sorters][columns]', $sorters);
    }
}
