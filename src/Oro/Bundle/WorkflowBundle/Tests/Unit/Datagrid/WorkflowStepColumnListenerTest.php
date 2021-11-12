<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionSelectType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepSelectType;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowStepColumnListenerTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY = 'Test:Entity';
    private const ENTITY_FULL_NAME = 'Test\Entity\Full\Name';
    private const ALIAS = 'testEntity';
    private const COLUMN = 'workflowStepLabel';

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var WorkflowManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManagerRegistry;

    /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datagrid;

    /** @var DatagridStateProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filtersStateProvider;

    /** @var WorkflowStepColumnListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->workflowManagerRegistry = $this->createMock(WorkflowManagerRegistry::class);
        $this->filtersStateProvider = $this->createMock(DatagridStateProviderInterface::class);
        $this->datagrid = $this->createMock(DatagridInterface::class);

        $this->listener = new WorkflowStepColumnListener(
            $this->doctrineHelper,
            $this->entityClassResolver,
            $this->configProvider,
            $this->workflowManagerRegistry,
            $this->filtersStateProvider
        );
    }

    public function testAddWorkflowStepColumn()
    {
        self::assertEquals(
            [WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN],
            ReflectionUtil::getPropertyValue($this->listener, 'workflowStepColumns')
        );

        $this->listener->addWorkflowStepColumn(WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN);
        $this->listener->addWorkflowStepColumn('workflowStep');
        $this->listener->addWorkflowStepColumn('workflowStep');

        self::assertEquals(
            [WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN, 'workflowStep'],
            ReflectionUtil::getPropertyValue($this->listener, 'workflowStepColumns')
        );
    }

    /**
     * @dataProvider buildBeforeNoUpdateDataProvider
     */
    public function testBuildBeforeNoUpdate(
        array $config,
        bool $hasWorkflow = true,
        bool $hasConfig = true,
        bool $isShowStep = true
    ) {
        $this->setUpEntityManagerMock(self::ENTITY, self::ENTITY);
        $this->setUpWorkflowManagerMock(self::ENTITY, $hasWorkflow);
        $this->setUpConfigProviderMock(self::ENTITY, $hasConfig, $isShowStep);

        $this->listener->addWorkflowStepColumn(self::COLUMN);

        $event = $this->createBuildBeforeEvent($config);
        $this->listener->onBuildBefore($event);
        $this->assertEquals($config, $event->getConfig()->toArray());
    }

    public function buildBeforeNoUpdateDataProvider(): array
    {
        return [
            'workflow step column already defined' => [
                'config' => [
                    'source' => [],
                    'columns' => [
                        self::COLUMN => ['label' => 'Test'],
                    ]
                ]
            ],
            'no root entity' => [
                'config' => [
                    'source' => [
                        'query' => [
                            'from' => []
                        ]
                    ],
                    'columns' => []
                ]
            ],
            'no root alias' => [
                'config' => [
                    'source' => [
                        'query' => [
                            'from' => [['table' => self::ENTITY]]
                        ]
                    ],
                    'columns' => []
                ]
            ],
            'no active workflow' => [
                'config' => [
                    'source' => [
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]]
                        ]
                    ],
                    'columns' => []
                ],
                'hasWorkflow' => false,
            ],
            'no entity config' => [
                'config' => [
                    'source' => [
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]]
                        ]
                    ],
                    'columns' => []
                ],
                'hasWorkflow' => true,
                'hasConfig' => false
            ],
            'workflow step is hidden' => [
                'config' => [
                    'source' => [
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]]
                        ]
                    ],
                    'columns' => []
                ],
                'hasWorkflow' => true,
                'hasConfig' => true,
                'isShowStep' => false,
            ],
        ];
    }

    /**
     * @dataProvider buildBeforeAddColumnDataProvider
     */
    public function testBuildBeforeAddColumn(
        array $inputConfig,
        array $expectedConfig,
        bool $multiWorkflows = true,
        string $datagridName = 'test_datagrid_name'
    ) {
        $this->setUpEntityManagerMock(self::ENTITY, self::ENTITY_FULL_NAME);
        $this->setUpWorkflowManagerMock(self::ENTITY_FULL_NAME, true, $multiWorkflows);
        $this->setUpConfigProviderMock(self::ENTITY_FULL_NAME);

        $this->datagrid->expects($this->any())
            ->method('getName')
            ->willReturn($datagridName);

        $event = $this->createBuildBeforeEvent($inputConfig);
        $this->listener->onBuildBefore($event);
        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildBeforeAddColumnDataProvider(): array
    {
        return [
            'simple configuration' => [
                'inputConfig' => [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField',
                            ],
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]],
                        ],
                        'type' => OrmDatasource::TYPE,
                    ],
                    'columns' => [
                        'rootField' => ['label' => 'Root field'],
                    ],
                ],
                'expectedConfig' => [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField'
                            ],
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]]
                        ],
                        'type' => OrmDatasource::TYPE,
                    ],
                    'columns' => [
                        'rootField' => ['label' => 'Root field'],
                        WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => [
                            'label' => 'oro.workflow.workflowstep.grid.label',
                            'type' => 'twig',
                            'frontend_type' => 'html',
                            'template' => '@OroWorkflow/Datagrid/Column/workflowStep.html.twig'
                        ],
                    ],
                ],
            ],
            'simple configuration with report datagrid' => [
                'inputConfig' => [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField',
                            ],
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]],
                        ],
                        'type' => OrmDatasource::TYPE,
                    ],
                    'columns' => [
                        'rootField' => ['label' => 'Root field'],
                    ],
                ],
                'expectedConfig' => [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField'
                            ],
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]]
                        ],
                        'type' => OrmDatasource::TYPE,
                    ],
                    'columns' => [
                        'rootField' => ['label' => 'Root field'],
                    ],
                ],
                'multiWorkflows' => true,
                'datagridName' => Report::GRID_PREFIX . '123'
            ],
            'simple configuration with segment datagrid' => [
                'inputConfig' => [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField',
                            ],
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]],
                        ],
                        'type' => OrmDatasource::TYPE,
                    ],
                    'columns' => [
                        'rootField' => ['label' => 'Root field'],
                    ],
                ],
                'expectedConfig' => [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField'
                            ],
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]]
                        ],
                        'type' => OrmDatasource::TYPE,
                    ],
                    'columns' => [
                        'rootField' => ['label' => 'Root field'],
                    ],
                ],
                'multiWorkflows' => true,
                'datagridName' => Segment::GRID_PREFIX . '123'
            ],
            'full configuration' => [
                'inputConfig' => [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField',
                                'b.innerJoinField',
                                'c.leftJoinField',
                            ],
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]],
                            'join' => [
                                'inner' => [['join' => self::ALIAS . '.b', 'alias' => 'b']],
                                'left' => [['join' => self::ALIAS . '.c', 'alias' => 'c']],
                            ],
                        ],
                        'type' => OrmDatasource::TYPE,
                    ],
                    'columns' => [
                        'rootField' => ['label' => 'Root field'],
                        'innerJoinField' => ['label' => 'Inner join field'],
                        'leftJoinField' => ['label' => 'Left join field'],
                    ],
                    'filters' => [
                        'columns' => [
                            'rootField' => ['data_name' => self::ALIAS . '.rootField'],
                            'innerJoinField' => ['data_name' => 'b.innerJoinField'],
                            'leftJoinField' => ['data_name' => 'c.leftJoinField'],
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            'rootField' => ['data_name' => self::ALIAS . '.rootField'],
                            'innerJoinField' => ['data_name' => 'b.innerJoinField'],
                            'leftJoinField' => ['data_name' => 'c.leftJoinField'],
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField',
                                'b.innerJoinField',
                                'c.leftJoinField'
                            ],
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]],
                            'join' => [
                                'inner' => [['join' => self::ALIAS . '.b', 'alias' => 'b']],
                                'left' => [['join' => self::ALIAS . '.c', 'alias' => 'c']]
                            ],
                        ],
                        'type' => OrmDatasource::TYPE,
                    ],
                    'columns' => [
                        'rootField' => ['label' => 'Root field'],
                        'innerJoinField' => ['label' => 'Inner join field'],
                        'leftJoinField' => ['label' => 'Left join field'],
                        WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => [
                            'label' => 'oro.workflow.workflowstep.grid.label',
                            'type' => 'twig',
                            'frontend_type' => 'html',
                            'template' => '@OroWorkflow/Datagrid/Column/workflowStep.html.twig'
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            'rootField' => ['data_name' => self::ALIAS . '.rootField'],
                            'innerJoinField' => ['data_name' => 'b.innerJoinField'],
                            'leftJoinField' => ['data_name' => 'c.leftJoinField'],
                            WorkflowStepColumnListener::WORKFLOW_FILTER => [
                                'type' => 'entity',
                                'data_name' => WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN,
                                'options' => [
                                    'field_type' => WorkflowDefinitionSelectType::class,
                                    'field_options' => [
                                        'workflow_entity_class' => self::ENTITY_FULL_NAME,
                                        'multiple' => true
                                    ]
                                ],
                                'label' => 'oro.workflow.workflowdefinition.entity_label'
                            ],
                            WorkflowStepColumnListener::WORKFLOW_STEP_FILTER => [
                                'type' => 'workflow_step',
                                'data_name' => WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN . '.id',
                                'options' => [
                                    'field_type' => WorkflowStepSelectType::class,
                                    'field_options' => [
                                        'workflow_entity_class' => self::ENTITY_FULL_NAME,
                                        'multiple' => true,
                                        'translatable_options' => false
                                    ]
                                ],
                                'label' => 'oro.workflow.workflowstep.grid.label'
                            ],
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            'rootField' => ['data_name' => self::ALIAS . '.rootField'],
                            'innerJoinField' => ['data_name' => 'b.innerJoinField'],
                            'leftJoinField' => ['data_name' => 'c.leftJoinField']
                        ],
                    ],
                ],
            ],
            'full configuration for one workflow' => [
                'inputConfig' => [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField',
                                'b.innerJoinField',
                                'c.leftJoinField',
                            ],
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]],
                            'join' => [
                                'inner' => [['join' => self::ALIAS . '.b', 'alias' => 'b']],
                                'left' => [['join' => self::ALIAS . '.c', 'alias' => 'c']],
                            ],
                        ],
                        'type' => OrmDatasource::TYPE,
                    ],
                    'columns' => [
                        'rootField' => ['label' => 'Root field'],
                        'innerJoinField' => ['label' => 'Inner join field'],
                        'leftJoinField' => ['label' => 'Left join field'],
                    ],
                    'filters' => [
                        'columns' => [
                            'rootField' => ['data_name' => self::ALIAS . '.rootField'],
                            'innerJoinField' => ['data_name' => 'b.innerJoinField'],
                            'leftJoinField' => ['data_name' => 'c.leftJoinField'],
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            'rootField' => ['data_name' => self::ALIAS . '.rootField'],
                            'innerJoinField' => ['data_name' => 'b.innerJoinField'],
                            'leftJoinField' => ['data_name' => 'c.leftJoinField'],
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField',
                                'b.innerJoinField',
                                'c.leftJoinField'
                            ],
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]],
                            'join' => [
                                'inner' => [['join' => self::ALIAS . '.b', 'alias' => 'b']],
                                'left' => [
                                    ['join' => self::ALIAS . '.c', 'alias' => 'c'],
                                ]
                            ],
                        ],
                        'type' => OrmDatasource::TYPE,
                    ],
                    'columns' => [
                        'rootField' => ['label' => 'Root field'],
                        'innerJoinField' => ['label' => 'Inner join field'],
                        'leftJoinField' => ['label' => 'Left join field'],
                        WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => [
                            'label' => 'oro.workflow.workflowstep.grid.label',
                            'type' => 'twig',
                            'frontend_type' => 'html',
                            'template' => '@OroWorkflow/Datagrid/Column/workflowStep.html.twig'
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            'rootField' => ['data_name' => self::ALIAS . '.rootField'],
                            'innerJoinField' => ['data_name' => 'b.innerJoinField'],
                            'leftJoinField' => ['data_name' => 'c.leftJoinField'],
                            WorkflowStepColumnListener::WORKFLOW_STEP_FILTER => [
                                'type' => 'workflow_step',
                                'data_name' => WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN . '.id',
                                'options' => [
                                    'field_type' => WorkflowStepSelectType::class,
                                    'field_options' => [
                                        'workflow_entity_class' => self::ENTITY_FULL_NAME,
                                        'multiple' => true,
                                        'translatable_options' => false
                                    ]
                                ],
                                'label' => 'oro.workflow.workflowstep.grid.label'
                            ],
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            'rootField' => ['data_name' => self::ALIAS . '.rootField'],
                            'innerJoinField' => ['data_name' => 'b.innerJoinField'],
                            'leftJoinField' => ['data_name' => 'c.leftJoinField'],
                        ],
                    ],
                ],
                'multiWorkflow' => false
            ]
        ];
    }

    /**
     * @dataProvider buildBeforeRemoveColumnDataProvider
     */
    public function testBuildBeforeRemoveColumn(array $inputConfig, array $expectedConfig)
    {
        $this->setUpEntityManagerMock(self::ENTITY, self::ENTITY_FULL_NAME);
        $this->setUpWorkflowManagerMock(self::ENTITY_FULL_NAME);
        $this->setUpConfigProviderMock(self::ENTITY_FULL_NAME, true, false);

        $this->datagrid->expects($this->any())
            ->method('getName')
            ->willReturn('test_datagrid_name');

        $event = $this->createBuildBeforeEvent($inputConfig);
        $this->listener->onBuildBefore($event);
        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    public function buildBeforeRemoveColumnDataProvider(): array
    {
        return [
            'remove defined workflow step column' => [
                'inputConfig' => [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField',
                                'workflowStep.label as ' . WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN,
                            ],
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]],
                            'join' => [
                                'inner' => [
                                    [
                                        'join' => self::ALIAS . '.' . 'workflowStep',
                                        'alias' => 'workflowStep',
                                    ]
                                ],
                            ],
                        ],
                        'type' => OrmDatasource::TYPE,
                    ],
                    'columns' => [
                        'rootField' => ['label' => 'Root field'],
                        WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => [
                            'label' => 'oro.workflow.workflowstep.grid.label'
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            'rootField' => ['data_name' => self::ALIAS . '.rootField'],
                            WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => [
                                'type' => 'entity',
                                'data_name' => self::ALIAS . '.' . 'workflowStep',
                                'options' => [
                                    'field_type' => WorkflowStepSelectType::class,
                                    'field_options' => ['workflow_entity_class' => self::ENTITY_FULL_NAME]
                                ]
                            ],
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            'rootField' => ['data_name' => self::ALIAS . '.rootField'],
                            WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => [
                                'data_name' => 'workflowStep.stepOrder',
                            ],
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField',
                                'workflowStep.label as ' . WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN,
                            ],
                            'from' => [['table' => self::ENTITY, 'alias' => self::ALIAS]],
                            'join' => [
                                'inner' => [
                                    [
                                        'join' => self::ALIAS . '.' . 'workflowStep',
                                        'alias' => 'workflowStep',
                                    ]
                                ],
                            ],
                        ],
                        'type' => OrmDatasource::TYPE,
                    ],
                    'columns' => [
                        'rootField' => ['label' => 'Root field'],
                    ],
                    'filters' => [
                        'columns' => [
                            'rootField' => ['data_name' => self::ALIAS . '.rootField'],
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            'rootField' => ['data_name' => self::ALIAS . '.rootField'],
                        ],
                    ],
                ],
            ]
        ];
    }

    /**
     * @dataProvider buildAfterNoUpdateDataProvider
     */
    public function testOnBuildAfterNoUpdate(DatasourceInterface $datasource, DatagridConfiguration $inputConfig)
    {
        /** @var DatasourceInterface|\PHPUnit\Framework\MockObject\MockObject $datasource */
        $datasource->expects($this->never())
            ->method($this->anything());

        $event = $this->createBuildAfterEvent($datasource, $inputConfig);

        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $event->getDatagrid();
        $datagrid->expects($this->any())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $this->filtersStateProvider->expects($this->any())
            ->method('getStateFromParameters')
            ->willReturn([]);

        $this->listener->onBuildAfter($event);
    }

    public function buildAfterNoUpdateDataProvider(): array
    {
        return [
            'no orm datasource' => [
                'datasource' => $this->createMock(DatasourceInterface::class),
                'inputConfig' => DatagridConfiguration::create([])
            ],
            'orm datasource and empty config' => [
                'datasource' => $this->createMock(OrmDatasource::class),
                'inputConfig' => DatagridConfiguration::create([])
            ],
            'orm datasource and no filters' => [
                'datasource' => $this->createMock(OrmDatasource::class),
                'inputConfig' => DatagridConfiguration::create(
                    [
                        'columns' => [
                            WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => []
                        ]
                    ]
                )
            ]
        ];
    }

    public function testOnBuildAfter()
    {
        $this->setUpEntityManagerMock(self::ENTITY, self::ENTITY_FULL_NAME);

        $repository = $this->setUpWorkflowItemRepository();
        $repository->expects($this->once())
            ->method('getEntityIdsByEntityClassAndWorkflowNames')
            ->with(self::ENTITY_FULL_NAME, ['workflow_filter_value'])
            ->willReturn([42, 100]);
        $repository->expects($this->once())
            ->method('getEntityIdsByEntityClassAndWorkflowStepIds')
            ->with(self::ENTITY_FULL_NAME, ['workflow_step_filter_value'])
            ->willReturn([42]);

        $expr = $this->createMock(Expr::class);
        $expr->expects($this->once())
            ->method('in')
            ->with(self::ALIAS, ':filteredWorkflowItemIds')
            ->willReturnSelf();

        $qParameter = new Parameter('filteredWorkflowItemIds', [42, 100]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($expr);
        $qb->expects($this->once())
            ->method('andWhere')
            ->with($expr)
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('getParameter')
            ->with('filteredWorkflowItemIds')
            ->willReturnOnConsecutiveCalls(
                null,
                $qParameter
            );
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['filteredWorkflowItemIds', [42, 100]],
                ['filteredWorkflowItemIds', [42]]
            );

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->exactly(2))
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $event = $this->createBuildAfterEvent(
            $datasource,
            DatagridConfiguration::create(
                [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField'
                            ],
                            'from' => [
                                ['table' => self::ENTITY, 'alias' => self::ALIAS]
                            ]
                        ]
                    ],
                    'columns' => [
                        WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => []
                    ]
                ]
            )
        );

        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $event->getDatagrid();
        $datagrid->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $this->filtersStateProvider->expects($this->exactly(2))
            ->method('getStateFromParameters')
            ->willReturn([
                WorkflowStepColumnListener::WORKFLOW_FILTER => ['value' => 'workflow_filter_value'],
                WorkflowStepColumnListener::WORKFLOW_STEP_FILTER => ['value' => 'workflow_step_filter_value']
            ]);

        $this->listener->onBuildAfter($event);
    }

    public function testOnResultAfterNoUpdate()
    {
        $event = $this->createResultAfterEvent(DatagridConfiguration::create([]));
        $event->expects($this->never())
            ->method('getRecords');

        $repository = $this->setUpWorkflowItemRepository();
        $repository->expects($this->never())
            ->method($this->anything());

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfter()
    {
        $this->setUpEntityManagerMock(self::ENTITY, self::ENTITY_FULL_NAME);

        $recordOne = new ResultRecord(['id' => 42]);
        $recordTwo = new ResultRecord(['id' => 100]);

        $event = $this->createResultAfterEvent(
            DatagridConfiguration::create(
                [
                    'source' => [
                        'query' => [
                            'select' => [
                                self::ALIAS . '.rootField'
                            ],
                            'from' => [
                                ['table' => self::ENTITY, 'alias' => self::ALIAS]
                            ]
                        ]
                    ],
                    'columns' => [
                        WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => []
                    ]
                ]
            )
        );
        $event->expects($this->once())
            ->method('getRecords')
            ->willReturn([$recordOne, $recordTwo]);

        $data = [
            ['entityId' => 42, 'workflowName' => 'test1', 'stepName' => 'step1'],
            ['entityId' => 42, 'workflowName' => 'test2', 'stepName' => 'step2']
        ];

        $repository = $this->setUpWorkflowItemRepository();
        $repository->expects($this->once())
            ->method('getGroupedWorkflowNameAndWorkflowStepName')
            ->with(self::ENTITY_FULL_NAME, [42, 100], true, ['test1', 'test2'])
            ->willReturn([42 => $data]);

        $this->workflowManagerRegistry->expects($this->once())
            ->method('getManager')
            ->willReturn($this->workflowManager);

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with(WorkflowStepColumnListenerTest::ENTITY_FULL_NAME)
            ->willReturn([
                'test1' => $this->createMock(Workflow::class),
                'test2' => $this->createMock(Workflow::class),
            ]);

        $this->listener->onResultAfter($event);

        $this->assertEquals($data, $recordOne->getValue(WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN));
        $this->assertEquals([], $recordTwo->getValue(WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN));
    }

    private function setUpEntityManagerMock(string $entity, string $entityFullName): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getName')
            ->willReturn($entityFullName);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($entity)
            ->willReturn($metadata);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($entityManager);

        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->with(self::ENTITY)
            ->willReturn(self::ENTITY_FULL_NAME);
    }

    /**
     * @return WorkflowItemRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function setUpWorkflowItemRepository()
    {
        $repository = $this->createMock(WorkflowItemRepository::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with('OroWorkflowBundle:WorkflowItem')
            ->willReturn($repository);

        return $repository;
    }

    private function setUpConfigProviderMock(string $entity, bool $hasConfig = true, bool $isShowStep = true): void
    {
        $this->configProvider->expects($this->any())
            ->method('hasConfig')
            ->with($entity)
            ->willReturn($hasConfig);

        if ($hasConfig) {
            $config = $this->createMock(ConfigInterface::class);
            $config->expects($this->any())
                ->method('has')
                ->with('show_step_in_grid')
                ->willReturn(true);
            $config->expects($this->any())
                ->method('is')
                ->with('show_step_in_grid')
                ->willReturn($isShowStep);

            $this->configProvider->expects($this->any())
                ->method('getConfig')
                ->with($entity)
                ->willReturn($config);
        } else {
            $this->configProvider->expects($this->never())
                ->method('getConfig');
        }
    }

    private function setUpWorkflowManagerMock(
        string $entityClass,
        bool $hasWorkflow = true,
        bool $multiWorkflow = true
    ): void {
        $workflows = new ArrayCollection();

        if ($hasWorkflow) {
            $workflows->add($this->createMock(Workflow::class));
            if ($multiWorkflow) {
                $workflows->add($this->createMock(Workflow::class));
            }
        }

        $this->workflowManagerRegistry->expects($this->any())
            ->method('getManager')
            ->willReturn($this->workflowManager);

        $this->workflowManager->expects($this->any())
            ->method('getApplicableWorkflows')
            ->with($entityClass)
            ->willReturn($workflows);
    }

    private function createBuildBeforeEvent(array $configuration): BuildBefore
    {
        $datagridConfiguration = DatagridConfiguration::create($configuration);

        $event = $this->createMock(BuildBefore::class);
        $event->expects($this->any())
            ->method('getConfig')
            ->willReturn($datagridConfiguration);
        $event->expects($this->any())
            ->method('getDatagrid')
            ->willReturn($this->datagrid);

        return $event;
    }

    private function createBuildAfterEvent(
        DatasourceInterface $datasource,
        DatagridConfiguration $configuration
    ): BuildAfter {
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->any())
            ->method('getDatasource')
            ->willReturn($datasource);
        $datagrid->expects($this->any())
            ->method('getConfig')
            ->willReturn($configuration);

        $event = $this->createMock(BuildAfter::class);
        $event->expects($this->any())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        return $event;
    }

    /**
     * @return OrmResultAfter|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createResultAfterEvent(DatagridConfiguration $configuration)
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->any())
            ->method('getConfig')
            ->willReturn($configuration);

        $event = $this->createMock(OrmResultAfter::class);
        $event->expects($this->any())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        return $event;
    }
}
