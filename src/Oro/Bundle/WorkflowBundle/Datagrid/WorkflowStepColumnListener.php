<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionSelectType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepSelectType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class WorkflowStepColumnListener
{
    const WORKFLOW_STEP_COLUMN = 'workflowStepLabel';

    const WORKFLOW_FILTER = 'workflowStepLableByWorkflow';
    const WORKFLOW_STEP_FILTER = 'workflowStepLableByWorkflowStep';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var array
     */
    protected $workflowStepColumns = [self::WORKFLOW_STEP_COLUMN];

    /**
     * @param DoctrineHelper  $doctrineHelper
     * @param ConfigProvider  $configProvider
     * @param WorkflowManager $workflowManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigProvider $configProvider,
        WorkflowManager $workflowManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
        $this->workflowManager = $workflowManager;
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

        // get root entity
        list($rootEntity, $rootEntityAlias) = $this->getRootEntityNameAndAlias($config);

        $groupBy = $config->offsetGetByPath('[source][query][groupBy]', null);
        if (!$rootEntity || !$rootEntityAlias || $groupBy) {
            return;
        }

        // whether entity has active workflow and entity should render workflow step field
        $isShowWorkflowStep = $this->workflowManager->hasApplicableWorkflowsByEntityClass($rootEntity)
            && $this->isShowWorkflowStep($rootEntity);

        // check whether grid contains workflow step column
        $columns = $config->offsetGetByPath('[columns]', []);
        $workflowStepColumns = array_intersect($this->workflowStepColumns, array_keys($columns));

        // remove workflow step if it must be hidden but there are workflow step columns
        if (!$isShowWorkflowStep && $workflowStepColumns) {
            $this->removeWorkflowStep($config, $workflowStepColumns);
        }

        // add workflow step if it must be shown and there are no workflow step columns
        if ($isShowWorkflowStep && !$workflowStepColumns) {
            $this->addWorkflowStep($config, $rootEntity);
        }
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
        list($rootEntity) = $this->getRootEntityNameAndAlias($config);

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $workflowItems = $this->getWorkflowItemRepository()->getGroupedWorkflowNameAndWorkflowStepName(
            $rootEntity,
            array_map(
                function (ResultRecord $record) {
                    return $record->getValue('id');
                },
                $records
            )
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
     * @return string
     */
    protected function updateEntityClass($entity)
    {
        return $this->doctrineHelper->getEntityManager($entity)->getClassMetadata($entity)->getName();
    }

    /**
     * @param string $entity
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
     */
    protected function addWorkflowStep(DatagridConfiguration $config, $rootEntity)
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

        // add filter (only if there is at least one filter)
        $filters = $config->offsetGetByPath('[filters][columns]', []);
        if ($filters) {
            $filters[self::WORKFLOW_FILTER] = [
                'label' => 'oro.workflow.workflowdefinition.entity_label',
                'type' => 'entity',
                'data_name' => self::WORKFLOW_STEP_COLUMN,
                'options' => [
                    'field_type' => WorkflowDefinitionSelectType::NAME,
                    'field_options' => [
                        'workflow_entity_class' => $rootEntity,
                        'multiple' => true
                    ]
                ]
            ];
            $filters[self::WORKFLOW_STEP_FILTER] = [
                'label' => 'oro.workflow.workflowstep.grid.label',
                'type' => 'entity',
                'data_name' => self::WORKFLOW_STEP_COLUMN,
                'options' => [
                    'field_type' => WorkflowStepSelectType::NAME,
                    'field_options' => [
                        'workflow_entity_class' => $rootEntity,
                        'multiple' => true
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
        // remove columns
        $columns = $config->offsetGetByPath('[columns]', []);
        foreach ($workflowStepColumns as $column) {
            if (!empty($columns[$column])) {
                unset($columns[$column]);
            }
        }
        $config->offsetSetByPath('[columns]', $columns);

        // remove filters
        $filters = $config->offsetGetByPath('[filters][columns]', []);
        if ($filters) {
            foreach ($workflowStepColumns as $column) {
                if (!empty($filters[$column])) {
                    unset($filters[$column]);
                }
            }
            $config->offsetSetByPath('[filters][columns]', $filters);
        }

        // remove sorters
        $sorters = $config->offsetGetByPath('[sorters][columns]', []);
        if ($sorters) {
            foreach ($workflowStepColumns as $column) {
                if (!empty($sorters[$column])) {
                    unset($sorters[$column]);
                }
            }
            $config->offsetSetByPath('[sorters][columns]', $sorters);
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    protected function getRootEntityNameAndAlias(DatagridConfiguration $config)
    {
        $rootEntity = null;
        $rootEntityAlias = null;

        $from = $config->offsetGetByPath('[source][query][from]');
        if ($from) {
            $firstFrom = current($from);
            if (!empty($firstFrom['table']) && !empty($firstFrom['alias'])) {
                $rootEntity = $this->updateEntityClass($firstFrom['table']);
                $rootEntityAlias = $firstFrom['alias'];
            }
        }

        return [$rootEntity, $rootEntityAlias];
    }

    /**
     * Check whether grid contains workflow step column
     *
     * @param DatagridConfiguration $config
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
        $parameters = $datagrid->getParameters();
        $filters = $parameters->get('_filter', []);

        if (array_key_exists($filter, $filters) && array_key_exists('value', $filters[$filter])) {
            list($rootEntity, $rootEntityAlias) = $this->getRootEntityNameAndAlias($datagrid->getConfig());

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

            unset($filters[$filter]);
            $parameters->set('_filter', $filters);
        }
    }
}
