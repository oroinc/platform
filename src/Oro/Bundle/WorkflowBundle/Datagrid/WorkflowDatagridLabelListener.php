<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionSelectType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepSelectType;
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
        if (count($columns[WorkflowStep::class])) {
            foreach ($columns[WorkflowStep::class] as $columnName) {
                $this->fixColumnDefinition($columnName, $configuration);
                $this->fixStepNameFilter($columnName, $configuration);
                $this->fixColumnSorter($columnName, $configuration);
            }
        }
        if (count($columns[WorkflowDefinition::class])) {
            foreach ($columns[WorkflowDefinition::class] as $columnName) {
                $this->fixColumnDefinition($columnName, $configuration);
                $this->fixDefinitionNameFilter($columnName, $configuration);
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
        $columns = [
            WorkflowStep::class => [],
            WorkflowDefinition::class => [],
        ];
        if (count($columnAliases)) {
            foreach ($columnAliases as $key => $alias) {
                if (false !== strpos($key, WorkflowStep::class . '::label')) {
                    $columns[WorkflowStep::class][] = $alias;
                    continue;
                }
                if (false !== strpos($key, WorkflowDefinition::class . '::label')) {
                    $columns[WorkflowDefinition::class][] = $alias;
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
    private function fixStepNameFilter($columnName, DatagridConfiguration $configuration)
    {
        $filters = $configuration->offsetGetByPath('[filters][columns]');
        $label = $configuration->offsetGetByPath(sprintf('[columns][%s][label]', $columnName));
        $label = $label ?: 'oro.workflow.workflowstep.grid.label';
        if (isset($filters[$columnName])) {
            $tableAlias = $this->getTableAliasForColumnName($columnName, $configuration);
            $filters[$columnName] = [
                'label' => $label,
                'type' => 'entity',
                'data_name' => $tableAlias . '.id',
                'options' => [
                    'field_type' => WorkflowStepSelectType::NAME,
                    'field_options' => [
                        'workflow_entity_class' => $this->getRootEntityClass($configuration),
                        'multiple' => true
                    ]
                ]
            ];
        }
        $configuration->offsetSetByPath('[filters][columns]', $filters);
    }

    /**
     * @param $columnName
     * @param DatagridConfiguration $configuration
     */
    private function fixDefinitionNameFilter($columnName, DatagridConfiguration $configuration)
    {
        $filters = $configuration->offsetGetByPath('[filters][columns]');
        $label = $configuration->offsetGetByPath(sprintf('[columns][%s][label]', $columnName));
        $label = $label ?: 'oro.workflow.workflowdefinition.grid.column.label';
        if (isset($filters[$columnName])) {
            $tableAlias = $this->getTableAliasForColumnName($columnName, $configuration);
            $filters[$columnName] = [
                'label' => $label,
                'type' => 'entity',
                'data_name' => $tableAlias . '.name',
                'options' => [
                    'field_type' => WorkflowDefinitionSelectType::NAME,
                    'field_options' => [
                        'workflow_entity_class' => $this->getRootEntityClass($configuration),
                        'multiple' => true
                    ]
                ]
            ];
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

    /**
     * @param $columnName
     * @param DatagridConfiguration $configuration
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    private function getTableAliasForColumnName($columnName, DatagridConfiguration $configuration)
    {
        $selects = $configuration->offsetGetByPath('[source][query][select]');
        foreach ($selects as $select) {
            $matches = [];
            if (preg_match('/^(.*)\.[a-zA-Z0-9_]+\sas\s' . $columnName . '$/', $select, $matches)) {
                if ($matches && isset($matches[1])) {
                    return $matches[1];
                }
            }
        }

        throw new InvalidArgumentException(sprintf('Source table for "%s" column not found', $columnName));
    }

    /**
     * @param DatagridConfiguration $configuration
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    private function getRootEntityClass(DatagridConfiguration $configuration)
    {
        $from = $configuration->offsetGetByPath('[source][query][from][0]');
        if ($from && isset($from['table'])) {
            return $from['table'];
        }

        throw new InvalidArgumentException('Unable to find root entity class');
    }
}
