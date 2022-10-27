<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActionBundle\Model\AttributeManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowRecordGroupException;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Tools\StartedWorkflowsBag;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\VariableManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowApplicabilityFilterInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRecordContext;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
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
    private const TEST_WORKFLOW_NAME = 'test_workflow';

    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowRegistry;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var WorkflowEntityConnector|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConnector;

    /** @var StartedWorkflowsBag */
    private $startedWorkflowsBag;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var WorkflowManager */
    private $workflowManager;

    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->entityConnector = $this->createMock(WorkflowEntityConnector::class);
        $this->startedWorkflowsBag = new StartedWorkflowsBag();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->workflowManager = new WorkflowManager(
            $this->workflowRegistry,
            $this->doctrineHelper,
            $this->eventDispatcher,
            $this->entityConnector,
            $this->startedWorkflowsBag
        );
        $this->workflowManager->setLogger($this->logger);
    }

    /**
     * @dataProvider getWorkflowDataProvider
     */
    public function testGetWorkflow(string|Workflow|WorkflowItem $workflowIdentifier): void
    {
        $expectedWorkflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME);

        if ($workflowIdentifier instanceof Workflow) {
            $this->workflowRegistry->expects($this->never())
                ->method('getWorkflow');
        } else {
            $this->workflowRegistry->expects($this->any())
                ->method('getWorkflow')
                ->with(self::TEST_WORKFLOW_NAME)
                ->willReturn($expectedWorkflow);
        }

        $this->assertEquals($expectedWorkflow, $this->workflowManager->getWorkflow($workflowIdentifier));
    }

    public function getWorkflowDataProvider(): array
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

    public function testGetTransitionsByWorkflowItem(): void
    {
        $workflowName = 'test_workflow';

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName);

        $transitions = new ArrayCollection([$this->createTransition('test_transition')]);

        $workflow = $this->createWorkflow($workflowName);
        $workflow->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->willReturn($transitions);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);

        $this->assertEquals(
            $transitions,
            $this->workflowManager->getTransitionsByWorkflowItem($workflowItem)
        );
    }

    public function testIsTransitionAvailable(): void
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
            ->willReturn(true);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);

        $this->assertTrue($this->workflowManager->isTransitionAvailable($workflowItem, $transition, $errors));
    }

    public function testIsStartTransitionAvailable(): void
    {
        $workflowName = 'test_workflow';
        $errors = new ArrayCollection();
        $entity = new \DateTime('now');
        $data = [];

        $transition = 'test_transition';

        $workflow = $this->createWorkflow($workflowName);
        $workflow->expects($this->once())
            ->method('isStartTransitionAvailable')
            ->with($transition, $entity, $data, $errors)
            ->willReturn(true);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);

        $this->assertTrue(
            $this->workflowManager->isStartTransitionAvailable($workflowName, $transition, $entity, $data, $errors)
        );
    }

    public function testResetWorkflowItemWithoutStartStep(): void
    {
        $workflowItem = new WorkflowItem();
        $workflowName = 'test_workflow';
        $entity = new EntityStub(42);
        $workflowItem
            ->setEntity($entity)
            ->setWorkflowName($workflowName);

        $em = $this->getTransactionScopedEntityManager(WorkflowItem::class, 1);

        $em->expects($this->once())
            ->method('remove')
            ->with($workflowItem);
        $em->expects($this->once())
            ->method('flush');

        $workflow = $this->createWorkflow('test_workflow');
        $stepManager = $this->createMock(StepManager::class);
        $workflow->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $workflow->expects($this->once())
            ->method('getStepManager')
            ->willReturn($stepManager);
        $stepManager->expects($this->once())
            ->method('hasStartStep')
            ->willReturn(false);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test_workflow')
            ->willReturn($workflow);

        $this->workflowManager->resetWorkflowItem($workflowItem);
    }

    public function testResetWorkflowItemWithStartStep(): void
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

        $em->expects($this->once())
            ->method('remove')
            ->with($workflowItem);
        $em->expects($this->once())
            ->method('persist')
            ->with($newItem);
        $em->expects($this->exactly(2))
            ->method('flush');

        $workflow = $this->createWorkflow('test_workflow', [$transaction]);
        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects($this->once())
            ->method('hasStartStep')
            ->willReturn(true);
        $workflow->expects($this->once())
            ->method('getStepManager')
            ->willReturn($stepManager);
        $workflow->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $workflow->expects($this->once())
            ->method('start')
            ->with($entity, [], $workflow->getTransitionManager()->getDefaultStartTransition())
            ->willReturn($newItem);
        $workflow->expects($this->any())
            ->method('isStartTransitionAvailable')
            ->willReturn(true);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
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
     * @return EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getTransactionScopedEntityManager(string $manageableEntityClass, int $transactionDepth = 1)
    {
        $entityManager = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects($this->exactly($transactionDepth))
            ->method('getEntityManagerForClass')
            ->with($manageableEntityClass)
            ->willReturn($entityManager);
        $entityManager->expects($this->exactly($transactionDepth))
            ->method('beginTransaction');
        $entityManager->expects($this->exactly($transactionDepth))
            ->method('commit');

        return $entityManager;
    }

    public function testGetApplicableWorkflowsNotApplicableEntity(): void
    {
        $entity = new EntityStub(42);
        $this->entityConnector->expects($this->once())
            ->method('isApplicableEntity')
            ->with($entity)
            ->willReturn(false);
        $this->assertEquals([], $this->workflowManager->getApplicableWorkflows($entity));
    }

    public function testGetApplicableWorkflows(): void
    {
        $filterMock = $this->createMock(WorkflowApplicabilityFilterInterface::class);
        $entity = new EntityStub(42);
        $workflow1 = $this->createMock(Workflow::class);
        $workflow2 = $this->createMock(Workflow::class);

        $this->entityConnector->expects($this->once())
            ->method('isApplicableEntity')
            ->with($entity)
            ->willReturn(true);

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

    public function testHasApplicableWorkflowsTrue(): void
    {
        $entity = new \DateTime('now');
        $entityClass = get_class($entity);
        $workflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME);

        $this->entityConnector->expects($this->once())
            ->method('isApplicableEntity')
            ->with($entity)
            ->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with($entityClass)
            ->willReturn(new ArrayCollection([$workflow]));

        $this->assertTrue($this->workflowManager->hasApplicableWorkflows($entity));
    }

    public function testHasApplicableWorkflowsFalse(): void
    {
        $entity = new \DateTime('now');
        $entityClass = get_class($entity);

        $this->entityConnector->expects($this->once())
            ->method('isApplicableEntity')
            ->with($entity)
            ->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with($entityClass)
            ->willReturn(new ArrayCollection([]));

        $this->assertFalse($this->workflowManager->hasApplicableWorkflows($entity));
    }

    public function testStartWorkflowEntityWithoutId(): void
    {
        $entity = new \DateTime();
        $transitionName = 'test_transition';
        $workflowData = ['key' => 'value'];
        $workflowItem = new WorkflowItem();
        $workflowItem->getData()->add($workflowData);

        $transition = $this->createStartTransition($transitionName);

        $errors = new ArrayCollection();

        $workflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME, [$transition]);
        $workflow->expects($this->once())
            ->method('start')
            ->with($entity, $workflowData, $transition, $errors)
            ->willReturn($workflowItem);

        $workflow->expects($this->never())
            ->method('isStartTransitionAvailable');

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test_workflow')
            ->willReturn($workflow);

        $em = $this->getTransactionScopedEntityManager(WorkflowItem::class);

        $em->expects($this->once())
            ->method('persist')
            ->with($workflowItem);
        $em->expects($this->once())
            ->method('flush');

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturn(null);

        $actualWorkflowItem = $this->workflowManager->startWorkflow(
            'test_workflow',
            $entity,
            $transition,
            $workflowData,
            true,
            $errors
        );

        $this->assertEquals($workflowItem, $actualWorkflowItem);
        $this->assertEquals($workflowData, $actualWorkflowItem->getData()->getValues());
    }

    public function testStartWorkflowEntityWithId(): void
    {
        $entity = new \stdClass();
        $entity->id = 42;
        $transitionName = 'test_transition';
        $workflowData = ['key' => 'value'];
        $workflowItem = new WorkflowItem();
        $workflowItem->getData()->add($workflowData);

        $transition = $this->createStartTransition($transitionName);

        $errors = new ArrayCollection();

        $workflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME, [$transition]);
        $workflow->expects($this->once())
            ->method('start')
            ->with($entity, $workflowData, $transition, $errors)
            ->willReturn($workflowItem);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test_workflow')
            ->willReturn($workflow);

        $em = $this->getTransactionScopedEntityManager(WorkflowItem::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($workflowItem);
        $em->expects($this->once())
            ->method('flush');

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entity->id);

        $actualWorkflowItem = $this->workflowManager->startWorkflow(
            'test_workflow',
            $entity,
            $transition,
            $workflowData,
            true,
            $errors
        );

        $this->assertEquals($workflowItem, $actualWorkflowItem);
        $this->assertEquals($workflowData, $actualWorkflowItem->getData()->getValues());
    }

    public function testStartWorkflowWithInitOptions(): void
    {
        $entity = new \stdClass();
        $entity->id = 42;
        $transitionName = 'test_transition';
        $workflowData = ['key' => 'value'];
        $workflowItem = new WorkflowItem();
        $workflowItem->getData()->add($workflowData);

        $transition = $this->createTransition($transitionName)->setInitEntities([EntityStub::class])->setStart(true);

        $errors = new ArrayCollection();

        $workflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME, [$transition]);
        $workflow->expects($this->once())
            ->method('start')
            ->with($entity, $workflowData, $transition, $errors)
            ->willReturn($workflowItem);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test_workflow')
            ->willReturn($workflow);

        $em = $this->getTransactionScopedEntityManager(WorkflowItem::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($workflowItem);
        $em->expects($this->once())
            ->method('flush');

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entity->id);

        $actualWorkflowItem = $this->workflowManager->startWorkflow(
            'test_workflow',
            $entity,
            $transitionName,
            $workflowData,
            true,
            $errors
        );

        $this->assertEquals($workflowItem, $actualWorkflowItem);
        $this->assertEquals($workflowData, $actualWorkflowItem->getData()->getValues());
    }

    public function testStartWorkflowRecordGroupException(): void
    {
        $this->expectException(WorkflowRecordGroupException::class);
        $this->expectExceptionMessage('Workflow "test_workflow" can not be started because it belongs to');

        $entity = new EntityStub(1);
        $transition = $this->createStartTransition('test_transition');
        $workflowItem = new WorkflowItem();

        $this->prepareGetWorkflowItemsByEntity($entity, [$workflowItem]);

        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setExclusiveRecordGroups(['group1']);

        $workflowItem->setDefinition($workflowDefinition);

        $workflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME);
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
     * @dataProvider massStartDataProvider
     */
    public function testMassStartWorkflow(array $source, array $expected): void
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
        $entityManager->expects($this->exactly($expectedCalls))
            ->method('beginTransaction');
        $entityManager->expects($this->exactly($expectedCalls))
            ->method('commit');
        $entityManager->expects($this->exactly($expectedCallsCount))
            ->method('persist');
        $entityManager->expects($this->exactly($expectedCalls))
            ->method('flush');

        if ($expectedCallsCount) {
            $this->doctrineHelper->expects($this->any())
                ->method('getSingleEntityIdentifier')
                ->willReturn(1);
        }

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(WorkflowItem::class)
            ->willReturn($entityManager);

        if ($expected) {
            $getWorkflowExpectations = [];
            $getWorkflowExpectationResults = [];
            foreach ($expected as $row) {
                $workflowName = $row['workflow'];
                $workflow = $this->createWorkflow($workflowName, [$this->createTransition('start')]);
                $workflow->expects($this->any())
                    ->method('isStartTransitionAvailable')
                    ->willReturn($row['startTransitionAllowed']);
                $workflow->expects($this->any())
                    ->method('getDefinition')
                    ->willReturn(new WorkflowDefinition());
                $workflow->expects($this->exactly((int)$row['startTransitionAllowed']))
                    ->method('start')
                    ->with($row['entity'], $row['data'], $row['transition'])
                    ->willReturn($this->createWorkflowItem($workflowName));

                $getWorkflowExpectations[] = [$workflowName];
                $getWorkflowExpectationResults[] = $workflow;
            }
            $this->workflowRegistry->expects($this->exactly(count($getWorkflowExpectations)))
                ->method('getWorkflow')
                ->withConsecutive(...$getWorkflowExpectations)
                ->willReturnOnConsecutiveCalls(...$getWorkflowExpectationResults);
        } else {
            $this->workflowRegistry->expects($this->never())
                ->method('getWorkflow');
        }

        $this->workflowManager->massStartWorkflow($source);
    }

    public function massStartDataProvider(): array
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

    public function testTransit(): void
    {
        $transition = 'test_transition';
        $workflowName = 'test_workflow';

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName);

        $errors = new ArrayCollection();

        $workflow = $this->createWorkflow($workflowName);
        $workflow->expects($this->once())
            ->method('transit')
            ->with($workflowItem, $transition, $errors);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);

        $this->logger->expects($this->once())
            ->method('info')
            ->willReturnCallback(function ($message, array $context) use ($workflow, $workflowItem, $transition) {
                $this->assertEquals('Workflow transition is complete', $message);
                $this->assertArrayHasKey('workflow', $context);
                $this->assertEquals($workflow, $context['workflow']);
                $this->assertArrayHasKey('workflowItem', $context);
                $this->assertEquals($workflowItem, $context['workflowItem']);
                $this->assertArrayHasKey('transition', $context);
                $this->assertEquals($transition, $context['transition']);
            });

        $entityManager = $this->getTransactionScopedEntityManager(WorkflowItem::class);

        $entityManager->expects($this->once())
            ->method('flush');

        $this->assertEmpty($workflowItem->getUpdated());
        $this->workflowManager->transit($workflowItem, $transition, $errors);
        $this->assertNotEmpty($workflowItem->getUpdated());
    }

    public function testTransitUnconditionally(): void
    {
        $transition = 'test_transition';
        $workflowName = 'test_workflow';

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName);

        $workflow = $this->createWorkflow($workflowName);
        $workflow->expects($this->once())
            ->method('transitUnconditionally')
            ->with($workflowItem, $transition);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);

        $this->logger->expects($this->once())
            ->method('info')
            ->willReturnCallback(function ($message, array $context) use ($workflow, $workflowItem, $transition) {
                $this->assertEquals('Workflow transition is complete', $message);
                $this->assertArrayHasKey('workflow', $context);
                $this->assertEquals($workflow, $context['workflow']);
                $this->assertArrayHasKey('workflowItem', $context);
                $this->assertEquals($workflowItem, $context['workflowItem']);
                $this->assertArrayHasKey('transition', $context);
                $this->assertEquals($transition, $context['transition']);
            });

        $entityManager = $this->getTransactionScopedEntityManager(WorkflowItem::class);

        $entityManager->expects($this->once())
            ->method('flush');

        $this->assertEmpty($workflowItem->getUpdated());
        $this->workflowManager->transitUnconditionally($workflowItem, $transition);
        $this->assertNotEmpty($workflowItem->getUpdated());
    }

    public function testTransitIfAllowed(): void
    {
        $transition = 'test_transition';
        $workflowName = 'test_workflow';

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName);

        $workflow = $this->createWorkflow($workflowName);
        $workflow->expects($this->once())
            ->method('transitUnconditionally')
            ->with($workflowItem, $transition);
        $workflow->expects($this->once())
            ->method('isTransitionAllowed')
            ->with($workflowItem, $transition)
            ->willReturn(true);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);

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

    public function testTransitIfAllowedFalse(): void
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
            ->willReturn($workflow);

        $this->assertEmpty($workflowItem->getUpdated());
        $this->assertFalse(
            $this->workflowManager->transitIfAllowed($workflowItem, $transition),
            'If transit is NOT allowed for current WorkflowItem and transition then FALSE expected.'
        );
        $this->assertEmpty($workflowItem->getUpdated());
    }

    /**
     * @dataProvider massTransitDataProvider
     */
    public function testMassTransit(array $source, array $expected): void
    {
        $entityManager = $this->getTransactionScopedEntityManager(WorkflowItem::class);

        /** @var WorkflowItem[] $workflowItems */
        $workflowItems = [];

        if ($expected) {
            $getWorkflowExpectations = [];
            $getWorkflowExpectationResults = [];
            foreach ($expected as $iteration => $row) {
                $workflowName = $row['workflow'];
                $workflowItem = $source[$iteration]['workflowItem'];
                $workflowItems[] = $workflowItem;
                $transition = $row['transition'];

                $workflow = $this->createWorkflow($workflowName);
                $workflow->expects($this->once())
                    ->method('transit')
                    ->with($workflowItem, $transition)
                    ->willReturn($workflowItem);

                $getWorkflowExpectations[] = [$workflowName];
                $getWorkflowExpectationResults[] = $workflow;
            }
            $this->workflowRegistry->expects($this->exactly(count($getWorkflowExpectations)))
                ->method('getWorkflow')
                ->withConsecutive(...$getWorkflowExpectations)
                ->willReturnOnConsecutiveCalls(...$getWorkflowExpectationResults);
        } else {
            $this->workflowRegistry->expects($this->never())
                ->method('getWorkflow');
        }

        $entityManager->expects($this->once())
            ->method('flush');

        $this->workflowManager->massTransit($source);

        foreach ($workflowItems as $workflowItem) {
            $this->assertNotEmpty($workflowItem->getUpdated());
        }
    }

    public function massTransitDataProvider(): array
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
     */
    public function testGetWorkflowItem(int|string $id): void
    {
        $entity = new EntityStub($id);
        $workflowName = 'test_workflow';

        $this->entityConnector->expects($this->once())
            ->method('isApplicableEntity')
            ->with($entity)
            ->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn(EntityStub::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($id);

        $repository = $this->createMock(WorkflowItemRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(WorkflowItem::class)
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findOneByEntityMetadata')
            ->with(EntityStub::class, $id, $workflowName)
            ->willReturn('result');
        $result = $this->workflowManager->getWorkflowItem($entity, $workflowName);

        $this->assertEquals('result', $result);
    }

    public function testGetWorkflowItemFromNotApplicableEntity(): void
    {
        $entity = new EntityStub(42);
        $workflowName = 'test_workflow';

        $this->entityConnector->expects($this->once())
            ->method('isApplicableEntity')
            ->with($entity)
            ->willReturn(false);

        $this->assertNull($this->workflowManager->getWorkflowItem($entity, $workflowName));
    }

    public function getWorkflowItemDataProvider(): array
    {
        return [
            [42],
            ['string']
        ];
    }

    /**
     * @dataProvider entityDataProvider
     */
    public function testGetWorkflowItemsByEntity(mixed $entityId, array $workflowItems = []): void
    {
        $entity = new EntityStub($entityId);
        $this->prepareGetWorkflowItemsByEntity($entity, $workflowItems);

        $this->assertEquals(
            $workflowItems,
            $this->workflowManager->getWorkflowItemsByEntity($entity)
        );
    }

    public function entityDataProvider(): array
    {
        return [
            'integer' => [1, [$this->createWorkflowItem()]],
            'integer_as_string' => ['123', [$this->createWorkflowItem()]],
            'string' => ['identifier', []],
            'null' => [null, []],
            'object' => [new \stdClass(), []],
        ];
    }

    public function testGetFirstWorkflowItemByEntity(): void
    {
        $entity = new EntityStub(123);
        $workflowItem = $this->createWorkflowItem();
        $this->prepareGetWorkflowItemsByEntity($entity, [$workflowItem]);

        $this->assertEquals(
            $workflowItem,
            $this->workflowManager->getFirstWorkflowItemByEntity($entity)
        );
    }

    public function testGetFirstWorkflowItemByEntityNoItem(): void
    {
        $entity = new EntityStub(123);

        $this->prepareGetWorkflowItemsByEntity($entity, []);

        $this->assertFalse($this->workflowManager->getFirstWorkflowItemByEntity($entity));
    }

    public function testActivateWorkflow(): void
    {
        $workflowName = 'test_workflow';

        $workflowMock = $this->createMock(Workflow::class);
        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $entityManager = $this->createMock(EntityManager::class);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflowMock);
        $workflowMock->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);

        $workflowDefinition->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        $workflowDefinition->expects($this->once())
            ->method('setActive')
            ->with(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(WorkflowDefinition::class)
            ->willReturn($entityManager);
        $entityManager->expects($this->once())
            ->method('flush')
            ->with($workflowDefinition);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [new WorkflowChangesEvent($workflowDefinition), WorkflowEvents::WORKFLOW_BEFORE_ACTIVATION],
                [new WorkflowChangesEvent($workflowDefinition), WorkflowEvents::WORKFLOW_ACTIVATED]
            );

        $this->assertTrue(
            $this->workflowManager->activateWorkflow($workflowName),
            'Returns true if workflow has changed its state.'
        );
    }

    public function testActivateWorkflowSkipIfAlreadyActive(): void
    {
        $workflowName = 'test_workflow';

        $workflowMock = $this->createMock(Workflow::class);
        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $entityManager = $this->createMock(EntityManager::class);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflowMock);
        $workflowMock->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);

        $workflowDefinition->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $workflowDefinition->expects($this->never())
            ->method('setActive');
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');
        $entityManager->expects($this->never())
            ->method('flush');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->assertFalse(
            $this->workflowManager->activateWorkflow($workflowName),
            'Returns false if workflow has not change its state.'
        );
    }

    public function testDeactivateWorkflow(): void
    {
        $workflowName = 'test_workflow';

        $workflowMock = $this->createMock(Workflow::class);
        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $entityManager = $this->createMock(EntityManager::class);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflowMock);
        $workflowMock->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);

        $workflowDefinition->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $workflowDefinition->expects($this->once())
            ->method('setActive')
            ->with(false);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(WorkflowDefinition::class)
            ->willReturn($entityManager);
        $entityManager->expects($this->once())
            ->method('flush')
            ->with($workflowDefinition);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [new WorkflowChangesEvent($workflowDefinition), WorkflowEvents::WORKFLOW_BEFORE_DEACTIVATION],
                [new WorkflowChangesEvent($workflowDefinition), WorkflowEvents::WORKFLOW_DEACTIVATED]
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

    public function testDeactivateWorkflowSkipIfNotActive(): void
    {
        $workflowName = 'test_workflow';

        $workflowMock = $this->createMock(Workflow::class);
        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $entityManager = $this->createMock(EntityManager::class);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflowMock);
        $workflowMock->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);

        $workflowDefinition->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        $workflowDefinition->expects($this->never())
            ->method('setActive');
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');
        $entityManager->expects($this->never())
            ->method('flush');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->assertFalse(
            $this->workflowManager->deactivateWorkflow($workflowName),
            'Returns false if workflow has not change its state.'
        );
    }

    /**
     * @dataProvider isActiveDataProvider
     */
    public function testIsActiveWorkflow(bool $isActive): void
    {
        $workflowName = 'test_workflow';

        $workflowMock = $this->createMock(Workflow::class);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflowMock);
        $workflowMock->expects($this->once())
            ->method('isActive')
            ->willReturn($isActive);

        $this->assertEquals($isActive, $this->workflowManager->isActiveWorkflow($workflowName));
    }

    public function isActiveDataProvider(): array
    {
        return [[true], [false]];
    }

    private function createWorkflowItem(string $workflowName = self::TEST_WORKFLOW_NAME): WorkflowItem
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName);

        return $workflowItem;
    }

    /**
     * @return Workflow|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createWorkflow(string $name, array $startTransitions = [])
    {
        $attributeManager = $this->createMock(AttributeManager::class);

        $transitionManager = $this->getMockBuilder(TransitionManager::class)
            ->onlyMethods(['getStartTransitions', 'getDefaultStartTransition'])
            ->getMock();
        $transitionManager->expects($this->any())
            ->method('getStartTransitions')
            ->willReturn(new ArrayCollection($startTransitions));
        $transitionManager->expects($this->any())
            ->method('getDefaultStartTransition')
            ->willReturn($this->getStartTransition());
        $transitionManager->setTransitions($startTransitions);

        $workflow = $this->getMockBuilder(Workflow::class)
            ->setConstructorArgs([
                $this->createMock(DoctrineHelper::class),
                $this->createMock(AclManager::class),
                $this->createMock(RestrictionManager::class),
                null,
                $attributeManager,
                $transitionManager,
                $this->createMock(VariableManager::class)
            ])
            ->onlyMethods([
                'isTransitionAllowed',
                'isTransitionAvailable',
                'isStartTransitionAvailable',
                'getTransitionsByWorkflowItem',
                'start',
                'isActive',
                'getDefinition',
                'getName',
                'getStepManager',
                'transit',
                'transitUnconditionally',
            ])
            ->getMock();

        $workflow->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $workflow;
    }

    public function trueFalseDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    public function testResetWorkflowData(): void
    {
        $name = 'testWorkflow';
        $entityClass = 'Test:Entity';

        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName($name)->setRelatedEntity($entityClass);

        $workflowItemsRepository = $this->createMock(WorkflowItemRepository::class);
        $workflowItemsRepository->expects($this->once())
            ->method('resetWorkflowData')
            ->with($name);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(WorkflowItem::class)
            ->willReturn($workflowItemsRepository);

        $this->startedWorkflowsBag->addWorkflowEntity($name, new \stdClass());
        $this->startedWorkflowsBag->addWorkflowEntity($name, new \stdClass());

        $this->assertCount(2, $this->startedWorkflowsBag->getWorkflowEntities($name));

        $this->workflowManager->resetWorkflowData($name);

        $this->assertCount(0, $this->startedWorkflowsBag->getWorkflowEntities($name));
    }

    private function getStartTransition(): Transition
    {
        return $this->createTransition('__start__')->setStart(true);
    }

    private function prepareGetWorkflowItemsByEntity(object $entity, array $workflowItems): void
    {
        $this->entityConnector->expects($this->once())
            ->method('isApplicableEntity')
            ->willReturn(true);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn(EntityStub::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(function ($entityParam) use ($entity) {
                return $entityParam === $entity ? $entity->getId() : null;
            });

        $workflowItemsRepository = $this->createMock(WorkflowItemRepository::class);
        $workflowItemsRepository->expects($this->any())
            ->method('findAllByEntityMetadata')
            ->with(EntityStub::class, $entity->getId())
            ->willReturn($workflowItems);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(WorkflowItem::class)
            ->willReturn($workflowItemsRepository);
    }

    private function createTransition(string $name): Transition
    {
        $transition = new Transition($this->createMock(TransitionOptionsResolver::class));
        $transition->setName($name);

        return $transition;
    }

    private function createStartTransition(string $name): Transition
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $transition->expects($this->any())
            ->method('isStart')
            ->willReturn(true);

        return $transition;
    }
}
