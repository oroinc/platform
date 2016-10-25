<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;

/**
 * Replaces translation keys (Workflow Step Label) with their translated values
 */
class WorkflowDatagridLabelListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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
     * Used only internally, please use WorkflowTranslationHelper or '@translator.default'
     *
     * @param $id
     *
     * @return string
     */
    public function trans($id)
    {
        return $this->translator->trans($id, [], WorkflowTranslationHelper::TRANSLATION_DOMAIN);
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

    /**
     * @param $columnName
     * @param DatagridConfiguration $configuration
     */
    private function fixColumnDefinition($columnName, DatagridConfiguration $configuration)
    {
        $path = sprintf('[columns][%s]', $columnName);
        $column = $configuration->offsetGetByPath($path);
        $column['frontend_type'] = 'html';
        $column['type'] = 'callback';
        $column['callable'] = [$this, "trans"];
        $column['params'] = [$columnName];
        $configuration->offsetSetByPath($path, $column);
    }

    /**
     * @param $columnName
     * @param DatagridConfiguration $configuration
     */
    private function fixColumnFilter($columnName, DatagridConfiguration $configuration)
    {
        $filters = $configuration->offsetGetByPath('[filters][columns]');
        if (isset($filters[$columnName])) {
            unset($filters[$columnName]);
        }
        $configuration->offsetSetByPath('[filters][columns]', $filters);
    }

    /**
     * @param $columnName
     * @param DatagridConfiguration $configuration
     */
    private function fixColumnSorter($columnName, DatagridConfiguration $configuration)
    {
        $sorters = $configuration->offsetGetByPath('[sorters][columns]');
        if (isset($sorters[$columnName])) {
            unset($sorters[$columnName]);
        }
        $configuration->offsetSetByPath('[sorters][columns]', $sorters);
    }
}
