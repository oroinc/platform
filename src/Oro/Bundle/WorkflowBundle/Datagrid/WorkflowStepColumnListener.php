<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionSelectType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepSelectType;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowQueryTrait;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;

/**
 * Adds workflow and workflow_step columns and filters to datagrids.
 */
class WorkflowStepColumnListener
{
    use WorkflowQueryTrait;
    const WORKFLOW_STEP_COLUMN = 'workflowStepLabel';
    const WORKFLOW_FILTER = 'workflowStepLabelByWorkflow';
    const WORKFLOW_STEP_FILTER = 'workflowStepLabelByWorkflowStep';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var WorkflowManagerRegistry */
    protected $workflowManagerRegistry;

    /** @var array */
    protected $workflowStepColumns = [self::WORKFLOW_STEP_COLUMN];

    /** @var array key(Entity Class) => value(array of Workflow instances) */
    protected $workflows = [];

    /** @var DatagridStateProviderInterface */
    private $filtersStateProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EntityClassResolver $entityClassResolver
     * @param ConfigProvider $configProvider
     * @param WorkflowManagerRegistry $workflowManagerRegistry
     * @param DatagridStateProviderInterface $filtersStateProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityClassResolver $entityClassResolver,
        ConfigProvider $configProvider,
        WorkflowManagerRegistry $workflowManagerRegistry,
        DatagridStateProviderInterface $filtersStateProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityClassResolver = $entityClassResolver;
        $this->configProvider = $configProvider;
        $this->workflowManagerRegistry = $workflowManagerRegistry;
        $this->filtersStateProvider = $filtersStateProvider;
    }

    /**
     * @param string $columnName
     */
    public function addWorkflowStepColumn($columnName)
    {
        if (!in_array($columnName, $this->workflowStepColumns, true)) {
            $this->workflowStepColumns[] = $columnName;
        }
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        $rootEntity = $this->getRootEntity($config);

        if (!$rootEntity) {
            return;
        }

        $rootEntityAlias = $config->getOrmQuery()->getRootAlias();
        if (!$rootEntityAlias) {
            return;
        }

        // whether entity has active workflow and entity should render workflow step field
        $isShowWorkflowStep = !empty($this->getWorkflows($rootEntity)) && $this->isShowWorkflowStep($rootEntity);

        // check whether grid contains workflow step column
        $columns = $config->offsetGetByPath('[columns]', []);
        $workflowStepColumns = array_intersect($this->workflowStepColumns, array_keys($columns));

        // remove workflow step if it must be hidden but there are workflow step columns
        if (!$isShowWorkflowStep && $workflowStepColumns) {
            $this->removeWorkflowStep($config, $workflowStepColumns);
        }

        // add workflow step if it must be shown and there are no workflow step columns
        if ($isShowWorkflowStep && !$workflowStepColumns) {
            $this->addWorkflowStep($config, $rootEntity, $rootEntityAlias);
        }
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return null|string
     */
    private function getRootEntity(DatagridConfiguration $config)
    {
        // datasource type other than ORM is not supported yet
        if (!$config->isOrmDatasource()) {
            return null;
        }

        // get root entity
        $rootEntity = $config->getOrmQuery()->getRootEntity($this->entityClassResolver);

        return $rootEntity ?: null;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();

        $config = $datagrid->getConfig();
        $datasource = $datagrid->getDatasource();

        if (!($datasource instanceof OrmDatasource) || !$this->isApplicable($config)) {
            return;
        }

        $this->applyFilter($datagrid, self::WORKFLOW_FILTER, 'getEntityIdsByEntityClassAndWorkflowNames');
        $this->applyFilter($datagrid, self::WORKFLOW_STEP_FILTER, 'getEntityIdsByEntityClassAndWorkflowStepIds');
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        $config = $event->getDatagrid()->getConfig();

        if (!$this->isApplicable($config)) {
            return;
        }

        // get root entity
        $rootEntity = $config->getOrmQuery()->getRootEntity($this->entityClassResolver);

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $workflowItems = $this->getWorkflowItemRepository()->getGroupedWorkflowNameAndWorkflowStepName(
            $rootEntity,
            array_map(
                function (ResultRecord $record) {
                    return $record->getValue('id');
                },
                $records
            ),
            $this->isEntityHaveMoreThanOneWorkflow($rootEntity),
            array_keys($this->getWorkflows($rootEntity))
        );

        foreach ($records as $record) {
            $items = [];

            $id = $record->getValue('id');
            if (array_key_exists($id, $workflowItems)) {
                foreach ($workflowItems[$id] as $data) {
                    $items[] = $data;
                }
            }

            $record->addData([self::WORKFLOW_STEP_COLUMN => $items]);
        }
    }

    /**
     * @param string $entity
     *
     * @return bool
     */
    protected function isShowWorkflowStep($entity)
    {
        if ($this->configProvider->hasConfig($entity)) {
            return $this->configProvider
                ->getConfig($entity)
                ->is('show_step_in_grid');
        }

        return false;
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $rootEntity
     * @param string $rootEntityAlias
     */
    protected function addWorkflowStep(DatagridConfiguration $config, $rootEntity, $rootEntityAlias)
    {
        // add column
        $columns = $config->offsetGetByPath('[columns]', []);
        $columns[self::WORKFLOW_STEP_COLUMN] = [
            'label' => 'oro.workflow.workflowstep.grid.label',
            'type' => 'twig',
            'frontend_type' => 'html',
            'template' => 'OroWorkflowBundle:Datagrid:Column/workflowStep.html.twig'
        ];
        $config->offsetSetByPath('[columns]', $columns);

        $isManyWorkflows = $this->isEntityHaveMoreThanOneWorkflow($rootEntity);

        // add sorting by WorkflowStep Label in scope https://magecore.atlassian.net/browse/BAP-13321

        // add filter (only if there is at least one filter)
        $filters = $config->offsetGetByPath('[filters][columns]', []);
        if ($filters) {
            if ($isManyWorkflows) {
                $filters[self::WORKFLOW_FILTER] = [
                    'label' => 'oro.workflow.workflowdefinition.entity_label',
                    'type' => 'entity',
                    'data_name' => self::WORKFLOW_STEP_COLUMN,
                    'options' => [
                        'field_type' => WorkflowDefinitionSelectType::class,
                        'field_options' => [
                            'workflow_entity_class' => $rootEntity,
                            'multiple' => true
                        ]
                    ]
                ];
            }

            $filters[self::WORKFLOW_STEP_FILTER] = [
                'label' => 'oro.workflow.workflowstep.grid.label',
                'type' => 'workflow_step',
                'data_name' => self::WORKFLOW_STEP_COLUMN . '.id',
                'options' => [
                    'field_type' => WorkflowStepSelectType::class,
                    'field_options' => [
                        'workflow_entity_class' => $rootEntity,
                        'multiple' => true,
                        'translatable_options' => false
                    ]
                ]
            ];
            $config->offsetSetByPath('[filters][columns]', $filters);
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @param array $workflowStepColumns
     */
    protected function removeWorkflowStep(DatagridConfiguration $config, array $workflowStepColumns)
    {
        $paths = [
            '[columns]',
            '[filters][columns]',
            '[sorters][columns]'
        ];

        foreach ($paths as $path) {
            $columns = $config->offsetGetByPath($path, []);
            foreach ($workflowStepColumns as $column) {
                if (!empty($columns[$column])) {
                    unset($columns[$column]);
                }
            }
            $config->offsetSetByPath($path, $columns);
        }
    }

    /**
     * Check whether grid contains workflow step column
     *
     * @param DatagridConfiguration $config
     *
     * @return bool
     */
    protected function isApplicable(DatagridConfiguration $config)
    {
        $columns = $config->offsetGetByPath('[columns]', []);

        return count(array_intersect($this->workflowStepColumns, array_keys($columns))) > 0;
    }

    /**
     * @return WorkflowItemRepository
     */
    protected function getWorkflowItemRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroWorkflowBundle:WorkflowItem');
    }

    /**
     * @param DatagridInterface $datagrid
     * @param string $filter
     * @param string $repositoryMethod
     */
    protected function applyFilter(DatagridInterface $datagrid, $filter, $repositoryMethod)
    {
        $filters = $this->filtersStateProvider
            ->getStateFromParameters($datagrid->getConfig(), $datagrid->getParameters());

        if (array_key_exists($filter, $filters) && array_key_exists('value', $filters[$filter])) {
            $rootEntity = $datagrid->getConfig()->getOrmQuery()->getRootEntity($this->entityClassResolver);
            $rootEntityAlias = $datagrid->getConfig()->getOrmQuery()->getRootAlias();

            $items = $this->getWorkflowItemRepository()
                ->$repositoryMethod($rootEntity, (array)$filters[$filter]['value']);

            /** @var OrmDatasource $datasource */
            $datasource = $datagrid->getDatasource();

            $qb = $datasource->getQueryBuilder();
            $param = $qb->getParameter('filteredWorkflowItemIds');

            if ($param === null) {
                $qb->andWhere($qb->expr()->in($rootEntityAlias, ':filteredWorkflowItemIds'))
                    ->setParameter('filteredWorkflowItemIds', $items);
            } else {
                $qb->setParameter('filteredWorkflowItemIds', array_intersect((array)$param->getValue(), $items));
            }
        }
    }

    /**
     * @param string $className
     *
     * @return array
     */
    protected function getWorkflows($className)
    {
        if (!array_key_exists($className, $this->workflows)) {
            $this->workflows[$className] = $this->getWorkflowManager()->getApplicableWorkflows($className);
        }

        return $this->workflows[$className];
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected function isEntityHaveMoreThanOneWorkflow($className)
    {
        return count($this->getWorkflows($className)) > 1;
    }

    /**
     * @return WorkflowManager
     */
    protected function getWorkflowManager()
    {
        return $this->workflowManagerRegistry->getManager();
    }
}
