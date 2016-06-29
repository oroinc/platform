<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Model\WorkflowSystemConfigManager;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
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

    /** @var WorkflowSystemConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowSystemConfig;

    protected function setUp()
    {
        $this->workflowRegistry = $this->getMockBuilder(WorkflowRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowSystemConfig = $this->getMockBuilder(WorkflowSystemConfigManager::class)
            ->disableOriginalConstructor()->getMock();

        $this->workflowManager = new WorkflowManager(
            $this->workflowRegistry,
            $this->doctrineHelper,
            $this->workflowSystemConfig
        );
    }

    protected function tearDown()
    {
        unset(
            $this->workflowRegistry,
            $this->doctrineHelper,
            $this->workflowManager
        );
    }

    public function testGetStartTransitions()
    {
        $startTransition = new Transition();
        $startTransition->setName('start_transition');
        $startTransition->setStart(true);

        $startTransitions = new ArrayCollection([$startTransition]);
        $workflow = $this->createWorkflow('test_workflow', [], $startTransitions->toArray());
        $this->assertEquals($startTransitions, $this->workflowManager->getStartTransitions($workflow));
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
        $workflowDefinition = (new WorkflowDefinition())->setName($workflowName);
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
        $stepManager->expects($this->once())->method('hasStartStep')->willReturn(false);
        $workflow->expects($this->once())->method('getStepManager')->willReturn($stepManager);
        $workflow->expects($this->once())->method('getDefinition')->willReturn($workflowDefinition);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test_workflow')
            ->willReturn($workflow);

        $this->workflowSystemConfig->expects($this->once())
            ->method('isActiveWorkflow')
            ->with($workflowDefinition)
            ->willReturn(true);

        $this->workflowManager->resetWorkflowItem($workflowItem);
    }

    public function testResetWorkflowItemWithStartStep()
    {
        $workflowItem = new WorkflowItem();
        $newItem = new WorkflowItem();
        $workflowName = 'test_workflow';
        $workflowDefinition = (new WorkflowDefinition())->setName($workflowName);
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
        $workflow->expects($this->once())->method('getDefinition')->willReturn($workflowDefinition);
        $workflow->expects($this->once())->method('start')->with($entity, [], null)->willReturn($newItem);

        $this->workflowRegistry->expects($this->exactly(2))
            ->method('getWorkflow')
            ->with('test_workflow')
            ->willReturn($workflow);

        $this->workflowSystemConfig->expects($this->once())
            ->method('isActiveWorkflow')
            ->with($workflowDefinition)
            ->willReturn(true);

        $item = $this->workflowManager->resetWorkflowItem($workflowItem);
        $this->assertSame($newItem, $item, 'should return item created while workflow->start invoked');
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

    public function testGetApplicableWorkflows()
    {
        $entity = new \DateTime('now');
        $entityClass = get_class($entity);
        $workflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->will($this->returnValue($entityClass));

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with($entityClass)
            ->will($this->returnValue([$workflow]));

        $this->assertEquals([$workflow], $this->workflowManager->getApplicableWorkflows($entity));
    }

    public function testHasApplicableWorkflowsTrue()
    {
        $entity = new \DateTime('now');
        $entityClass = get_class($entity);
        $workflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->will($this->returnValue($entityClass));

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with($entityClass)
            ->will($this->returnValue([$workflow]));

        $this->assertTrue($this->workflowManager->hasApplicableWorkflows($entity));
    }

    public function testHasApplicableWorkflowsFalse()
    {
        $entity = new \DateTime('now');
        $entityClass = get_class($entity);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->will($this->returnValue($entityClass));

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with($entityClass)
            ->will($this->returnValue([]));

        $this->assertFalse($this->workflowManager->hasApplicableWorkflows($entity));
    }

    public function testStartWorkflow()
    {
        $entity = new \DateTime();
        $transition = 'test_transition';
        $workflowData = ['key' => 'value'];
        $workflowItem = new WorkflowItem();
        $workflowItem->getData()->add($workflowData);

        $workflow = $this->createWorkflow();
        $workflow->expects($this->once())
            ->method('start')
            ->with($entity, $workflowData, $transition)
            ->will($this->returnValue($workflowItem));

        $em = $this->getTransactionScopedEntityManager(WorkflowItem::class);

        $em->expects($this->once())
            ->method('persist')
            ->with($workflowItem);
        $em->expects($this->once())
            ->method('flush');

        $actualWorkflowItem = $this->workflowManager->startWorkflow($workflow, $entity, $transition, $workflowData);

        $this->assertEquals($workflowItem, $actualWorkflowItem);
        $this->assertEquals($workflowData, $actualWorkflowItem->getData()->getValues());
    }

    /**
     * @param array $source
     * @param array $expected
     * @dataProvider massStartDataProvider
     */
    public function testMassStartWorkflow(array $source, array $expected)
    {
        $entityManager = $this->getTransactionScopedEntityManager(WorkflowItem::class);

        if ($expected) {
            foreach ($expected as $iteration => $row) {
                $workflowName = $row['workflow'];
                $workflow = $this->createWorkflow($workflowName);
                $workflowItem = $this->createWorkflowItem($workflowName);

                $workflow->expects($this->once())->method('start')
                    ->with($row['entity'], $row['data'], $row['transition'])
                    ->will($this->returnValue($workflowItem));

                $this->workflowRegistry->expects($this->at($iteration))->method('getWorkflow')->with($workflowName)
                    ->will($this->returnValue($workflow));

                $entityManager->expects($this->at($iteration + 1))->method('persist')->with($workflowItem);
            }
        } else {
            $this->workflowRegistry->expects($this->never())->method('getWorkflow');
            $entityManager->expects($this->never())->method('persist');
        }

        $entityManager->expects($this->once())->method('flush');

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
                    ['workflow' => 'first', 'entity' => $firstEntity],
                    ['workflow' => 'second', 'entity' => $secondEntity],
                ],
                'expected' => [
                    ['workflow' => 'first', 'entity' => $firstEntity, 'transition' => null, 'data' => []],
                    ['workflow' => 'second', 'entity' => $secondEntity, 'transition' => null, 'data' => []],
                ],
            ],
            'extra cases' => [
                'source' => [
                    ['workflow' => 'first', 'entity' => $firstEntity, 'transition' => 'start'],
                    [
                        'workflow' => 'second',
                        'entity' => $secondEntity,
                        'transition' => 'start',
                        'data' => ['field' => 'value']
                    ],
                    ['some', 'strange', 'data'],
                ],
                'expected' => [
                    ['workflow' => 'first', 'entity' => $firstEntity, 'transition' => 'start', 'data' => []],
                    [
                        'workflow' => 'second',
                        'entity' => $secondEntity,
                        'transition' => 'start',
                        'data' => ['field' => 'value'],
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

        $entityManager = $this->getTransactionScopedEntityManager(WorkflowItem::class);

        $entityManager->expects($this->once())
            ->method('flush');

        $this->assertEmpty($workflowItem->getUpdated());
        $this->workflowManager->transit($workflowItem, $transition);
        $this->assertNotEmpty($workflowItem->getUpdated());
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

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')->with($entity)->willReturn($id);

        $repository = $this->getMockBuilder(WorkflowItemRepository::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->with(WorkflowItem::class)
            ->willReturn($repository);
        $this->doctrineHelper->expects($this->once())->method('getEntityClass')->with($entity)
            ->willReturn(EntityStub::class);
        $repository->expects($this->once())->method('findOneByEntityMetadata')
            ->with(EntityStub::class, $id, $workflowName)
            ->willReturn(['result']);

        $result = $this->workflowManager->getWorkflowItem($entity, $workflowName);

        $this->assertEquals(['result'], $result);
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
     * @dataProvider unsupportedIdentifiersDataProvider
     * @param mixed $id
     */
    public function testGetWorkflowItemUnsupportedIdentifier($id)
    {
        $entity = new EntityStub($id);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')->with($entity)->willReturn($id);

        $this->doctrineHelper->expects($this->never())->method('getEntityRepository');

        $result = $this->workflowManager->getWorkflowItem($entity, 'workflow_name');

        $this->assertNull($result, 'If not an integer identifier got - return null');
    }

    /**
     * @return array
     */
    public function unsupportedIdentifiersDataProvider()
    {
        return [
            [['array']],
            [1.123123],
            [(object)[]]
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
        $entity = new \DateTime('now');
        $entityClass = get_class($entity);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->with($entity)
            ->will($this->returnValue($entityClass));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue($entityId));

        $workflowItemsRepository =
            $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository')
                ->disableOriginalConstructor()
                ->setMethods(['findAllByEntityMetadata'])
                ->getMock();
        $workflowItemsRepository->expects($this->any())
            ->method('findAllByEntityMetadata')
            ->with($entityClass, $entityId)
            ->will($this->returnValue($workflowItems));
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(WorkflowItem::class)
            ->will($this->returnValue($workflowItemsRepository));

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
        $workflowDefinition = new WorkflowDefinition();

        $this->workflowSystemConfig->expects($this->once())
            ->method('isActiveWorkflow')
            ->with($workflowDefinition)
            ->willReturn(false);
        $this->workflowSystemConfig->expects($this->once())->method('setWorkflowActive')->with($workflowDefinition);

        $this->workflowManager->activateWorkflow($workflowDefinition);
    }

    public function testActivateWorkflowSkipIfAlreadyActive()
    {
        $workflowDefinition = new WorkflowDefinition();

        $this->workflowSystemConfig->expects($this->once())
            ->method('isActiveWorkflow')
            ->with($workflowDefinition)
            ->willReturn(true);
        $this->workflowSystemConfig->expects($this->never())->method('setWorkflowActive');

        $this->workflowManager->activateWorkflow($workflowDefinition);
    }

    public function testDeactivateWorkflow()
    {
        $workflowDefinition = new WorkflowDefinition();

        $this->workflowSystemConfig->expects($this->once())
            ->method('isActiveWorkflow')
            ->with($workflowDefinition)
            ->willReturn(true);
        $this->workflowSystemConfig->expects($this->once())->method('setWorkflowInactive')->with($workflowDefinition);

        $this->workflowManager->deactivateWorkflow($workflowDefinition);
    }

    public function testDeactivateWorkflowSkipIfNotActive()
    {
        $workflowDefinition = new WorkflowDefinition();

        $this->workflowSystemConfig->expects($this->once())
            ->method('isActiveWorkflow')
            ->with($workflowDefinition)
            ->willReturn(false);
        $this->workflowSystemConfig->expects($this->never())->method('setWorkflowInactive');

        $this->workflowManager->deactivateWorkflow($workflowDefinition);
    }

    public function testIsActiveWorkflow()
    {
        $workflowDefinition = new WorkflowDefinition();

        $this->workflowSystemConfig->expects($this->once())->method('isActiveWorkflow')->with($workflowDefinition);

        $this->workflowManager->isActiveWorkflow($workflowDefinition);
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
            ->setMethods(['getStartTransitions'])
            ->getMock();
        $transitionManager->expects($this->any())
            ->method('getStartTransitions')
            ->will($this->returnValue(new ArrayCollection($startTransitions)));

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
                    'isTransitionAvailable',
                    'isStartTransitionAvailable',
                    'getTransitionsByWorkflowItem',
                    'start',
                    'getDefinition',
                    'getStepManager',
                    'transit'
                ]
            )
            ->getMock();

        /** @var Workflow $workflow */
        $workflow->setName($name);

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
            ->with($workflowDefinition);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(WorkflowItem::class)
            ->will($this->returnValue($workflowItemsRepository));

        $this->workflowManager->resetWorkflowData($workflowDefinition);
    }
}
