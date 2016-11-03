<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Handler\TransitionEventTriggerHandler;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Oro\Component\Testing\Unit\EntityTrait;

class TransitionEventTriggerHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ENTITY_CLASS = 'stdClass';
    const WORKFLOW_NAME = 'test_workflow';
    const TRANSITION_NAME = 'test_transition';

    /**
     * @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $featureChecker;

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    private $workflowManager;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    private $objectManager;

    /** @var TransitionEventTriggerHandler */
    private $handler;

    /** @var TransitionEventTrigger */
    private $trigger;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)->disableOriginalConstructor()->getMock();

        $this->objectManager = $this->getMock(ObjectManager::class);

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->objectManager);

        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new TransitionEventTriggerHandler($this->workflowManager, $registry, $this->featureChecker);

        $this->trigger = $this->getEntity(
            TransitionEventTrigger::class,
            [
                'transitionName' => self::TRANSITION_NAME,
                'workflowDefinition' => $this->getEntity(
                    WorkflowDefinition::class,
                    [
                        'name' => self::WORKFLOW_NAME,
                        'relatedEntity' => self::ENTITY_CLASS
                    ]
                )
            ]
        );
    }

    public function testProcessWithWorkflowItem()
    {
        $entityClass = self::ENTITY_CLASS;
        $entityId = 42;
        $entity = new $entityClass();
        $workflowItem = new WorkflowItem();

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with(self::WORKFLOW_NAME, FeatureConfigurationExtension::WORKFLOWS_NODE_NAME)
            ->willReturn(true);

        $this->objectManager->expects($this->once())
            ->method('find')
            ->with(self::ENTITY_CLASS, $entityId)
            ->willReturn($entity);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($entity, self::WORKFLOW_NAME)
            ->willReturn($workflowItem);
        $this->workflowManager->expects($this->once())
            ->method('transitIfAllowed')
            ->with($workflowItem, self::TRANSITION_NAME)
            ->willReturn(true);

        $this->assertTrue(
            $this->handler->process($this->trigger, TransitionTriggerMessage::create($this->trigger, $entityId))
        );
    }

    public function testProcessWithoutWorkflowItem()
    {
        $entityClass = self::ENTITY_CLASS;
        $entityId = 42;
        $entity = new $entityClass();

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with(self::WORKFLOW_NAME, FeatureConfigurationExtension::WORKFLOWS_NODE_NAME)
            ->willReturn(true);

        $this->objectManager->expects($this->once())
            ->method('find')
            ->with(self::ENTITY_CLASS, $entityId)
            ->willReturn($entity);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($entity, self::WORKFLOW_NAME)
            ->willReturn(null);
        $this->workflowManager->expects($this->once())
            ->method('startWorkflow')
            ->with(self::WORKFLOW_NAME, $entity, self::TRANSITION_NAME, [], false)
            ->willReturn(true);

        $this->assertTrue(
            $this->handler->process($this->trigger, TransitionTriggerMessage::create($this->trigger, $entityId))
        );
    }

    /**
     * @dataProvider processExceptionDataProvider
     *
     * @param null|array $entityId
     * @param string $expectedException
     * @param string $expectedMessage
     */
    public function testProcessException($entityId, $expectedException, $expectedMessage)
    {
        $message = TransitionTriggerMessage::create($this->trigger, $entityId);

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with(self::WORKFLOW_NAME, FeatureConfigurationExtension::WORKFLOWS_NODE_NAME)
            ->willReturn(true);

        $this->setExpectedException($expectedException, $expectedMessage);

        $this->handler->process($this->trigger, $message);
    }

    public function testProcessDisabledFeature()
    {
        $entityId = 42;

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with(self::WORKFLOW_NAME, FeatureConfigurationExtension::WORKFLOWS_NODE_NAME)
            ->willReturn(false);

        $this->objectManager->expects($this->never())
            ->method($this->anything());

        $this->workflowManager->expects($this->never())
            ->method($this->anything());

        $this->assertFalse(
            $this->handler->process($this->trigger, TransitionTriggerMessage::create($this->trigger, $entityId))
        );
    }

    /**
     * @return array
     */
    public function processExceptionDataProvider()
    {
        $id = ['test' => 1];

        return [
            'empty entity id' => [
                'entityId' => null,
                'expectedException' => \InvalidArgumentException::class,
                'expectedMessage' => sprintf('Message should contain valid %s id', self::ENTITY_CLASS)
            ],
            'without entity' => [
                'data' => 42,
                'expectedException' => EntityNotFoundException::class,
                'expectedMessage' => sprintf('Entity %s with identifier %s not found', self::ENTITY_CLASS, 42)
            ],
            'without entity array key' => [
                'data' => ['test' => 1],
                'expectedException' => EntityNotFoundException::class,
                'expectedMessage' => sprintf(
                    'Entity %s with identifier %s not found',
                    self::ENTITY_CLASS,
                    json_encode($id)
                )
            ]
        ];
    }
}
