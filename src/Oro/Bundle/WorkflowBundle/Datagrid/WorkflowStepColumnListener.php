<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Field\FieldGenerator;

class WorkflowStepColumnListener
{
    const WORKFLOW_STEP_COLUMN = 'workflowStepLabel';
    const WORKFLOW_STEP_ALIAS  = 'workflowStepEntity';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var array
     */
    protected $workflowStepColumns = array();

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigProviderInterface $configProvider
     * @param WorkflowManager $workflowManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigProviderInterface $configProvider,
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
        if (!in_array($columnName, $this->workflowStepColumns)) {
            $this->workflowStepColumns[] = $columnName;
        }
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        // check whether grid contains workflow step column
        $columns = $config->offsetGetByPath('[columns]', array());
        if (array_intersect($this->workflowStepColumns, array_keys($columns))) {
            return;
        }

        // get root entity
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

        // check whether entity has active workflow and entity should render workflow step field
        if (!$rootEntity || !$rootEntityAlias
            || !$this->workflowManager->hasApplicableWorkflowByEntityClass($rootEntity)
            || !$this->isShowWorkflowStep($rootEntity)
        ) {
            return;
        }

        $this->updateDatagridConfiguration($config, $rootEntity, $rootEntityAlias);
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
            $config = $this->configProvider->getConfig($entity);
            return $config->has('show_step_in_grid') && $config->is('show_step_in_grid');
        }

        return false;
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $rootEntity
     * @param string $rootEntityAlias
     */
    protected function updateDatagridConfiguration(DatagridConfiguration $config, $rootEntity, $rootEntityAlias)
    {
        $workflowStepTable = sprintf('%s.%s', $rootEntityAlias, FieldGenerator::PROPERTY_WORKFLOW_STEP);
        $workflowStepSelect = sprintf('%s.%s as %s', self::WORKFLOW_STEP_ALIAS, 'label', self::WORKFLOW_STEP_COLUMN);
        $workflowStepOrder = sprintf('%s.%s', self::WORKFLOW_STEP_ALIAS, 'stepOrder');

        // add left join
        $leftJoins = $config->offsetGetByPath('[source][query][join][left]', array());
        $leftJoins[] = array('join'  => $workflowStepTable, 'alias' => self::WORKFLOW_STEP_ALIAS);
        $config->offsetSetByPath('[source][query][join][left]', $leftJoins);

        // add select
        $selects = $config->offsetGetByPath('[source][query][select]', array());
        $selects[] = $workflowStepSelect;
        $config->offsetSetByPath('[source][query][select]', $selects);

        // add column
        $columns = $config->offsetGetByPath('[columns]', array());
        $columns[self::WORKFLOW_STEP_COLUMN] = array('label' => 'oro.workflow.workflowstep.grid.label');
        $config->offsetSetByPath('[columns]', $columns);

        // add filter (only if there is at least one filter)
        $filters = $config->offsetGetByPath('[filters][columns]', array());
        if ($filters) {
            $filters[self::WORKFLOW_STEP_COLUMN] = array(
                'type' => 'entity',
                'data_name' => $workflowStepTable,
                'options' => array(
                    'field_type' => 'oro_workflow_step_select',
                    'field_options' => array('workflow_entity_class' => $rootEntity)
                )
            );
            $config->offsetSetByPath('[filters][columns]', $filters);
        }

        // add sorter (only if there is at least one sorter)
        $sorters = $config->offsetGetByPath('[sorters][columns]', array());
        if ($sorters) {
            $sorters[self::WORKFLOW_STEP_COLUMN] = array('data_name' => $workflowStepOrder);
            $config->offsetSetByPath('[sorters][columns]', $sorters);
        }
    }
}
