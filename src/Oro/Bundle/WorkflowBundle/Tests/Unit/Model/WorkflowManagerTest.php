<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowApplicabilityFilterInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRecordContext;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class WorkflowManagerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_WORKFLOW_NAME = 'test_workflow';

    /** @var WorkflowManager */
    protected $workflowManager;

    /** @var WorkflowRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowRegistry;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var WorkflowEntityConnector|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityConnector;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    protected function setUp()
    {
        $this->workflowRegistry = $this->getMockBuilder(WorkflowRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->entityConnector = $this->getMockBuilder(WorkflowEntityConnector::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMock(LoggerInterface::class);

        $this->workflowManager = new WorkflowManager(
            $this->workflowRegistry,
            $this->doctrineHelper,
            $this->eventDispatcher,
            $this->entityConnector
        );
        $this->workflowManager->setLogger($this->logger);
    }

    protected function tearDown()
    {
        unset(
            $this->workflowManager,
            $this->workflowRegistry,
            $this->doctrineHelper,
            $this->eventDispatcher,
            $this->entityConnector,
            $this->logger
        );
    }

    /**
     * @param mixed $workflowIdentifier
     * @dataProvider getWorkflowDataProvider
     */
    public function testGetWorkflow($workflowIdentifier)
    {
        $expectedWorkflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME);

        if ($workflowIdentifier instanceof Workflow) {
            $this->workflowRegistry->expects($this->never())
                ->method('getWorkflow');
        } else {
            $this->workflowRegistry->expects($this->any())
                ->method('getWorkflow')
                ->with(self::TEST_WORKFLOW_NAME)
                ->will($this->returnValue($expectedWorkflow));
        }

        $this->assertEquals($expectedWorkflow, $this->workflowManager->getWorkflow($workflowIdentifier));
    }

    /**
     * @return array
     */
    public function getWorkflowDataProvider()
    {
        return [
            'string' => [
                'workflowIdentifier' => self::TEST_WORKFLOW_NAME,
            ],
            'workflow item' => [
                'workflowIdentifier' => $this->createWorkflowItem(self::TEST_WORKFLOW_NAME),
            ],
            'workflow' => [
                'workflowIdentifier' => $this->createWorkflow(self::TEST_WORKFLOW_NAME),
            ],
        ];
    }

    public function testGetTransitionsByWorkflowItem()
    {
        $workflowName = 'test_workflow';

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName);

        $transition = new Transition();
        $transition->setName('test_transition');

        $transitions = new ArrayCollection([$transition]);

        $workflow = $this->createWorkflow($workflowName);
        $workflow->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->will($this->returnValue($transitions));

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->will($this->returnValue($workflow));

        $this->assertEquals(
            $transitions,
            $this->workflowManager->getTransitionsByWorkflowItem($workflowItem)
        );
    }

    public function testIsTransitionAvailable()
    {
        $workflowName = 'test_workflow';

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName);

        $errors = new ArrayCollection();

        $transition = new Transition();
        $transition->setName('test_transition');

        $workflow = $this->createWorkflow($workflowName);
        $workflow->expects($this->once())
            ->method('isTransitionAvailable')
            ->with($workflowItem, $transition, $errors)
            ->will($this->returnValue(true));

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->will($this->returnValue($workflow));

        $this->assertTrue($this->workflowManager->isTransitionAvailable($workflowItem, $transition, $errors));
    }

    public function testIsStartTransitionAvailable()
    {
        $workflowName = 'test_workflow';
        $errors = new ArrayCollection();
        $entity = new \DateTime('now');
        $data = [];

        $entityAttribute = new Attribute();
        $entityAttribute->setName('entity_attribute');
        $entityAttribute->setType('entity');
        $entityAttribute->setOptions(['class' => 'DateTime']);

        $stringAttribute = new Attribute();
        $stringAttribute->setName('other_attribute');
        $stringAttribute->setType('string');

        $transition = 'test_transition';

        $workflow = $this->createWorkflow($workflowName, [$entityAttribute, $stringAttribute]);
        $workflow->expects($this->once())
            ->method('isStartTransitionAvailable')
            ->with($transition, $entity, $data, $errors)
            ->will($this->returnValue(true));

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->will($this->returnValue($workflow));

        $this->assertTrue(
            $this->workflowManager->isStartTransitionAvailable($workflowName, $transition, $entity, $data, $errors)
        );
    }

    public function testResetWorkflowItemWithoutStartStep()
    {
        $workflowItem = new WorkflowItem();
        $workflowName = 'test_workflow';
        $entity = new EntityStub(42);
        $workflowItem
            ->setEntity($entity)
            ->setWorkflowName($workflowName);

        $em = $this->getTransactionScopedEntityManager(WorkflowItem::class, 1);

        $em->expects($this->once())->method('remove')->with($workflowItem);
        $em->expects($this->once())->method('flush');

        $workflow = $this->createWorkflow('test_workflow');
        /**@var StepManager|\PHPUnit_Framework_MockObject_MockObject $stepManager */
        $stepManager = $this->getMockBuilder(StepManager::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->once())->method('isActive')->willReturn(true);
        $workflow->expects($this->once())->method('getStepManager')->willReturn($stepManager);
        $stepManager->expects($this->once())->method('hasStartStep')->willReturn(false);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test_workflow')
            ->willReturn($workflow);

        $this->workflowManager->resetWorkflowItem($workflowItem);
    }

    public function testResetWorkflowItemWithStartStep()
    {
        $workflowItem = new WorkflowItem();
        $newItem = new WorkflowItem();
        $workflowName = 'test_workflow';
        $entity = new EntityStub(42);
        $workflowItem
            ->setEntity($entity)
            ->setWorkflowName($workflowName);

        $em = $this->getTransactionScopedEntityManager(WorkflowItem::class, 2); //add one more transaction startWorkflow

        $em->expects($this->once())->method('remove')->with($workflowItem);
        $em->expects($this->once())->method('persist')->with($newItem);
        $em->expects($this->exactly(2))->method('flush');

        $workflow = $this->createWorkflow('test_workflow');
        /**@var StepManager|\PHPUnit_Framework_MockObject_MockObject $stepManager */
        $stepManager = $this->getMockBuilder(StepManager::class)->disableOriginalConstructor()->getMock();
        $stepManager->expects($this->once())->method('hasStartStep')->willReturn(true);
        $workflow->expects($this->once())->method('getStepManager')->willReturn($stepManager);
        $workflow->expects($this->once())->method('isActive')->willReturn(true);
        $workflow->expects($this->once())
            ->method('start')
            ->with($entity, [], $workflow->getTransitionManager()->getDefaultStartTransition())
            ->willReturn($newItem);
        $workflow->expects($this->once())->method('isStartTransitionAvailable')->willReturn(true);

        $workflowDefinition = new WorkflowDefinition();
        $workflow->expects($this->once())->method('getDefinition')->willReturn($workflowDefinition);

        $this->workflowRegistry->expects($this->exactly(2))
            ->method('getWorkflow')
            ->with('test_workflow')
            ->willReturn($workflow);

        $item = $this->workflowManager->resetWorkflowItem($workflowItem);
        $this->assertSame($newItem, $item, 'should return item created when workflow->start invoked');
    }

    /**
     * @param string $manageableEntityClass
     * @param int $transactionDepth
     * @return EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getTransactionScopedEntityManager($manageableEntityClass, $transactionDepth = 1)
    {
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $this->doctrineHelper->expects($this->exactly($transactionDepth))
            ->method('getEntityManagerForClass')
            ->with($manageableEntityClass)
            ->willReturn($entityManager);
        $entityManager->expects($this->exactly($transactionDepth))->method('beginTransaction');
        $entityManager->expects($this->exactly($transactionDepth))->method('commit');

        return $entityManager;
    }

    public function testGetApplicableWorkflowsNotApplicableEntity()
    {
        $entity = new EntityStub(42);
        $this->entityConnector->expects($this->once())->method('isApplicableEntity')->with($entity)->willReturn(false);
        $this->assertEquals([], $this->workflowManager->getApplicableWorkflows($entity));
    }

    public function testGetApplicableWorkflows()
    {
        $filterMock = $this->getMockBuilder(WorkflowApplicabilityFilterInterface::class)->getMock();
        $entity = new EntityStub(42);
        $workflow1 = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow2 = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();

        $this->entityConnector->expects($this->once())->method('isApplicableEntity')->with($entity)->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn(EntityStub::class);

        $activeWorkflows = new ArrayCollection(['w1' => $workflow1, 'w2' => $workflow2]);
        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with(EntityStub::class)
            ->willReturn($activeWorkflows);

        $filterMock->expects($this->once())
            ->method('filter')
            ->with($activeWorkflows, new WorkflowRecordContext($entity))
            ->willReturn(new ArrayCollection(['w1' => $workflow1]));

        $this->workflowManager->addApplicabilityFilter($filterMock);
        $this->assertEquals(['w1' => $workflow1], $this->workflowManager->getApplicableWorkflows($entity));
    }

    public function testHasApplicableWorkflowsTrue()
    {
        $entity = new \DateTime('now');
        $entityClass = get_class($entity);
        $workflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME);

        $this->entityConnector->expects($this->once())->method('isApplicableEntity')->with($entity)->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->will($this->returnValue($entityClass));

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with($entityClass)
            ->will($this->returnValue(new ArrayCollection([$workflow])));

        $this->assertTrue($this->workflowManager->hasApplicableWorkflows($entity));
    }

    public function testHasApplicableWorkflowsFalse()
    {
        $entity = new \DateTime('now');
        $entityClass = get_class($entity);

        $this->entityConnector->expects($this->once())->method('isApplicableEntity')->with($entity)->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->will($this->returnValue($entityClass));

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with($entityClass)
            ->will($this->returnValue(new ArrayCollection([])));

        $this->assertFalse($this->workflowManager->hasApplicableWorkflows($entity));
    }

    public function testStartWorkflow()
    {
        $entity = new \DateTime();
        $transition = 'test_transition';
        $workflowData = ['key' => 'value'];
        $workflowItem = new WorkflowItem();
        $workflowItem->getData()->add($workflowData);

        $workflowDefinition = new WorkflowDefinition();
        $workflow = $this->createWorkflow();

        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);

        $workflow->expects($this->once())
            ->method('start')
            ->with($entity, $workflowData, $transition)
            ->will($this->returnValue($workflowItem));

        $this->workflowRegistry->expects($this->once())->method('getWorkflow')
            ->with('test_workflow')->willReturn($workflow);

        $em = $this->getTransactionScopedEntityManager(WorkflowItem::class);

        $em->expects($this->once())
            ->method('persist')
            ->with($workflowItem);
        $em->expects($this->once())
            ->method('flush');

        $actualWorkflowItem = $this->workflowManager->startWorkflow(
            'test_workflow',
            $entity,
            $transition,
            $workflowData
        );

        $this->assertEquals($workflowItem, $actualWorkflowItem);
        $this->assertEquals($workflowData, $actualWorkflowItem->getData()->getValues());
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowRecordGroupException
     * @expectedExceptionMessage Workflow "test_workflow" can not be started because it belongs to
     */
    public function testStartWorkflowRecordGroupException()
    {
        $entity = new EntityStub(1);
        $transition = 'test_transition';
        $workflowItem = new WorkflowItem();

        $this->prepareGetWorkflowItemsByEntity($entity, [$workflowItem]);

        $workflowDefinition = new WorkflowDefinition();
        $workflowItem->setDefinition($workflowDefinition);
        $workflowDefinition->setExclusiveRecordGroups(['group1']);
        $workflow = $this->createWorkflow();

        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);
        $workflow->expects($this->once())
            ->method('getName')
            ->willReturn('test_workflow');

        $this->workflowRegistry->expects($this->once())->method('getWorkflow')
            ->with('test_workflow')->willReturn($workflow);

        $this->workflowManager->startWorkflow(
            'test_workflow',
            $entity,
            $transition
        );
    }

    /**
     * @param array $source
     * @param array $expected
     * @dataProvider massStartDataProvider
     */
    public function testMassStartWorkflow(array $source, array $expected)
    {
        $expectedCalls = count(
            array_filter(
                $expected,
                function (array $data) {
                    return $data['startTransitionAllowed'];
                }
            )
        );

        $entityManager = $this->getTransactionScopedEntityManager(WorkflowItem::class, $expectedCalls);

        if ($expected) {
            $emIterator = 0;

            foreach ($expected as $iteration => $row) {
                $workflowDefinition = new WorkflowDefinition();

                $workflowName = $row['workflow'];
                $workflow = $this->createWorkflow($workflowName);
                $workflow->expects($this->any())
                    ->method('isStartTransitionAvailable')
                    ->willReturn($row['startTransitionAllowed']);

                $workflow->expects($this->any())
                    ->method('getDefinition')
                    ->willReturn($workflowDefinition);

                $workflowItem = $this->createWorkflowItem($workflowName);

                $workflow->expects($this->exactly((int)$row['startTransitionAllowed']))->method('start')
                    ->with($row['entity'], $row['data'], $row['transition'])
                    ->will($this->returnValue($workflowItem));

                $this->workflowRegistry->expects($this->at($iteration))
                    ->method('getWorkflow')
                    ->with($workflowName)
                    ->will($this->returnValue($workflow));

                if ($row['startTransitionAllowed']) {
                    $entityManager->expects($this->at(++$emIterator))->method('persist')->with($workflowItem);
                    $entityManager->expects($this->at(++$emIterator))->method('flush');
                    $emIterator += 2; //transaction methods calls
                }
            }
        } else {
            $this->workflowRegistry->expects($this->never())->method('getWorkflow');
            $entityManager->expects($this->never())->method('persist');
            $entityManager->expects($this->never())->method('flush');
        }

        $this->workflowManager->massStartWorkflow($source);
    }

    /**
     * @return array
     */
    public function massStartDataProvider()
    {
        $firstEntity = new \DateTime('2012-12-12');
        $secondEntity = new \DateTime('2012-12-13');

        return [
            'no data' => [
                'source' => [],
                'expected' => [],
            ],
            'regular data' => [
                'source' => [
                    new WorkflowStartArguments('first', $firstEntity),
                    new WorkflowStartArguments('second', $secondEntity),
                ],
                'expected' => [
                    [
                        'workflow' => 'first',
                        'entity' => $firstEntity,
                        'transition' => $this->getStartTransition(),
                        'data' => [],
                        'startTransitionAllowed' => true
                    ],
                    [
                        'workflow' => 'second',
                        'entity' => $secondEntity,
                        'transition' => $this->getStartTransition(),
                        'data' => [],
                        'startTransitionAllowed' => false
                    ],
                ],
            ],
            'extra cases' => [
                'source' => [
                    new WorkflowStartArguments('first', $firstEntity, [], 'start'),
                    new WorkflowStartArguments('second', $secondEntity, ['field' => 'value'], 'start'),
                    ['some', 'strange', 'data'],
                ],
                'expected' => [
                    [
                        'workflow' => 'first',
                        'entity' => $firstEntity,
                        'transition' => 'start',
                        'data' => [],
                        'startTransitionAllowed' => true
                    ],
                    [
                        'workflow' => 'second',
                        'entity' => $secondEntity,
                        'transition' => 'start',
                        'data' => ['field' => 'value'],
                        'startTransitionAllowed' => true
                    ],
                ],
            ]
        ];
    }

    public function testTransit()
    {
        $transition = 'test_transition';
        $workflowName = 'test_workflow';

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName);

        $workflow = $this->createWorkflow($workflowName);
        $workflow->expects($this->once())
            ->method('transit')
            ->with($workflowItem, $transition);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->will($this->returnValue($workflow));

        $this->logger->expects($this->once())
            ->method('info')
            ->willReturnCallback(
                function ($message, array $context) use ($workflow, $workflowItem, $transition) {
                    $this->assertEquals('Workflow transition is complete', $message);
                    $this->assertArrayHasKey('workflow', $context);
                    $this->assertEquals($workflow, $context['workflow']);
                    $this->assertArrayHasKey('workflowItem', $context);
                    $this->assertEquals($workflowItem, $context['workflowItem']);
                    $this->assertArrayHasKey('transition', $context);
                    $this->assertEquals($transition, $context['transition']);
                }
            );

        $entityManager = $this->getTransactionScopedEntityManager(WorkflowItem::class);

        $entityManager->expects($this->once())
            ->method('flush');

        $this->assertEmpty($workflowItem->getUpdated());
        $this->workflowManager->transit($workflowItem, $transition);
        $this->assertNotEmpty($workflowItem->getUpdated());
    }

    public function testTransitIfAllowed()
    {
        $transition = 'test_transition';
        $workflowName = 'test_workflow';

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName);

        $workflow = $this->createWorkflow($workflowName);
        $workflow->expects($this->once())
            ->method('transit')
            ->with($workflowItem, $transition);
        $workflow->expects($this->once())
            ->method('isTransitionAllowed')->with($workflowItem, $transition)->willReturn(true);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->will($this->returnValue($workflow));

        $entityManager = $this->getTransactionScopedEntityManager(WorkflowItem::class);

        $entityManager->expects($this->once())
            ->method('flush');

        $this->assertEmpty($workflowItem->getUpdated());
        $this->assertTrue(
            $this->workflowManager->transitIfAllowed($workflowItem, $transition),
            'If transit is allowed for current WorkflowItem and transition then TRUE expected after transition success.'
        );
        $this->assertNotEmpty($workflowItem->getUpdated());
    }

    public function testTransitIfAllowedFalse()
    {
        $transition = 'test_transition';
        $workflowName = 'test_workflow';

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName);

        $workflow = $this->createWorkflow($workflowName);

        $workflow->expects($this->once())
            ->method('isTransitionAllowed')
            ->with($workflowItem, $transition)
            ->willReturn(false);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->will($this->returnValue($workflow));

        $this->assertEmpty($workflowItem->getUpdated());
        $this->assertFalse(
            $this->workflowManager->transitIfAllowed($workflowItem, $transition),
            'If transit is NOT allowed for current WorkflowItem and transition then FALSE expected.'
        );
        $this->assertEmpty($workflowItem->getUpdated());
    }

    /**
     * @param array $source
     * @param array $expected
     * @dataProvider massTransitDataProvider
     */
    public function testMassTransit(array $source, array $expected)
    {
        $entityManager = $this->getTransactionScopedEntityManager(WorkflowItem::class);

        /** @var WorkflowItem[] $workflowItems */
        $workflowItems = [];

        if ($expected) {
            foreach ($expected as $iteration => $row) {
                $workflowName = $row['workflow'];
                $workflow = $this->createWorkflow($workflowName);
                $workflowItem = $source[$iteration]['workflowItem'];
                $workflowItems[] = $workflowItem;
                $transition = $row['transition'];

                $workflow->expects($this->once())->method('transit')->with($workflowItem, $transition)
                    ->willReturn($workflowItem);

                $this->workflowRegistry->expects($this->at($iteration))->method('getWorkflow')->with($workflowName)
                    ->willReturn($workflow);
            }
        } else {
            $this->workflowRegistry->expects($this->never())->method('getWorkflow');
        }

        $entityManager->expects($this->once())->method('flush');

        $this->workflowManager->massTransit($source);

        foreach ($workflowItems as $workflowItem) {
            $this->assertNotEmpty($workflowItem->getUpdated());
        }
    }

    /**
     * @return array
     */
    public function massTransitDataProvider()
    {
        return [
            'no data' => [
                'source' => [],
                'expected' => []
            ],
            'invalid data' => [
                'source' => [
                    ['transition' => 'test'],
                    ['workflowItem' => null, 'transition' => 'test'],
                    ['workflowItem' => new \stdClass(), 'transition' => 'test'],
                    ['workflowItem' => $this->createWorkflowItem('test'), 'transition' => null],
                    ['workflowItem' => $this->createWorkflowItem('test')],
                ],
                'expected' => []
            ],
            'valid data' => [
                'source' => [
                    ['workflowItem' => $this->createWorkflowItem('flow1'), 'transition' => 'transition1'],
                    ['workflowItem' => $this->createWorkflowItem('flow2'), 'transition' => 'transition2'],
                ],
                'expected' => [
                    ['workflow' => 'flow1', 'transition' => 'transition1'],
                    ['workflow' => 'flow2', 'transition' => 'transition2'],
                ],
            ]
        ];
    }

    /**
     * @dataProvider getWorkflowItemDataProvider
     *
     * @param int|string $id
     */
    public function testGetWorkflowItem($id)
    {
        $entity = new EntityStub($id);
        $workflowName = 'test_workflow';

        $this->entityConnector->expects($this->once())
            ->method('isApplicableEntity')->with($entity)->willReturn(true);

        $this->doctrineHelper->expects($this->once())->method('getEntityClass')->with($entity)
            ->willReturn(EntityStub::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')->with($entity)->willReturn($id);

        $repository = $this->getMockBuilder(WorkflowItemRepository::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->with(WorkflowItem::class)
            ->willReturn($repository);

        $repository->expects($this->once())->method('findOneByEntityMetadata')
            ->with(EntityStub::class, $id, $workflowName)
            ->willReturn('result');
        $result = $this->workflowManager->getWorkflowItem($entity, $workflowName);

        $this->assertEquals('result', $result);
    }

    public function testGetWorkflowItemFromNotApplicableEntity()
    {
        $entity = new EntityStub(42);
        $workflowName = 'test_workflow';

        $this->entityConnector->expects($this->once())
            ->method('isApplicableEntity')->with($entity)->willReturn(false);

        $this->assertNull($this->workflowManager->getWorkflowItem($entity, $workflowName));
    }

    /**
     * @return array
     */
    public function getWorkflowItemDataProvider()
    {
        return [
            [42],
            ['string']
        ];
    }

    /**
     * @param mixed $entityId
     * @param WorkflowItem[] $workflowItems
     *
     * @dataProvider entityDataProvider
     */
    public function testGetWorkflowItemsByEntity($entityId, array $workflowItems = [])
    {
        $entity = new EntityStub($entityId);
        $this->prepareGetWorkflowItemsByEntity($entity, $workflowItems);

        $this->assertEquals(
            $workflowItems,
            $this->workflowManager->getWorkflowItemsByEntity($entity)
        );
    }

    /**
     * @return array
     */
    public function entityDataProvider()
    {
        return [
            'integer' => [1, [$this->createWorkflowItem()]],
            'integer_as_string' => ['123', [$this->createWorkflowItem()]],
            'string' => ['identifier', []],
            'null' => [null, []],
            'object' => [new \stdClass(), []],
        ];
    }

    public function testActivateWorkflow()
    {
        $workflowName = 'test_workflow';

        $workflowMock = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        /** @var WorkflowDefinition|\PHPUnit_Framework_MockObject_MockObject $workflowDefinition */
        $workflowDefinition = $this->getMockBuilder(WorkflowDefinition::class)->getMock();
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')->with($workflowName)->willReturn($workflowMock);
        $workflowMock->expects($this->once())
            ->method('getDefinition')->willReturn($workflowDefinition);

        $workflowDefinition->expects($this->once())
            ->method('isActive')->willReturn(false);

        $workflowDefinition->expects($this->once())
            ->method('setActive')->with(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')->with(WorkflowDefinition::class)->willReturn($entityManager);
        $entityManager->expects($this->once())->method('flush')->with($workflowDefinition);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')->with(
                WorkflowEvents::WORKFLOW_ACTIVATED,
                new WorkflowChangesEvent($workflowDefinition)
            );

        $this->assertTrue(
            $this->workflowManager->activateWorkflow($workflowName),
            'Returns true if workflow has changed its state.'
        );
    }

    public function testActivateWorkflowSkipIfAlreadyActive()
    {
        $workflowName = 'test_workflow';

        $workflowMock = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflowDefinition = $this->getMockBuilder(WorkflowDefinition::class)->getMock();
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')->with($workflowName)->willReturn($workflowMock);
        $workflowMock->expects($this->once())
            ->method('getDefinition')->willReturn($workflowDefinition);

        $workflowDefinition->expects($this->once())
            ->method('isActive')->willReturn(true);

        $workflowDefinition->expects($this->never())->method('setActive');
        $this->doctrineHelper->expects($this->never())->method('getEntityManager');
        $entityManager->expects($this->never())->method('flush');

        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->assertFalse(
            $this->workflowManager->activateWorkflow($workflowName),
            'Returns false if workflow has not change its state.'
        );
    }

    public function testDeactivateWorkflow()
    {
        $workflowName = 'test_workflow';

        $workflowMock = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        /** @var WorkflowDefinition|\PHPUnit_Framework_MockObject_MockObject $workflowDefinition */
        $workflowDefinition = $this->getMockBuilder(WorkflowDefinition::class)->getMock();
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')->with($workflowName)->willReturn($workflowMock);
        $workflowMock->expects($this->once())
            ->method('getDefinition')->willReturn($workflowDefinition);

        $workflowDefinition->expects($this->once())
            ->method('isActive')->willReturn(true);

        $workflowDefinition->expects($this->once())
            ->method('setActive')->with(false);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')->with(WorkflowDefinition::class)->willReturn($entityManager);
        $entityManager->expects($this->once())->method('flush')->with($workflowDefinition);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')->with(
                WorkflowEvents::WORKFLOW_DEACTIVATED,
                new WorkflowChangesEvent($workflowDefinition)
            );

        $this->assertTrue(
            $this->workflowManager->deactivateWorkflow($workflowName),
            'Returns true if workflow has changed its state.'
        );
    }

    public function testDeactivateWorkflowSkipIfNotActive()
    {
        $workflowName = 'test_workflow';

        $workflowMock = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflowDefinition = $this->getMockBuilder(WorkflowDefinition::class)->getMock();
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')->with($workflowName)->willReturn($workflowMock);
        $workflowMock->expects($this->once())
            ->method('getDefinition')->willReturn($workflowDefinition);

        $workflowDefinition->expects($this->once())
            ->method('isActive')->willReturn(false);

        $workflowDefinition->expects($this->never())->method('setActive');
        $this->doctrineHelper->expects($this->never())->method('getEntityManager');
        $entityManager->expects($this->never())->method('flush');

        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->assertFalse(
            $this->workflowManager->deactivateWorkflow($workflowName),
            'Returns false if workflow has not change its state.'
        );
    }

    /**
     * @dataProvider isActiveDataProvider
     * @param boolean $isActive
     */
    public function testIsActiveWorkflow($isActive)
    {
        $workflowName = 'test_workflow';

        $workflowMock = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflowDefinition = $this->getMockBuilder(WorkflowDefinition::class)->getMock();

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')->with($workflowName)->willReturn($workflowMock);
        $workflowMock->expects($this->once())
            ->method('getDefinition')->willReturn($workflowDefinition);

        $workflowDefinition->expects($this->once())
            ->method('isActive')->willReturn($isActive);

        $this->assertEquals($isActive, $this->workflowManager->isActiveWorkflow($workflowName));
    }

    /**
     * @return array
     */
    public function isActiveDataProvider()
    {
        return [[true], [false]];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEntityManager()
    {
        return $this->getMockBuilder('Doctrine\Orm\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'remove', 'persist', 'flush', 'commit', 'rollback'])
            ->getMock();
    }

    /**
     * @param string $workflowName
     * @return WorkflowItem
     */
    protected function createWorkflowItem($workflowName = self::TEST_WORKFLOW_NAME)
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName);

        return $workflowItem;
    }

    /**
     * @param string $name
     * @param array $entityAttributes
     * @param array $startTransitions
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createWorkflow(
        $name = self::TEST_WORKFLOW_NAME,
        array $entityAttributes = [],
        array $startTransitions = []
    ) {
        $attributeManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\AttributeManager')
            ->setMethods(['getManagedEntityAttributes'])
            ->getMock();
        $attributeManager->expects($this->any())
            ->method('getManagedEntityAttributes')
            ->will($this->returnValue($entityAttributes));

        $transitionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\TransitionManager')
            ->setMethods(['getStartTransitions', 'getDefaultStartTransition'])
            ->getMock();
        $transitionManager->expects($this->any())
            ->method('getStartTransitions')
            ->will($this->returnValue(new ArrayCollection($startTransitions)));
        $transitionManager->expects($this->any())
            ->method('getDefaultStartTransition')
            ->willReturn($this->getStartTransition());

        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();

        $aclManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Acl\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $restrictionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->setConstructorArgs(
                [$doctrineHelper, $aclManager, $restrictionManager, null, $attributeManager, $transitionManager]
            )
            ->setMethods(
                [
                    'isTransitionAllowed',
                    'isTransitionAvailable',
                    'isStartTransitionAvailable',
                    'getTransitionsByWorkflowItem',
                    'start',
                    'isActive',
                    'getDefinition',
                    'getName',
                    'getStepManager',
                    'transit'
                ]
            )
            ->getMock();

        $workflow->expects($this->any())->method('getName')->willReturn($name);

        /** @var Workflow $workflow */
        return $workflow;
    }

    /**
     * @return array
     */
    public function trueFalseDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }

    public function testResetWorkflowData()
    {
        $name = 'testWorkflow';
        $entityClass = 'Test:Entity';

        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName($name)->setRelatedEntity($entityClass);

        $workflowItemsRepository =
            $this->getMockBuilder(WorkflowItemRepository::class)
                ->disableOriginalConstructor()
                ->setMethods(['resetWorkflowData'])
                ->getMock();
        $workflowItemsRepository->expects($this->once())->method('resetWorkflowData')
            ->with($name);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(WorkflowItem::class)
            ->will($this->returnValue($workflowItemsRepository));

        $this->workflowManager->resetWorkflowData($name);
    }

    /**
     * @return Transition
     */
    private function getStartTransition()
    {
        $startTransition = new Transition();
        $startTransition->setName('__start__');
        $startTransition->setStart(true);

        return $startTransition;
    }

    /**
     * @param object $entity
     * @param array|WorkflowItem[] $workflowItems
     */
    private function prepareGetWorkflowItemsByEntity($entity, $workflowItems)
    {
        $this->entityConnector->expects($this->once())->method('isApplicableEntity')->willReturn(true);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($entity)
            ->will($this->returnValue(EntityStub::class));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue($entity->getId()));

        $workflowItemsRepository = $this->getMockBuilder(WorkflowItemRepository::class)
                ->disableOriginalConstructor()
                ->setMethods(['findAllByEntityMetadata'])
                ->getMock();
        $workflowItemsRepository->expects($this->any())
            ->method('findAllByEntityMetadata')
            ->with(EntityStub::class, $entity->getId())
            ->will($this->returnValue($workflowItems));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(WorkflowItem::class)
            ->will($this->returnValue($workflowItemsRepository));
    }
}
