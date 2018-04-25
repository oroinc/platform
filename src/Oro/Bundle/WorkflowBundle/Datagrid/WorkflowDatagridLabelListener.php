<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;
use Oro\Bundle\QueryDesignerBundle\Grid\QueryDesignerQueryConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionSelectType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepSelectType;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Replaces translation keys (Workflow Step Label) with their translated values
 */
class WorkflowDatagridLabelListener
{
    /** @var array */
    protected static $columns = [
        WorkflowStep::class => [
            'pk' => 'id',
            'defaultLabel' => 'oro.workflow.workflowstep.grid.label',
            'form' => WorkflowStepSelectType::class
        ],
        WorkflowDefinition::class => [
            'pk' => 'name',
            'defaultLabel' => 'oro.workflow.workflowdefinition.grid.column.label',
            'form' => WorkflowDefinitionSelectType::class
        ]
    ];

    /** @var TranslatorInterface */
    protected $translator;

    /** @param TranslatorInterface $translator */
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

        foreach (self::$columns as $class => $config) {
            if (count($columns[$class])) {
                foreach ($columns[$class] as $columnName) {
                    $this->updateColumnDefinition($configuration, $columnName);
                    $this->updateColumnFilter(
                        $configuration,
                        $columnName,
                        $config['pk'],
                        $config['defaultLabel'],
                        $config['form']
                    );
                    $this->updateColumnSorter($configuration, $columnName);
                }
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
        $columns = [
            WorkflowStep::class => [],
            WorkflowDefinition::class => [],
        ];
        $columnAliases = $configuration->offsetGetByPath(QueryDesignerQueryConfiguration::COLUMN_ALIASES, []);
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

        return $columns;
    }

    /**
     * @param DatagridConfiguration $configuration
     * @param string $columnName
     */
    private function updateColumnDefinition(DatagridConfiguration $configuration, $columnName)
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
     * @param DatagridConfiguration $configuration
     * @param string $columnName
     * @param string $pk
     * @param string $defaultLabel
     * @param string $form
     */
    private function updateColumnFilter(DatagridConfiguration $configuration, $columnName, $pk, $defaultLabel, $form)
    {
        $filters = $configuration->offsetGetByPath('[filters][columns]');
        $label = $configuration->offsetGetByPath(sprintf('[columns][%s][label]', $columnName));
        $label = $label ?: $defaultLabel;
        if (isset($filters[$columnName])) {
            $tableAlias = $this->getTableAliasForColumnName($columnName, $configuration);
            $filters[$columnName] = [
                'label' => $label,
                'type' => 'entity',
                'data_name' => $tableAlias . '.' . $pk,
                'options' => [
                    'field_type' => $form,
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
     * @param DatagridConfiguration $configuration
     * @param string $columnName
     */
    private function updateColumnSorter(DatagridConfiguration $configuration, $columnName)
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
        $selects = $configuration->getOrmQuery()->getSelect();
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
        $rootEntity = $configuration->getOrmQuery()->getRootEntity();
        if (!$rootEntity) {
            throw new InvalidArgumentException('Unable to find root entity class');
        }

        return $rootEntity;
    }
}
