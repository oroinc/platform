<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Psr\Log\LoggerInterface;

use Oro\Bundle\WorkflowBundle\Async\TransitionEventTriggerProcessor;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Helper\TransitionEventTriggerHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class TransitionEventTriggerProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const TRANSITION_TRIGGER_EVENT_ID = 42;
    const ENTITY_CLASS = 'stdClass';
    const ENTITY_ID = 142;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $objectManager;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var TransitionEventTriggerHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $helper;

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

        $this->helper = $this->getMockBuilder(TransitionEventTriggerHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = $this->getMockBuilder(WorkflowManager::class)->disableOriginalConstructor()->getMock();

        $this->logger = $this->getMock(LoggerInterface::class);

        $this->processor = new TransitionEventTriggerProcessor(
            $this->registry,
            $this->helper,
            $this->manager,
            $this->logger
        );

        $this->session = $this->getMock(SessionInterface::class);
    }

    protected function tearDown()
    {
        unset(
            $this->processor,
            $this->registry,
            $this->helper,
            $this->manager,
            $this->logger,
            $this->objectManager,
            $this->session
        );
    }

    public function testProcess()
    {
        $trigger = $this->getTrigger();
        $entity = $this->getEntity(self::ENTITY_CLASS);
        $workflowItem = $this->getEntity(WorkflowItem::class);

        $this->setUpObjectManager($trigger, $entity);

        $this->helper->expects($this->once())->method('isRequirePass')->with($trigger, $entity)->willReturn(true);

        $this->manager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($entity, $trigger->getWorkflowDefinition()->getName())
            ->willReturn($workflowItem);
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

    public function testProcessNotRequirePass()
    {
        $message = $this->getMessageMock();
        $trigger = $this->getTrigger();
        $entity = $this->getEntity(self::ENTITY_CLASS);

        $this->setUpObjectManager($trigger, $entity);

        $this->helper->expects($this->once())->method('isRequirePass')->with($trigger, $entity)->willReturn(false);

        $this->manager->expects($this->never())->method($this->anything());

        $this->setUpLogger('Require of TransitionEventTrigger was not pass', $message->getBody());

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    public function testProcessNoWorkflowItem()
    {
        $message = $this->getMessageMock();
        $trigger = $this->getTrigger();
        $entity = $this->getEntity(self::ENTITY_CLASS);
        $workflowItem = $this->getEntity(WorkflowItem::class);

        $this->setUpObjectManager($trigger, $entity);

        $this->helper->expects($this->once())->method('isRequirePass')->with($trigger, $entity)->willReturn(true);

        $this->manager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($entity, $trigger->getWorkflowDefinition()->getName())
            ->willReturn($workflowItem);
        $this->manager->expects($this->once())
            ->method('transitIfAllowed')
            ->with($workflowItem, $trigger->getTransitionName())
            ->willReturn(false);

        $this->setUpLogger('Transition not allowed', $message->getBody());

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    public function testProcessTransitionNotAllowed()
    {
        $message = $this->getMessageMock();
        $trigger = $this->getTrigger();
        $entity = $this->getEntity(self::ENTITY_CLASS);

        $this->setUpObjectManager($trigger, $entity);

        $this->helper->expects($this->once())->method('isRequirePass')->with($trigger, $entity)->willReturn(true);

        $this->manager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($entity, $trigger->getWorkflowDefinition()->getName())
            ->willReturn(null);

        $this->setUpLogger('Could not find WorkflowItem', $message->getBody());

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
                    [TransitionEventTrigger::class, self::TRANSITION_TRIGGER_EVENT_ID, $trigger]
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
                'expectedMessage' => 'Message should not be empty'
            ],
            'without trigger id, entity class and id' => [
                'data' => ['test' => 1],
                'expectedMessage' => 'Message should contain valid TransitionEventTrigger id'
            ],
            'empty trigger id, without entity class and id' => [
                'data' => [TransitionEventTriggerProcessor::TRANSITION_TRIGGER_EVENT => null],
                'expectedMessage' => 'Message should contain valid TransitionEventTrigger id'
            ],
            'empty trigger id, no trigger entity, without entity class and id' => [
                'data' => [
                    TransitionEventTriggerProcessor::TRANSITION_TRIGGER_EVENT => self::TRANSITION_TRIGGER_EVENT_ID
                ],
                'expectedMessage' => 'Message should contain valid TransitionEventTrigger id'
            ],
            'without entity class and id' => [
                'data' => [
                    TransitionEventTriggerProcessor::TRANSITION_TRIGGER_EVENT => self::TRANSITION_TRIGGER_EVENT_ID
                ],
                'expectedMessage' => 'Message should contain valid entity class name and id',
                'trigger' => true
            ],
            'empty entity class, without entity id' => [
                'data' => [
                    TransitionEventTriggerProcessor::TRANSITION_TRIGGER_EVENT => self::TRANSITION_TRIGGER_EVENT_ID,
                    TransitionEventTriggerProcessor::ENTITY_CLASS => null,
                ],
                'expectedMessage' => 'Message should contain valid entity class name and id',
                'trigger' => true
            ],
            'without entity id' => [
                'data' => [
                    TransitionEventTriggerProcessor::TRANSITION_TRIGGER_EVENT => self::TRANSITION_TRIGGER_EVENT_ID,
                    TransitionEventTriggerProcessor::ENTITY_CLASS => self::ENTITY_CLASS,
                ],
                'expectedMessage' => 'Message should contain valid entity class name and id',
                'trigger' => true
            ],
            'empty entity id' => [
                'data' => [
                    TransitionEventTriggerProcessor::TRANSITION_TRIGGER_EVENT => self::TRANSITION_TRIGGER_EVENT_ID,
                    TransitionEventTriggerProcessor::ENTITY_CLASS => self::ENTITY_CLASS,
                    TransitionEventTriggerProcessor::ENTITY_ID => null,
                ],
                'expectedMessage' => 'Message should contain valid entity class name and id',
                'trigger' => true
            ],
            'no entity' => [
                'data' => [
                    TransitionEventTriggerProcessor::TRANSITION_TRIGGER_EVENT => self::TRANSITION_TRIGGER_EVENT_ID,
                    TransitionEventTriggerProcessor::ENTITY_CLASS => self::ENTITY_CLASS,
                    TransitionEventTriggerProcessor::ENTITY_ID => self::ENTITY_ID,
                ],
                'expectedMessage' => 'Message should contain valid entity class name and id',
                'trigger' => true
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
                TransitionEventTriggerProcessor::TRANSITION_TRIGGER_EVENT => self::TRANSITION_TRIGGER_EVENT_ID,
                TransitionEventTriggerProcessor::ENTITY_CLASS => self::ENTITY_CLASS,
                TransitionEventTriggerProcessor::ENTITY_ID => self::ENTITY_ID,
            ];
        }

        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())->method('getBody')->willReturn(json_encode($data));

        return $message;
    }

    /**
     * @param string $transitionName
     * @param string $workflowName
     * @return TransitionEventTrigger
     */
    private function getTrigger($transitionName = 'test transition', $workflowName = 'test workflow')
    {
        return $this->getEntity(
            TransitionEventTrigger::class,
            [
                'transitionName' => $transitionName,
                'workflowDefinition' => $this->getEntity(WorkflowDefinition::class, ['name' => $workflowName])
            ]
        );
    }

    /**
     * @param TransitionEventTrigger $trigger
     * @param object $entity
     */
    private function setUpObjectManager(TransitionEventTrigger $trigger, $entity)
    {
        $this->objectManager->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap(
                [
                    [TransitionEventTrigger::class, self::TRANSITION_TRIGGER_EVENT_ID, $trigger],
                    [self::ENTITY_CLASS, self::ENTITY_ID, $entity]
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
