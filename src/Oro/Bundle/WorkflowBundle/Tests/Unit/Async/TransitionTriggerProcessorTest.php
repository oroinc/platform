<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;

use Psr\Log\LoggerInterface;

use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerProcessor;
use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Handler\TransitionTriggerHandlerInterface;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class TransitionTriggerProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const TRIGGER_ID = 42;
    const MAIN_ENTITY_CLASS = 'stdClass';
    const MAIN_ENTITY_ID = 105;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $objectManager;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;
    
    /** @var TransitionTriggerHandlerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $handler;

    /** @var TransitionTriggerProcessor */
    protected $processor;

    /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    protected function setUp()
    {
        $this->objectManager = $this->getMock(ObjectManager::class);

        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($this->objectManager);

        $this->logger = $this->getMock(LoggerInterface::class);
        $this->handler = $this->getMock(TransitionTriggerHandlerInterface::class);

        $this->processor = new TransitionTriggerProcessor($this->registry, $this->logger, $this->handler);

        $this->session = $this->getMock(SessionInterface::class);
    }

    protected function tearDown()
    {
        unset($this->processor, $this->registry, $this->handler, $this->logger, $this->objectManager, $this->session);
    }

    public function testProcess()
    {
        $trigger = $this->getTriggerMock();
        $message = TransitionTriggerMessage::create($trigger, self::MAIN_ENTITY_ID);

        $this->setUpObjectManager($trigger);
        $this->setUpLogger();

        $this->handler->expects($this->once())->method('process')->with($trigger, $message)->willReturn(true);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessageMock(), $this->session)
        );
    }

    public function testProcessTransitionNotAllowed()
    {
        $trigger = $this->getTriggerMock();
        $message = $this->getMessageMock();

        $this->setUpObjectManager($trigger);
        $this->setUpLogger(
            true,
            '[TransitionTriggerProcessor] Transition not allowed',
            [
                'message_body' => $message->getBody(),
                'trigger' => $trigger
            ],
            'warning'
        );

        $this->handler->expects($this->once())->method('process')->willReturn(false);

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    /**
     * @dataProvider processWithInvalidMessageProvider
     *
     * @param array $data
     * @param \Exception $expectedException
     * @param bool $isTrigger
     */
    public function testProcessWithInvalidMessage(array $data, $expectedException, $isTrigger = false)
    {
        $trigger = $isTrigger ? $this->getTriggerMock() : null;
        $message = $this->getMessageMock($data);

        $this->setUpObjectManager($trigger);
        $this->setUpLogger(
            true,
            '[TransitionTriggerProcessor] Queue message could not be processed.',
            [
                'message_body' => $message->getBody(),
                'exception' => $expectedException
            ]
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
                'expectedException' => new \InvalidArgumentException('Given json should not be empty')
            ],
            'without trigger id' => [
                'data' => ['test' => 1],
                'expectedException' => new \InvalidArgumentException(
                    'Message should contain valid transition trigger id'
                )
            ],
            'empty trigger id' => [
                'data' => [TransitionTriggerMessage::TRANSITION_TRIGGER => null],
                'expectedException' => new \InvalidArgumentException(
                    'Message should contain valid transition trigger id'
                )
            ],
            'trigger id, without trigger entity' => [
                'data' => [TransitionTriggerMessage::TRANSITION_TRIGGER => self::TRIGGER_ID],
                'expectedException' => new EntityNotFoundException(
                    sprintf('Transition trigger entity with identifier %d not found', self::TRIGGER_ID)
                )
            ]
        ];
    }

    /**
     * @return BaseTransitionTrigger|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getTriggerMock()
    {
        $trigger = $this->getMockBuilder(BaseTransitionTrigger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $trigger->expects($this->any())->method('getId')->willReturn(self::TRIGGER_ID);

        return $trigger;
    }

    /**
     * @param array $data
     * @return MessageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMessageMock(array $data = null)
    {
        if (null === $data) {
            $data = [
                TransitionTriggerMessage::TRANSITION_TRIGGER => self::TRIGGER_ID,
                TransitionTriggerMessage::MAIN_ENTITY => self::MAIN_ENTITY_ID
            ];
        }

        $message = $this->getMock(MessageInterface::class);
        $message->expects($this->any())->method('getBody')->willReturn(json_encode($data));

        return $message;
    }

    /**
     * @param BaseTransitionTrigger $trigger
     */
    private function setUpObjectManager(BaseTransitionTrigger $trigger = null)
    {
        $this->objectManager->expects($this->any())
            ->method('find')
            ->with(BaseTransitionTrigger::class, self::TRIGGER_ID)
            ->willReturn($trigger);
    }

    /**
     * @param bool $called
     * @param string $expectedMessage
     * @param array $expectedContext
     * @param string $type
     */
    private function setUpLogger($called = false, $expectedMessage = '', array $expectedContext = [], $type = 'error')
    {
        if ($called) {
            $this->logger->expects($this->once())
                ->method($type)
                ->willReturnCallback(
                    function ($message, array $context) use ($expectedMessage, $expectedContext) {
                        $this->assertEquals($expectedMessage, $message);

                        foreach ($expectedContext as $key => $value) {
                            $this->assertArrayHasKey($key, $context);

                            if ($value instanceof \Exception) {
                                $this->assertInstanceOf(get_class($value), $context[$key]);
                                $this->assertEquals($value->getMessage(), $context[$key]->getMessage());
                            } else {
                                $this->assertEquals($value, $context[$key]);
                            }
                        }
                    }
                );
        } else {
            $this->logger->expects($this->never())->method($this->anything());
        }
    }
}
