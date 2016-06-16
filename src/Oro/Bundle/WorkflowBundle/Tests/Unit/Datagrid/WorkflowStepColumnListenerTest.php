<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class WorkflowStepColumnListenerTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY = 'Test:Entity';
    const ENTITY_FULL_NAME = 'Test\Entity\Full\Name';
    const ALIAS = 'testEntity';
    const COLUMN = 'workflowStepLabel';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowManager;

    /**
     * @var WorkflowStepColumnListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WorkflowStepColumnListener(
            $this->doctrineHelper,
            $this->configProvider,
            $this->workflowManager
        );
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper);
        unset($this->configProvider);
        unset($this->workflowManager);
        unset($this->listener);
    }

    public function testAddWorkflowStepColumn()
    {
        $this->assertAttributeEquals(
            array(WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN),
            'workflowStepColumns',
            $this->listener
        );

        $this->listener->addWorkflowStepColumn(WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN);
        $this->listener->addWorkflowStepColumn('workflowStep');
        $this->listener->addWorkflowStepColumn('workflowStep');

        $this->assertAttributeEquals(
            array(WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN, 'workflowStep'),
            'workflowStepColumns',
            $this->listener
        );
    }

    /**
     * @param array $config
     * @param bool $hasWorkflow
     * @param bool $hasConfig
     * @param bool $isShowStep
     * @dataProvider buildBeforeNoUpdateDataProvider
     */
    public function testBuildBeforeNoUpdate(array $config, $hasWorkflow = true, $hasConfig = true, $isShowStep = true)
    {
        $this->setUpEntityManagerMock(self::ENTITY, self::ENTITY);
        $this->setUpWorkflowManagerMock(self::ENTITY, $hasWorkflow);
        $this->setUpConfigProviderMock(self::ENTITY, $hasConfig, $isShowStep);

        $this->listener->addWorkflowStepColumn(self::COLUMN);

        $event = $this->createEvent($config);
        $this->listener->onBuildBefore($event);
        $this->assertEquals($config, $event->getConfig()->toArray());
    }

    /**
     * @return array
     */
    public function buildBeforeNoUpdateDataProvider()
    {
        return array(
            'workflow step column already defined' => array(
                'config' => array(
                    'source' => array(),
                    'columns' => array(
                        self::COLUMN => array('label' => 'Test'),
                    )
                )
            ),
            'no root entity' => array(
                'config' => array(
                    'source' => array(
                        'query' => array(
                            'from' => array()
                        )
                    ),
                    'columns' => array()
                )
            ),
            'no root alias' => array(
                'config' => array(
                    'source' => array(
                        'query' => array(
                            'from' => array(array('table' => self::ENTITY))
                        )
                    ),
                    'columns' => array()
                )
            ),
            'no active workflow' => array(
                'config' => array(
                    'source' => array(
                        'query' => array(
                            'from' => array(array('table' => self::ENTITY, 'alias' => self::ALIAS))
                        )
                    ),
                    'columns' => array()
                ),
                'hasWorkflow' => false,
            ),
            'no entity config' => array(
                'config' => array(
                    'source' => array(
                        'query' => array(
                            'from' => array(array('table' => self::ENTITY, 'alias' => self::ALIAS))
                        )
                    ),
                    'columns' => array()
                ),
                'hasWorkflow' => true,
                'hasConfig' => false
            ),
            'workflow step is hidden' => array(
                'config' => array(
                    'source' => array(
                        'query' => array(
                            'from' => array(array('table' => self::ENTITY, 'alias' => self::ALIAS))
                        )
                    ),
                    'columns' => array()
                ),
                'hasWorkflow' => true,
                'hasConfig' => true,
                'isShowStep' => false,
            ),
            'has group by' => array(
                'config' => array(
                    'source' => array(
                        'query' => array(
                            'from' => array(array('table' => self::ENTITY, 'alias' => self::ALIAS)),
                            'groupBy' => self::ALIAS . '.id'
                        )
                    ),
                    'columns' => array()
                ),
                'hasWorkflow' => true,
                'hasConfig' => true,
                'isShowStep' => true,
            ),
        );
    }

    /**
     * @param array $inputConfig
     * @param array $expectedConfig
     * @dataProvider buildBeforeAddColumnDataProvider
     */
    public function testBuildBeforeAddColumn(array $inputConfig, array $expectedConfig)
    {
        $this->setUpEntityManagerMock(self::ENTITY, self::ENTITY_FULL_NAME);
        $this->setUpWorkflowManagerMock(self::ENTITY_FULL_NAME);
        $this->setUpConfigProviderMock(self::ENTITY_FULL_NAME);

        $event = $this->createEvent($inputConfig);
        $this->listener->onBuildBefore($event);
        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildBeforeAddColumnDataProvider()
    {
        return array(
            'simple configuration' => array(
                'inputConfig' => array(
                    'source' => array(
                        'query' => array(
                            'select' => array(
                                self::ALIAS . '.rootField',
                            ),
                            'from' => array(array('table' => self::ENTITY, 'alias' => self::ALIAS)),
                        ),
                    ),
                    'columns' => array(
                        'rootField' => array('label' => 'Root field'),
                    ),
                ),
                'expectedConfig' => array(
                    'source' => array(
                        'query' => array(
                            'select' => array(
                                self::ALIAS . '.rootField',
                                WorkflowStepColumnListener::WORKFLOW_STEP_ALIAS . '.label as '
                                . WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN,
                            ),
                            'from' => array(array('table' => self::ENTITY, 'alias' => self::ALIAS)),
                            'join' => array(
                                'left' => array(
                                    array(
                                        'join' => self::ALIAS . '.' . WorkflowStepColumnListener::PROPERTY_WORKFLOW_STEP,
                                        'alias' => WorkflowStepColumnListener::WORKFLOW_STEP_ALIAS,
                                    )
                                ),
                            ),
                        ),
                    ),
                    'columns' => array(
                        'rootField' => array('label' => 'Root field'),
                        WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => array(
                            'label' => 'oro.workflow.workflowstep.grid.label'
                        ),
                    ),
                ),
            ),
            'full configuration' => array(
                'inputConfig' => array(
                    'source' => array(
                        'query' => array(
                            'select' => array(
                                self::ALIAS . '.rootField',
                                'b.innerJoinField',
                                'c.leftJoinField',
                            ),
                            'from' => array(array('table' => self::ENTITY, 'alias' => self::ALIAS)),
                            'join' => array(
                                'inner' => array(array('join' => self::ALIAS . '.b', 'alias' => 'b')),
                                'left' => array(array('join' => self::ALIAS . '.c', 'alias' => 'c')),
                            ),
                        ),
                    ),
                    'columns' => array(
                        'rootField' => array('label' => 'Root field'),
                        'innerJoinField' => array('label' => 'Inner join field'),
                        'leftJoinField' => array('label' => 'Left join field'),
                    ),
                    'filters' => array(
                        'columns' => array(
                            'rootField' => array('data_name' => self::ALIAS . '.rootField'),
                            'innerJoinField' => array('data_name' => 'b.innerJoinField'),
                            'leftJoinField' => array('data_name' => 'c.leftJoinField'),
                        ),
                    ),
                    'sorters' => array(
                        'columns' => array(
                            'rootField' => array('data_name' => self::ALIAS . '.rootField'),
                            'innerJoinField' => array('data_name' => 'b.innerJoinField'),
                            'leftJoinField' => array('data_name' => 'c.leftJoinField'),
                        ),
                    ),
                ),
                'expectedConfig' => array(
                    'source' => array(
                        'query' => array(
                            'select' => array(
                                self::ALIAS . '.rootField',
                                'b.innerJoinField',
                                'c.leftJoinField',
                                WorkflowStepColumnListener::WORKFLOW_STEP_ALIAS . '.label as '
                                    . WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN,
                            ),
                            'from' => array(array('table' => self::ENTITY, 'alias' => self::ALIAS)),
                            'join' => array(
                                'inner' => array(array('join' => self::ALIAS . '.b', 'alias' => 'b')),
                                'left' => array(
                                    array('join' => self::ALIAS . '.c', 'alias' => 'c'),
                                    array(
                                        'join' => self::ALIAS . '.' . WorkflowStepColumnListener::PROPERTY_WORKFLOW_STEP,
                                        'alias' => WorkflowStepColumnListener::WORKFLOW_STEP_ALIAS,
                                    )
                                ),
                            ),
                        ),
                    ),
                    'columns' => array(
                        'rootField' => array('label' => 'Root field'),
                        'innerJoinField' => array('label' => 'Inner join field'),
                        'leftJoinField' => array('label' => 'Left join field'),
                        WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => array(
                            'label' => 'oro.workflow.workflowstep.grid.label'
                        ),
                    ),
                    'filters' => array(
                        'columns' => array(
                            'rootField' => array('data_name' => self::ALIAS . '.rootField'),
                            'innerJoinField' => array('data_name' => 'b.innerJoinField'),
                            'leftJoinField' => array('data_name' => 'c.leftJoinField'),
                            WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => array(
                                'type' => 'entity',
                                'data_name' => self::ALIAS . '.' . WorkflowStepColumnListener::PROPERTY_WORKFLOW_STEP,
                                'options' => array(
                                    'field_type' => 'oro_workflow_step_select',
                                    'field_options' => array('workflow_entity_class' => self::ENTITY_FULL_NAME)
                                )
                            ),
                        ),
                    ),
                    'sorters' => array(
                        'columns' => array(
                            'rootField' => array('data_name' => self::ALIAS . '.rootField'),
                            'innerJoinField' => array('data_name' => 'b.innerJoinField'),
                            'leftJoinField' => array('data_name' => 'c.leftJoinField'),
                            WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => array(
                                'data_name' => WorkflowStepColumnListener::WORKFLOW_STEP_ALIAS . '.stepOrder',
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * @param array $inputConfig
     * @param array $expectedConfig
     * @dataProvider buildBeforeRemoveColumnDataProvider
     */
    public function testBuildBeforeRemoveColumn(array $inputConfig, array $expectedConfig)
    {
        $this->setUpEntityManagerMock(self::ENTITY, self::ENTITY_FULL_NAME);
        $this->setUpWorkflowManagerMock(self::ENTITY_FULL_NAME);
        $this->setUpConfigProviderMock(self::ENTITY_FULL_NAME, true, false);

        $event = $this->createEvent($inputConfig);
        $this->listener->onBuildBefore($event);
        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    /**
     * @return array
     */
    public function buildBeforeRemoveColumnDataProvider()
    {
        return array(
            'remove defined workflow step column' => array(
                'inputConfig' => array(
                    'source' => array(
                        'query' => array(
                            'select' => array(
                                self::ALIAS . '.rootField',
                                'workflowStep.label as ' . WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN,
                            ),
                            'from' => array(array('table' => self::ENTITY, 'alias' => self::ALIAS)),
                            'join' => array(
                                'inner' => array(
                                    array(
                                        'join' => self::ALIAS . '.' . WorkflowStepColumnListener::PROPERTY_WORKFLOW_STEP,
                                        'alias' => 'workflowStep',
                                    )
                                ),
                            ),
                        ),
                    ),
                    'columns' => array(
                        'rootField' => array('label' => 'Root field'),
                        WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => array(
                            'label' => 'oro.workflow.workflowstep.grid.label'
                        ),
                    ),
                    'filters' => array(
                        'columns' => array(
                            'rootField' => array('data_name' => self::ALIAS . '.rootField'),
                            WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => array(
                                'type' => 'entity',
                                'data_name' => self::ALIAS . '.' . WorkflowStepColumnListener::PROPERTY_WORKFLOW_STEP,
                                'options' => array(
                                    'field_type' => 'oro_workflow_step_select',
                                    'field_options' => array('workflow_entity_class' => self::ENTITY_FULL_NAME)
                                )
                            ),
                        ),
                    ),
                    'sorters' => array(
                        'columns' => array(
                            'rootField' => array('data_name' => self::ALIAS . '.rootField'),
                            WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => array(
                                'data_name' => 'workflowStep.stepOrder',
                            ),
                        ),
                    ),
                ),
                'expectedConfig' => array(
                    'source' => array(
                        'query' => array(
                            'select' => array(
                                self::ALIAS . '.rootField',
                                'workflowStep.label as ' . WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN,
                            ),
                            'from' => array(array('table' => self::ENTITY, 'alias' => self::ALIAS)),
                            'join' => array(
                                'inner' => array(
                                    array(
                                        'join' => self::ALIAS . '.' . WorkflowStepColumnListener::PROPERTY_WORKFLOW_STEP,
                                        'alias' => 'workflowStep',
                                    )
                                ),
                            ),
                        ),
                    ),
                    'columns' => array(
                        'rootField' => array('label' => 'Root field'),
                    ),
                    'filters' => array(
                        'columns' => array(
                            'rootField' => array('data_name' => self::ALIAS . '.rootField'),
                        ),
                    ),
                    'sorters' => array(
                        'columns' => array(
                            'rootField' => array('data_name' => self::ALIAS . '.rootField'),
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * @param string $entity
     * @param string $entityFullName
     */
    protected function setUpEntityManagerMock($entity, $entityFullName)
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())->method('getName')
            ->will($this->returnValue($entityFullName));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())->method('getClassMetadata')->with($entity)
            ->will($this->returnValue($metadata));

        $this->doctrineHelper->expects($this->any())->method('getEntityManager')->with($entity)
            ->will($this->returnValue($entityManager));
    }

    /**
     * @param string $entity
     * @param bool $hasConfig
     * @param bool $isShowStep
     */
    protected function setUpConfigProviderMock($entity, $hasConfig = true, $isShowStep = true)
    {
        $this->configProvider->expects($this->any())->method('hasConfig')->with($entity)
            ->will($this->returnValue($hasConfig));

        if ($hasConfig) {
            $config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
            $config->expects($this->any())->method('has')->with('show_step_in_grid')
                ->will($this->returnValue(true));
            $config->expects($this->any())->method('is')->with('show_step_in_grid')
                ->will($this->returnValue($isShowStep));

            $this->configProvider->expects($this->any())->method('getConfig')->with($entity)
                ->will($this->returnValue($config));
        } else {
            $this->configProvider->expects($this->never())->method('getConfig');
        }
    }

    /**
     * @param string $entity
     * @param bool $hasWorkflow
     */
    protected function setUpWorkflowManagerMock($entity, $hasWorkflow = true)
    {
        $this->workflowManager->expects($this->any())->method('hasApplicableWorkflowByEntityClass')->with($entity)
            ->will($this->returnValue($hasWorkflow));
    }

    /**
     * @param array $configuration
     * @return BuildBefore
     */
    protected function createEvent(array $configuration)
    {
        $datagridConfiguration = DatagridConfiguration::create($configuration);

        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildBefore')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())->method('getConfig')
            ->will($this->returnValue($datagridConfiguration));

        return $event;
    }
}
