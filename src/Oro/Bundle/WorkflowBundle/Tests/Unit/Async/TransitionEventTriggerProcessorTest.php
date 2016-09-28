<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Psr\Log\LoggerInterface;

use Oro\Bundle\WorkflowBundle\Async\Model\TransitionEventTriggerMessage;
use Oro\Bundle\WorkflowBundle\Async\TransitionEventTriggerProcessor;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class TransitionEventTriggerProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const TRANSITION_EVENT_TRIGGER_ID = 42;
    const WORKFLOW_ITEM_ID = 142;
    const MAIN_ENTITY_CLASS = 'stdClass';
    const MAIN_ENTITY_ID = 105;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $objectManager;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var TransitionEventTriggerProcessor */
    protected $processor;

    /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    protected function setUp()
    {
        $this->objectManager = $this->getMock(ObjectManager::class);

        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($this->objectManager);

        $this->manager = $this->getMockBuilder(WorkflowManager::class)->disableOriginalConstructor()->getMock();

        $this->logger = $this->getMock(LoggerInterface::class);

        $this->processor = new TransitionEventTriggerProcessor($this->registry, $this->manager, $this->logger);

        $this->session = $this->getMock(SessionInterface::class);
    }

    protected function tearDown()
    {
        unset($this->processor, $this->registry, $this->manager, $this->logger, $this->objectManager, $this->session);
    }

    public function testProcess()
    {
        $trigger = $this->getEntity(TransitionEventTrigger::class, ['transitionName' => 'test transition']);
        $workflowItem = $this->getEntity(WorkflowItem::class);

        $this->setUpObjectManager($trigger, $workflowItem);

        $this->manager->expects($this->once())
            ->method('transitIfAllowed')
            ->with($workflowItem, $trigger->getTransitionName())
            ->willReturn(true);

        $this->setUpLogger(null, null);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessageMock(), $this->session)
        );
    }

    public function testProcessWithoutWorkflowItemWithEntity()
    {
        $transitionName = 'test transition';
        $workflowName = 'test definition name';

        $trigger = $this->getEntity(
            TransitionEventTrigger::class,
            [
                'transitionName' => $transitionName,
                'workflowDefinition' => $this->getEntity(
                    WorkflowDefinition::class,
                    [
                        'name' => $workflowName,
                        'relatedEntity' => self::MAIN_ENTITY_CLASS
                    ]
                )
            ]
        );

        $entityClass = self::MAIN_ENTITY_CLASS;
        $entity = new $entityClass();

        $this->setUpObjectManager($trigger, null, $entity);

        $this->manager->expects($this->once())
            ->method('startWorkflow')
            ->with($workflowName, $entity, $transitionName)
            ->willReturn(true);

        $this->setUpLogger(null, null);

        $message = $this->getMessageMock(
            [
                TransitionEventTriggerMessage::TRANSITION_EVENT_TRIGGER => self::TRANSITION_EVENT_TRIGGER_ID,
                TransitionEventTriggerMessage::WORKFLOW_ITEM => null,
                TransitionEventTriggerMessage::MAIN_ENTITY => self::MAIN_ENTITY_ID
            ]
        );

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
    }

    public function testProcessWithoutWorkflowItemAndEntity()
    {
        $trigger = $this->getEntity(
            TransitionEventTrigger::class,
            [
                'workflowDefinition' => $this->getEntity(
                    WorkflowDefinition::class,
                    ['relatedEntity' => self::MAIN_ENTITY_CLASS]
                )
            ]
        );

        $this->setUpObjectManager($trigger, null, null);

        $this->manager->expects($this->never())->method($this->anything());

        $message = $this->getMessageMock(
            [
                TransitionEventTriggerMessage::TRANSITION_EVENT_TRIGGER => self::TRANSITION_EVENT_TRIGGER_ID,
                TransitionEventTriggerMessage::WORKFLOW_ITEM => null,
                TransitionEventTriggerMessage::MAIN_ENTITY => self::MAIN_ENTITY_ID
            ]
        );

        $this->setUpLogger('Transition not allowed', $message->getBody());

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    public function testProcessTransitionNotAllowed()
    {
        $message = $this->getMessageMock();
        $trigger = $this->getEntity(TransitionEventTrigger::class, ['transitionName' => 'test transition']);
        $workflowItem = $this->getEntity(WorkflowItem::class);

        $this->setUpObjectManager($trigger, $workflowItem);

        $this->manager->expects($this->once())
            ->method('transitIfAllowed')
            ->with($workflowItem, $trigger->getTransitionName())
            ->willReturn(false);

        $this->setUpLogger('Transition not allowed', $message->getBody());

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    /**
     * @dataProvider processWithInvalidMessageProvider
     *
     * @param array $data
     * @param string $expectedMessage
     * @param bool $isTrigger
     */
    public function testProcessWithInvalidMessage(array $data, $expectedMessage, $isTrigger = false)
    {
        $message = $this->getMessageMock($data);

        $trigger = null;
        if ($isTrigger) {
            $trigger = $this->getEntity(TransitionEventTrigger::class);
        }

        $this->objectManager->expects($this->any())
            ->method('find')
            ->willReturnMap(
                [
                    [TransitionEventTrigger::class, self::TRANSITION_EVENT_TRIGGER_ID, $trigger]
                ]
            );

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                sprintf(
                    'Message could not be processed: %s. Original message: "%s"',
                    $expectedMessage,
                    $message->getBody()
                )
            );

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    /**
     * @return array
     */
    public function processWithInvalidMessageProvider()
    {
        return [
            'empty data' => [
                'data' => [],
                'expectedMessage' => 'Given json should not be empty'
            ],
            'without trigger id' => [
                'data' => ['test' => 1],
                'expectedMessage' => sprintf('Message should contain valid %s id', TransitionEventTrigger::class)
            ],
            'empty trigger id' => [
                'data' => [TransitionEventTriggerMessage::TRANSITION_EVENT_TRIGGER => null],
                'expectedMessage' => sprintf('Message should contain valid %s id', TransitionEventTrigger::class)
            ],
            'trigger id, without trigger entity' => [
                'data' => [
                    TransitionEventTriggerMessage::TRANSITION_EVENT_TRIGGER => self::TRANSITION_EVENT_TRIGGER_ID
                ],
                'expectedMessage' => sprintf(
                    'Entity %s with identifier %d not found',
                    TransitionEventTrigger::class,
                    self::TRANSITION_EVENT_TRIGGER_ID
                )
            ]
        ];
    }

    /**
     * @param array $data
     * @return MessageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMessageMock(array $data = null)
    {
        if (null === $data) {
            $data = [
                TransitionEventTriggerMessage::TRANSITION_EVENT_TRIGGER => self::TRANSITION_EVENT_TRIGGER_ID,
                TransitionEventTriggerMessage::WORKFLOW_ITEM => self::WORKFLOW_ITEM_ID
            ];
        }

        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())->method('getBody')->willReturn(json_encode($data));

        return $message;
    }

    /**
     * @param TransitionEventTrigger $trigger
     * @param WorkflowItem $item
     * @param object $entity
     */
    private function setUpObjectManager(TransitionEventTrigger $trigger, WorkflowItem $item = null, $entity = null)
    {
        $this->objectManager->expects($this->any())
            ->method('find')
            ->willReturnMap(
                [
                    [TransitionEventTrigger::class, self::TRANSITION_EVENT_TRIGGER_ID, $trigger],
                    [WorkflowItem::class, self::WORKFLOW_ITEM_ID, $item],
                    [self::MAIN_ENTITY_CLASS, self::MAIN_ENTITY_ID, $entity]
                ]
            );
    }

    /**
     * @param null|string $message
     * @param null|string $body
     */
    private function setUpLogger($message, $body)
    {
        if ($message) {
            $this->logger->expects($this->once())
                ->method('error')
                ->with(sprintf('Message could not be processed: %s. Original message: "%s"', $message, $body));
        } else {
            $this->logger->expects($this->never())->method($this->anything());
        }
    }
}
