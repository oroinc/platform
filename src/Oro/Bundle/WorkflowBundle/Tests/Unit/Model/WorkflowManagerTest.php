<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Model\EntityConnector;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class WorkflowManagerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_WORKFLOW_NAME = 'test_workflow';

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->setMethods(array('getManager'))
            ->getMockForAbstractClass();

        $this->workflowRegistry = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->workflowManager = new WorkflowManager(
            $this->registry,
            $this->workflowRegistry,
            $this->doctrineHelper,
            $this->configManager,
            $this->eventDispatcher
        );
    }

    protected function tearDown()
    {
        unset($this->registry);
        unset($this->workflowRegistry);
        unset($this->doctrineHelper);
        unset($this->workflowManager);
        unset($this->eventDispatcher);
    }

    public function testGetStartTransitions()
    {
        $startTransition = new Transition();
        $startTransition->setName('start_transition');
        $startTransition->setStart(true);

        $startTransitions = new ArrayCollection(array($startTransition));
        $workflow = $this->createWorkflow('test_workflow', array(), $startTransitions->toArray());
        $this->assertEquals($startTransitions, $this->workflowManager->getStartTransitions($workflow));
    }

    /**
     * @param string $workflowItemDefinition
     * @param string $activeDefinition
     * @param bool $result
     * @dataProvider getActiveWorkflowDataProvider
     */
    public function testIsResetAllowed($workflowItemDefinition, $activeDefinition, $result)
    {
        $entity       = new \DateTime('now');
        $entityId     = 1;
        $entityClass  = get_class($entity);
        $workflowItem = null === $workflowItemDefinition ? null : $this->createWorkflowItem($workflowItemDefinition);

        if (null === $activeDefinition) {
            $workflow = null;
        } else {
            $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
                ->disableOriginalConstructor()
                ->setMethods(null)
                ->getMock();
            $workflow->setName($activeDefinition);
        }

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
                ->setMethods(array('findByEntityMetadata'))
                ->getMock();
        $workflowItemsRepository->expects($this->once())
            ->method('findByEntityMetadata')
            ->with($entityClass, $entityId)
            ->will($this->returnValue($workflowItem));
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with('OroWorkflowBundle:WorkflowItem')
            ->will($this->returnValue($workflowItemsRepository));

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowByEntityClass')
            ->with($entityClass)
            ->will($this->returnValue($workflow));

        $this->assertEquals($result, $this->workflowManager->isResetAllowed($entity));
    }

    /**
     * @return array
     */
    public function getActiveWorkflowDataProvider()
    {
        return array(
            array('active-workflow', 'active-workflow', false),
            array('active-workflow', 'current-workflow', true),
            array(null, 'current-workflow', false),
            array(null, 'active-workflow', false),
            array('current-workflow', null, false),
            array('active-workflow', null, false),
            array(null, null, false),
        );
    }

    public function testGetTransitionsByWorkflowItem()
    {
        $workflowName = 'test_workflow';

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName);

        $transition = new Transition();
        $transition->setName('test_transition');

        $transitions = new ArrayCollection(array($transition));

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
        $data = array();

        $entityAttribute = new Attribute();
        $entityAttribute->setName('entity_attribute');
        $entityAttribute->setType('entity');
        $entityAttribute->setOptions(array('class' => 'DateTime'));

        $stringAttribute = new Attribute();
        $stringAttribute->setName('other_attribute');
        $stringAttribute->setType('string');

        $transition = 'test_transition';

        $workflow = $this->createWorkflow($workflowName, array($entityAttribute, $stringAttribute));
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

    /**
     * @param boolean $withStartStep
     * @dataProvider resetWorkflowItemProvider
     */
    public function testResetWorkflowItem($withStartStep)
    {
        $workflowName       = self::TEST_WORKFLOW_NAME;
        $activeWorkflowName = self::TEST_WORKFLOW_NAME . '_active';
        $workflowItem       = $this->createWorkflowItem();
        $entity             = new WorkflowAwareEntity();
        $workflowStep       = new WorkflowStep();

        $entity->setWorkflowItem($workflowItem);
        $entity->setWorkflowStep($workflowStep);

        $workflowItem->setEntity($entity);

        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setRelatedEntity(get_class($entity));

        $workflowItem->setDefinition($workflowDefinition);

        $aclManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Acl\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->setConstructorArgs(array(new EntityConnector(), $aclManager, null, null, null))
            ->setMethods(null)
            ->getMock();
        $workflow->setName($workflowName);

        $stepManager = $this->getMock('Oro\Bundle\WorkflowBundle\Model\StepManager');
        $stepManager->expects($this->any())->method('hasStartStep')
            ->will($this->returnValue($withStartStep));

        $activeWorkflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->setConstructorArgs(array(new EntityConnector(), $aclManager, $stepManager, null, null))
            ->setMethods(array('start'))
            ->getMock();
        $activeWorkflow->setName($activeWorkflowName);
        $activeWorkflow->setDefinition($workflowDefinition);
        if ($withStartStep) {
            $workflowDefinition->setName($activeWorkflowName);
            $workflowItemActive = $this->createWorkflowItem($activeWorkflowName);
            $workflowItemActive->setEntity($entity);
            $workflowItemActive->setDefinition($workflowDefinition);

            $activeWorkflow->expects($this->once())
                ->method('start')
                ->with($entity, array(), null)
                ->will($this->returnValue($workflowItemActive));
        } else {
            $activeWorkflow->expects($this->never())
                ->method('start');
        }

        $this->workflowRegistry->expects($this->any())
            ->method('getWorkflow')
            ->will(
                $this->returnCallback(
                    function ($workflowIdentifier) use ($workflow, $activeWorkflow) {
                        return is_string($workflowIdentifier) ? $activeWorkflow : $workflow;
                    }
                )
            );

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowByEntityClass')
            ->will($this->returnValue($activeWorkflow));

        $entityManager = $this->createEntityManager();
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->will($this->returnValue($entityManager));

        if ($withStartStep) {
            $this->registry->expects($this->once())
                ->method('getManager')
                ->will($this->returnValue($entityManager));
        } else {
            $this->registry->expects($this->never())
                ->method('getManager');
        }

        $activeWorkflowItem = $this->workflowManager->resetWorkflowItem($workflowItem);
        if ($withStartStep) {
            $this->assertNotNull($activeWorkflowItem);
            $this->assertEquals($activeWorkflowName, $activeWorkflowItem->getDefinition()->getName());
        } else {
            $this->assertNull($activeWorkflowItem);
        }

        $this->assertNull($entity->getWorkflowStep());
        $this->assertNull($entity->getWorkflowItem());
    }

    /**
     * @return array
     */
    public function resetWorkflowItemProvider()
    {
        return array(
            array(true),
            array(false)
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Reset workflow exception message
     */
    public function testResetWorkflowItemException()
    {
        $workflowItem = $this->createWorkflowItem();
        $entity       = new WorkflowAwareEntity();
        $workflowItem->setEntity($entity);

        $aclManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Acl\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->setConstructorArgs(array(new EntityConnector(), $aclManager, null, null, null))
            ->setMethods(null)
            ->getMock();

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('beginTransaction');
        $entityManager->expects($this->once())
            ->method('remove')
            ->will($this->throwException(new \Exception('Reset workflow exception message')));
        $entityManager->expects($this->once())
            ->method('rollback');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->will($this->returnValue($entityManager));
        $this->workflowRegistry->expects($this->any())
            ->method('getWorkflow')
            ->with(self::TEST_WORKFLOW_NAME)
            ->will($this->returnValue($workflow));

        $this->workflowManager->resetWorkflowItem($workflowItem);
    }

    public function testStartWorkflow()
    {
        $entity = new \DateTime();
        $transition = 'test_transition';
        $workflowData = array('key' => 'value');
        $workflowItem = new WorkflowItem();
        $workflowItem->getData()->add($workflowData);

        $workflow = $this->createWorkflow();
        $workflow->expects($this->once())
            ->method('start')
            ->with($entity, $workflowData, $transition)
            ->will($this->returnValue($workflowItem));

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('beginTransaction');
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($workflowItem);
        $entityManager->expects($this->once())
            ->method('flush');
        $entityManager->expects($this->once())
            ->method('commit');

        $this->registry->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $actualWorkflowItem = $this->workflowManager->startWorkflow($workflow, $entity, $transition, $workflowData);

        $this->assertEquals($workflowItem, $actualWorkflowItem);
        $this->assertEquals($workflowData, $actualWorkflowItem->getData()->getValues());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Start workflow exception message
     */
    public function testStartWorkflowException()
    {
        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('beginTransaction');
        $entityManager->expects($this->once())
            ->method('persist')
            ->will($this->throwException(new \Exception('Start workflow exception message')));
        $entityManager->expects($this->once())
            ->method('rollback');

        $this->registry->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $this->workflowManager->startWorkflow($this->createWorkflow(), null, 'test_transition');
    }

    /**
     * @param array $source
     * @param array $expected
     * @dataProvider massStartDataProvider
     */
    public function testMassStartWorkflow(array $source, array $expected)
    {
        $entityManager = $this->createEntityManager();
        $this->registry->expects($this->once())->method('getManager')
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())->method('beginTransaction');

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
        $entityManager->expects($this->once())->method('commit');

        $this->workflowManager->massStartWorkflow($source);
    }

    public function massStartDataProvider()
    {
        $firstEntity = new \DateTime('2012-12-12');
        $secondEntity = new \DateTime('2012-12-13');

        return array(
            'no data' => array(
                'source' => array(),
                'expected' => array(),
            ),
            'regular data' => array(
                'source' => array(
                    array('workflow' => 'first', 'entity' => $firstEntity),
                    array('workflow' => 'second', 'entity' => $secondEntity),
                ),
                'expected' => array(
                    array('workflow' => 'first', 'entity' => $firstEntity, 'transition' => null, 'data' => array()),
                    array('workflow' => 'second', 'entity' => $secondEntity, 'transition' => null, 'data' => array()),
                ),
            ),
            'extra cases' => array(
                'source' => array(
                    array('workflow' => 'first', 'entity' => $firstEntity, 'transition' => 'start'),
                    array(
                        'workflow' => 'second',
                        'entity' => $secondEntity,
                        'transition' => 'start',
                        'data' => array('field' => 'value')
                    ),
                    array('some', 'strange', 'data'),
                ),
                'expected' => array(
                    array('workflow' => 'first', 'entity' => $firstEntity, 'transition' => 'start', 'data' => array()),
                    array(
                        'workflow' => 'second',
                        'entity' => $secondEntity,
                        'transition' => 'start',
                        'data' => array('field' => 'value'),
                    ),
                ),
            )
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Mass start workflow exception message
     */
    public function testMassStartWorkflowException()
    {
        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())->method('beginTransaction');
        $entityManager->expects($this->once())->method('rollback');
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('commit');

        $this->registry->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $workflowName = 'test_workflow';
        $entity = new \DateTime();
        $workflow = $this->createWorkflow($workflowName);

        $workflow->expects($this->once())->method('start')->with($entity, array(), null)
            ->will($this->throwException(new \Exception('Mass start workflow exception message')));

        $this->workflowRegistry->expects($this->once())->method('getWorkflow')->with($workflowName)
            ->will($this->returnValue($workflow));

        $this->workflowManager->massStartWorkflow(array(array('workflow' => $workflowName, 'entity' => $entity)));
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

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('beginTransaction');
        $entityManager->expects($this->once())
            ->method('flush');
        $entityManager->expects($this->once())
            ->method('commit');

        $this->registry->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $this->assertEmpty($workflowItem->getUpdated());
        $this->workflowManager->transit($workflowItem, $transition);
        $this->assertNotEmpty($workflowItem->getUpdated());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Transit exception message
     */
    public function testTransitException()
    {
        $workflowName = 'test_workflow';

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($workflowName);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->will($this->returnValue($this->createWorkflow($workflowName)));

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())
            ->method('beginTransaction');
        $entityManager->expects($this->once())
            ->method('flush')
            ->will($this->throwException(new \Exception('Transit exception message')));
        $entityManager->expects($this->once())
            ->method('rollback');

        $this->registry->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $this->workflowManager->transit($workflowItem, 'test_transition');
    }

    /**
     * @param array $source
     * @param array $expected
     * @dataProvider massTransitDataProvider
     */
    public function testMassTransit(array $source, array $expected)
    {
        $entityManager = $this->createEntityManager();
        $this->registry->expects($this->once())->method('getManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->once())->method('beginTransaction');

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
        $entityManager->expects($this->once())->method('commit');

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
     * @expectedException \Exception
     * @expectedExceptionMessage Mass transit exception message
     */
    public function testMassTransitException()
    {
        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->once())->method('beginTransaction');
        $entityManager->expects($this->once())->method('rollback');
        $entityManager->expects($this->never())->method('commit');

        $this->registry->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $workflow = $this->createWorkflow();
        $workflowItem = $this->createWorkflowItem();
        $transition = 'test_transition';

        $workflow->expects($this->once())->method('transit')->with($workflowItem, $transition)
            ->willThrowException(new \Exception('Mass transit exception message'));

        $this->workflowRegistry->expects($this->once())->method('getWorkflow')
            ->willReturn($workflow);

        $this->workflowManager->massTransit([['workflowItem' => $workflowItem, 'transition' => $transition]]);
    }

    public function testGetApplicableWorkflow()
    {
        $entity = new \DateTime('now');
        $entityClass = get_class($entity);
        $workflow = $this->createWorkflow(self::TEST_WORKFLOW_NAME);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->will($this->returnValue($entityClass));
        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowByEntityClass')
            ->with($entityClass)
            ->will($this->returnValue($workflow));

        $this->assertEquals($workflow, $this->workflowManager->getApplicableWorkflow($entity));
    }

    /**
     * @param mixed $entityId
     * @param WorkflowItem $workflowItem
     *
     * @dataProvider entityDataProvider
     */
    public function testGetWorkflowItemByEntity($entityId, WorkflowItem $workflowItem = null)
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
                ->setMethods(array('findByEntityMetadata'))
                ->getMock();
        $workflowItemsRepository->expects($this->any())
            ->method('findByEntityMetadata')
            ->with($entityClass, $entityId)
            ->will($this->returnValue($workflowItem));
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with('OroWorkflowBundle:WorkflowItem')
            ->will($this->returnValue($workflowItemsRepository));

        $this->assertEquals(
            $workflowItem,
            $this->workflowManager->getWorkflowItemByEntity($entity)
        );
    }

    /**
     * @return array
     */
    public function entityDataProvider()
    {
        return [
            'integer'           => [1, $this->createWorkflowItem()],
            'integer_as_string' => ['123', $this->createWorkflowItem()],
            'string'            => ['identifier', null],
            'null'              => [null, null],
            'object'            => [new \stdClass(), null],
        ];
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
        return array(
            'string' => array(
                'workflowIdentifier' => self::TEST_WORKFLOW_NAME,
            ),
            'workflow item' => array(
                'workflowIdentifier' => $this->createWorkflowItem(self::TEST_WORKFLOW_NAME),
            ),
            'workflow' => array(
                'workflowIdentifier' => $this->createWorkflow(self::TEST_WORKFLOW_NAME),
            ),
        );
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Can't find workflow by given identifier.
     */
    public function testGetWorkflowCantFind()
    {
        $incorrectIdentifier = null;
        $this->workflowManager->getWorkflow($incorrectIdentifier);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEntityManager()
    {
        return $this->getMockBuilder('Doctrine\Orm\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('beginTransaction', 'remove', 'persist', 'flush', 'commit', 'rollback'))
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
        array $entityAttributes = array(),
        array $startTransitions = array()
    ) {
        $attributeManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\AttributeManager')
            ->setMethods(array('getManagedEntityAttributes'))
            ->getMock();
        $attributeManager->expects($this->any())
            ->method('getManagedEntityAttributes')
            ->will($this->returnValue($entityAttributes));

        $transitionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\TransitionManager')
            ->setMethods(array('getStartTransitions'))
            ->getMock();
        $transitionManager->expects($this->any())
            ->method('getStartTransitions')
            ->will($this->returnValue(new ArrayCollection($startTransitions)));

        $entityConnector = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\EntityConnector')
            ->disableOriginalConstructor()
            ->getMock();
        $aclManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Acl\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->setConstructorArgs(array($entityConnector, $aclManager, null, $attributeManager, $transitionManager))
            ->setMethods(
                array(
                    'isTransitionAvailable',
                    'isStartTransitionAvailable',
                    'getTransitionsByWorkflowItem',
                    'start',
                    'transit'
                )
            )
            ->getMock();

        /** @var Workflow $workflow */
        $workflow->setName($name);

        return $workflow;
    }

    public function trueFalseDataProvider()
    {
        return array(
            array(true),
            array(false)
        );
    }

    /**
     * @param bool $result
     * @dataProvider trueFalseDataProvider
     */
    public function testHasApplicableWorkflowByEntityClass($result)
    {
        $entityClass = 'TestEntity';

        $this->workflowRegistry->expects($this->once())
            ->method('hasActiveWorkflowByEntityClass')
            ->with($entityClass)
            ->will($this->returnValue($result));

        $this->assertEquals($result, $this->workflowManager->hasApplicableWorkflowByEntityClass($entityClass));
    }

    public function activateWorkflowDataProvider()
    {
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName('test_workflow');
        $workflowDefinition->setRelatedEntity('\DateTime');

        return array(
            'by workflow name' => array(
                'workflow_identifier' => 'test_workflow'
            ),
            'by workflow definition' => array(
                'workflow_identifier' => $workflowDefinition
            ),
        );
    }

    /**
     * @param mixed $workflowIdentifier
     * @dataProvider activateWorkflowDataProvider
     */
    public function testActivateWorkflow($workflowIdentifier)
    {
        if ($workflowIdentifier instanceof WorkflowDefinition) {
            $workflowName = $workflowIdentifier->getName();
            $entityClass = $workflowIdentifier->getRelatedEntity();
        } else {
            $workflowName = $workflowIdentifier;
            $entityClass = '\DateTime';
            $workflowDefinition = new WorkflowDefinition();
            $workflowDefinition->setRelatedEntity($entityClass);
            /** @var Workflow $workflow */
            $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
                ->disableOriginalConstructor()
                ->setMethods(null)
                ->getMock();
            $workflow->setName($workflowName);
            $workflow->setDefinition($workflowDefinition);
            $this->workflowRegistry->expects($this->once())->method('getWorkflow')->with($workflowIdentifier)
                ->will($this->returnValue($workflow));
        }

        $entityConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $entityConfig->expects($this->once())->method('set')->with('active_workflow', $workflowName);

        $workflowConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowConfigProvider->expects($this->once())->method('hasConfig')->with($entityClass)
            ->will($this->returnValue(true));
        $workflowConfigProvider->expects($this->once())->method('getConfig')->with($entityClass)
            ->will($this->returnValue($entityConfig));

        $this->configManager->expects($this->once())->method('getProvider')->with('workflow')
            ->will($this->returnValue($workflowConfigProvider));
        $this->configManager->expects($this->once())->method('persist')->with($entityConfig);
        $this->configManager->expects($this->once())->method('flush');

        $this->workflowManager->activateWorkflow($workflowIdentifier);
    }

    public function testDeactivateWorkflow()
    {
        $entityClass = '\DateTime';
        $workflowMock = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();

        $entityConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $entityConfig->expects($this->once())->method('set')->with('active_workflow', null);

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowByEntityClass')
            ->with($entityClass)
            ->willReturn($workflowMock);

        $workflowConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowConfigProvider->expects($this->once())->method('hasConfig')->with($entityClass)
            ->will($this->returnValue(true));
        $workflowConfigProvider->expects($this->once())->method('getConfig')->with($entityClass)
            ->will($this->returnValue($entityConfig));

        $this->configManager->expects($this->once())->method('getProvider')->with('workflow')
            ->will($this->returnValue($workflowConfigProvider));
        $this->configManager->expects($this->once())->method('persist')->with($entityConfig);
        $this->configManager->expects($this->once())->method('flush');

        $definition = new WorkflowDefinition();
        $workflowMock->expects($this->once())->method('getDefinition')->willReturn($definition);
        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(
            $this->equalTo(WorkflowEvents::WORKFLOW_DEACTIVATED),
            $this->callback(function($e) use ($definition){
                /** @var WorkflowChangesEvent $e*/
                $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent', $e);
                $this->assertSame($definition, $e->getDefinition());
            })
        );

        $this->workflowManager->deactivateWorkflow($entityClass);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Entity \DateTime is not configurable
     */
    public function testNotConfigurableEntityException()
    {
        $entityClass = '\DateTime';

        $workflowConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowConfigProvider->expects($this->once())->method('hasConfig')->with($entityClass)
            ->will($this->returnValue(false));
        $workflowConfigProvider->expects($this->never())->method('getConfig');

        $this->configManager->expects($this->once())->method('getProvider')->with('workflow')
            ->will($this->returnValue($workflowConfigProvider));

        $this->workflowManager->deactivateWorkflow($entityClass);
    }

    public function testResetWorkflowData()
    {
        $name = 'testWorkflow';
        $entityClass = 'Test:Entity';

        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName($name)->setRelatedEntity($entityClass);

        $workflowItemsRepository =
            $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository')
                ->disableOriginalConstructor()
                ->setMethods(array('resetWorkflowData'))
                ->getMock();
        $workflowItemsRepository->expects($this->once())->method('resetWorkflowData')
            ->with($entityClass, array($name));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroWorkflowBundle:WorkflowItem')
            ->will($this->returnValue($workflowItemsRepository));

        $this->workflowManager->resetWorkflowData($workflowDefinition);
    }
}
