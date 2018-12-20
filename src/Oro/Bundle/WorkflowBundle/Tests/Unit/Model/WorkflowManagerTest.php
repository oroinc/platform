<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Tools\StartedWorkflowsBag;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\VariableManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowApplicabilityFilterInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRecordContext;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class WorkflowManagerTest extends \PHPUnit\Framework\TestCase
{
    const TEST_WORKFLOW_NAME = 'test_workflow';

    /** @var WorkflowManager */
    protected $workflowManager;

    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowRegistry;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var WorkflowEntityConnector|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityConnector;

    /** @var StartedWorkflowsBag */
    protected $startedWorkflowsBag;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    protected function setUp()
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->entityConnector = $this->createMock(WorkflowEntityConnector::class);

        $this->startedWorkflowsBag = new StartedWorkflowsBag();

        $this->workflowManager = new WorkflowManager(
            $this->workflowRegistry,
            $this->doctrineHelper,
            $this->eventDispatcher,
            $this->entityConnector,
            $this->startedWorkflowsBag
        );

        $this->logger = $this->createMock(LoggerInterface::class);

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

        $transitions = new ArrayCollection([$this->createTransition('test_transition')]);

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

        $transition = $this->createTransition('test_transition');

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
        /**@var StepManager|\PHPUnit\Framework\MockObject\MockObject $stepManager */
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
        $transaction = $this->createStartTransition('__start__');
        $workflowItem
            ->setEntity($entity)
            ->setWorkflowName($workflowName);

        $em = $this->getTransactionScopedEntityManager(WorkflowItem::class, 2); //add one more transaction startWorkflow

        $em->expects($this->once())->method('remove')->with($workflowItem);
        $em->expects($this->once())->method('persist')->with($newItem);
        $em->expects($this->exactly(2))->method('flush');

        $workflow = $this->createWorkflow('test_workflow', [], [$transaction]);
        /**@var StepManager|\PHPUnit\Framework\MockObject\MockObject $stepManager */
        $stepManager = $this->getMockBuilder(StepManager::class)->disableOriginalConstructor()->getMock();
        $stepManager->expects($this->once())->method('hasStartStep')->willReturn(true);
        $workflow->expects($this->once())->method('getStepManager')->willReturn($stepManager);
        $workflow->expects($this->once())->method('isActive')->willReturn(true);
        $workflow->expects($this->once())
            ->method('start')
            ->with($entity, [], $workflow->getTransitionManager()->getDefaultStartTransition())
            ->willReturn($newItem);
        $workflow->expects($this->any())->method('isStartTransitionAvailable')->willReturn(true);

        $this->doctrineHelper->expects($this->any())->method('getSingleEntityIdentifier')
            ->willReturnCallback(function (EntityStub $entity) {
                return $entity->getId();
            });

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
     * @return EntityManager|\PHPUnit\Framework\MockObject\MockObject
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

    public function testStartWorkflowEntityWithoutId()
    {
        $entity = new \DateTime();
        $transitionName = 'test_transition';
        $workflowData = ['key' => 'value'];
        $workflowItem = new WorkflowItem();
        $workflowItem->getData()->add($workflowData);

        $transition = $this->createStartTransition($transitionName);

        $workflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME, [], [$transition]);
        $workflow->expects($this->once())
            ->method('start')
            ->with($entity, $workflowData, $transition)
            ->will($this->returnValue($workflowItem));

        $workflow->expects($this->never())->method('isStartTransitionAvailable');

        $this->workflowRegistry->expects($this->once())->method('getWorkflow')
            ->with('test_workflow')->willReturn($workflow);

        $em = $this->getTransactionScopedEntityManager(WorkflowItem::class);

        $em->expects($this->once())
            ->method('persist')
            ->with($workflowItem);
        $em->expects($this->once())
            ->method('flush');

        $this->doctrineHelper->expects($this->any())->method('getSingleEntityIdentifier')
            ->willReturnCallback(function ($entity) {
                return null;
            });

        $actualWorkflowItem = $this->workflowManager->startWorkflow(
            'test_workflow',
            $entity,
            $transition,
            $workflowData
        );

        $this->assertEquals($workflowItem, $actualWorkflowItem);
        $this->assertEquals($workflowData, $actualWorkflowItem->getData()->getValues());
    }

    public function testStartWorkflowEntityWithId()
    {
        $entity = new \stdClass();
        $entity->id = 42;
        $transitionName = 'test_transition';
        $workflowData = ['key' => 'value'];
        $workflowItem = new WorkflowItem();
        $workflowItem->getData()->add($workflowData);

        $transition = $this->createStartTransition($transitionName);

        $workflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME, [], [$transition]);

        $workflow->expects($this->once())
            ->method('start')
            ->with($entity, $workflowData, $transition)
            ->will($this->returnValue($workflowItem));

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test_workflow')
            ->willReturn($workflow);

        $em = $this->getTransactionScopedEntityManager(WorkflowItem::class);
        $em->expects($this->once())->method('persist')->with($workflowItem);
        $em->expects($this->once())->method('flush');

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entity->id);

        $actualWorkflowItem = $this->workflowManager->startWorkflow(
            'test_workflow',
            $entity,
            $transition,
            $workflowData
        );

        $this->assertEquals($workflowItem, $actualWorkflowItem);
        $this->assertEquals($workflowData, $actualWorkflowItem->getData()->getValues());
    }

    public function testStartWorkflowWithInitOptions()
    {
        $entity = new \stdClass();
        $entity->id = 42;
        $transitionName = 'test_transition';
        $workflowData = ['key' => 'value'];
        $workflowItem = new WorkflowItem();
        $workflowItem->getData()->add($workflowData);

        $transition = $this->createTransition($transitionName)->setInitEntities([EntityStub::class])->setStart(true);

        $workflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME, [], [$transition]);

        $workflow->expects($this->once())
            ->method('start')
            ->with($entity, $workflowData, $transition)
            ->will($this->returnValue($workflowItem));

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test_workflow')
            ->willReturn($workflow);

        $em = $this->getTransactionScopedEntityManager(WorkflowItem::class);
        $em->expects($this->once())->method('persist')->with($workflowItem);
        $em->expects($this->once())->method('flush');

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entity->id);

        $actualWorkflowItem = $this->workflowManager->startWorkflow(
            'test_workflow',
            $entity,
            $transitionName,
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
        $transition = $this->createStartTransition('test_transition');
        $workflowItem = new WorkflowItem();

        $this->prepareGetWorkflowItemsByEntity($entity, [$workflowItem]);

        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setExclusiveRecordGroups(['group1']);

        $workflowItem->setDefinition($workflowDefinition);

        $workflow = $this->createWorkflow();
        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);
        $workflow->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('test_workflow');

        $this->workflowManager->startWorkflow(
            $workflow,
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
        $expectedCallsCount = count(
            array_filter(
                $expected,
                function (array $data) {
                    return $data['startTransitionAllowed'];
                }
            )
        );
        $expectedCalls = $expectedCallsCount ? 1 : 0;

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->exactly($expectedCalls))->method('beginTransaction');
        $entityManager->expects($this->exactly($expectedCalls))->method('commit');
        $entityManager->expects($this->exactly($expectedCallsCount))->method('persist');
        $entityManager->expects($this->exactly($expectedCalls))->method('flush');

        if ($expectedCallsCount) {
            $this->doctrineHelper->expects($this->any())->method('getSingleEntityIdentifier')->willReturn(1);
        }

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(WorkflowItem::class)
            ->willReturn($entityManager);


        if ($expected) {
            foreach ($expected as $iteration => $row) {
                $workflowDefinition = new WorkflowDefinition();

                $workflowName = $row['workflow'];
                $workflow = $this->createWorkflow($workflowName, [], [$this->createTransition('start')]);
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
            }
        } else {
            $this->workflowRegistry->expects($this->never())->method('getWorkflow');
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
                    new WorkflowStartArguments('first', $firstEntity, [], $this->createStartTransition('start')),
                    new WorkflowStartArguments(
                        'second',
                        $secondEntity,
                        ['field' => 'value'],
                        $this->createStartTransition('start')
                    ),
                    ['some', 'strange', 'data'],
                ],
                'expected' => [
                    [
                        'workflow' => 'first',
                        'entity' => $firstEntity,
                        'transition' => $this->createStartTransition('start'),
                        'data' => [],
                        'startTransitionAllowed' => true
                    ],
                    [
                        'workflow' => 'second',
                        'entity' => $secondEntity,
                        'transition' => $this->createStartTransition('start'),
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

    public function testGetFirstWorkflowItemByEntity()
    {
        $entity = new EntityStub(123);
        $workflowItem = $this->createWorkflowItem();
        $this->prepareGetWorkflowItemsByEntity($entity, [$workflowItem]);

        $this->assertEquals(
            $workflowItem,
            $this->workflowManager->getFirstWorkflowItemByEntity($entity)
        );
    }

    public function testGetFirstWorkflowItemByEntityNoItem()
    {
        $entity = new EntityStub(123);

        $this->prepareGetWorkflowItemsByEntity($entity, []);

        $this->assertFalse($this->workflowManager->getFirstWorkflowItemByEntity($entity));
    }

    public function testActivateWorkflow()
    {
        $workflowName = 'test_workflow';

        $workflowMock = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        /** @var WorkflowDefinition|\PHPUnit\Framework\MockObject\MockObject $workflowDefinition */
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

        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')->with(
                WorkflowEvents::WORKFLOW_BEFORE_ACTIVATION,
                new WorkflowChangesEvent($workflowDefinition)
            );
        $this->eventDispatcher->expects($this->at(1))
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
        /** @var WorkflowDefinition|\PHPUnit\Framework\MockObject\MockObject $workflowDefinition */
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

        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')->with(
                WorkflowEvents::WORKFLOW_BEFORE_DEACTIVATION,
                new WorkflowChangesEvent($workflowDefinition)
            );
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')->with(
                WorkflowEvents::WORKFLOW_DEACTIVATED,
                new WorkflowChangesEvent($workflowDefinition)
            );

        $this->startedWorkflowsBag->addWorkflowEntity($workflowName, new \stdClass());
        $this->startedWorkflowsBag->addWorkflowEntity($workflowName, new \stdClass());

        $this->assertCount(2, $this->startedWorkflowsBag->getWorkflowEntities($workflowName));

        $this->assertTrue(
            $this->workflowManager->deactivateWorkflow($workflowName),
            'Returns true if workflow has changed its state.'
        );

        $this->assertCount(0, $this->startedWorkflowsBag->getWorkflowEntities($workflowName));
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

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')->with($workflowName)->willReturn($workflowMock);
        $workflowMock->expects($this->once())
            ->method('isActive')
            ->willReturn($isActive);


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
     * @return \PHPUnit\Framework\MockObject\MockObject
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
     * @return Workflow|\PHPUnit\Framework\MockObject\MockObject
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
        $transitionManager->setTransitions($startTransitions);

        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();

        $aclManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Acl\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $restrictionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var VariableManager|\PHPUnit\Framework\MockObject\MockObject $restrictionManager */
        $variableManager = $this->getMockBuilder(VariableManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->setConstructorArgs([
                $doctrineHelper,
                    $aclManager,
                    $restrictionManager,
                    null,
                    $attributeManager,
                    $transitionManager,
                    $variableManager
                ])
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

        $this->startedWorkflowsBag->addWorkflowEntity($name, new \stdClass());
        $this->startedWorkflowsBag->addWorkflowEntity($name, new \stdClass());

        $this->assertCount(2, $this->startedWorkflowsBag->getWorkflowEntities($name));

        $this->workflowManager->resetWorkflowData($name);

        $this->assertCount(0, $this->startedWorkflowsBag->getWorkflowEntities($name));
    }

    /**
     * @return Transition
     */
    private function getStartTransition()
    {
        return $this->createTransition('__start__')->setStart(true);
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

        $this->doctrineHelper->expects($this->any())->method('getSingleEntityIdentifier')
            ->willReturnCallback(function ($entityParam) use ($entity) {
                return $entityParam === $entity ? $entity->getId() : null;
            });

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

    /**
     * @param string $name
     *
     * @return Transition
     */
    private function createTransition($name)
    {
        $transition = new Transition($this->createMock(TransitionOptionsResolver::class));

        return $transition->setName($name);
    }

    /**
     * @param string $name
     *
     * @return Transition
     */
    private function createStartTransition($name)
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())->method('getName')->willReturn($name);
        $transition->expects($this->any())->method('isStart')->willReturn(true);

        return $transition;
    }
}
