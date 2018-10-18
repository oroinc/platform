<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\WebSocket;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;
use Oro\Bundle\ReminderBundle\Model\WebSocket\WebSocketSendProcessor;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\UserBundle\Entity\User;

class WebSocketSendProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WebSocketSendProcessor
     */
    protected $processor;

    /**
     * @var WebsocketClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $websocketClient;

    /**
     * @var ConnectionChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $connectionChecker;

    /**
     * @var MessageParamsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageParamsProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $reminder;

    protected function setUp()
    {
        $this->websocketClient = $this->createMock(WebsocketClientInterface::class);
        $this->connectionChecker = $this->createMock(ConnectionChecker::class);
        $this->messageParamsProvider = $this->createMock(MessageParamsProvider::class);

        $this->processor = new WebSocketSendProcessor(
            $this->websocketClient,
            $this->connectionChecker,
            $this->messageParamsProvider
        );
    }

    public function testPush()
    {
        $fooUserId = 100;
        $fooUser = $this->createUser($fooUserId);
        $barUserId = 200;
        $barUser = $this->createUser($barUserId);

        $fooReminder = $this->createReminder($fooUser);
        $barReminder = $this->createReminder($barUser);
        $bazReminder = $this->createReminder($barUser);

        $this->processor->push($fooReminder);
        $this->processor->push($barReminder);
        $this->processor->push($bazReminder);

        $this->assertAttributeEquals(
            [
                $fooUserId => [$fooReminder],
                $barUserId => [$barReminder, $bazReminder],
            ],
            'remindersByRecipient',
            $this->processor
        );
    }

    public function testProcess()
    {
        $fooUserId = 100;
        $fooUser = $this->createUser($fooUserId);
        $barUserId = 200;
        $barUser = $this->createUser($barUserId);

        $fooReminder = $this->createReminder($fooUser);
        $fooMessage = ['data' => 'foo'];
        $barReminder = $this->createReminder($barUser);
        $barMessage = ['data' => 'bar'];
        $bazReminder = $this->createReminder($barUser);
        $bazMessage = ['data' => 'baz'];

        $this->processor->push($fooReminder);
        $this->processor->push($barReminder);
        $this->processor->push($bazReminder);

        $this->messageParamsProvider->expects($this->exactly(3))
            ->method('getMessageParams')
            ->willReturnMap(
                [
                    [$fooReminder, $fooMessage],
                    [$barReminder, $barMessage],
                    [$bazReminder, $bazMessage]
                ]
            );

        $this->connectionChecker
            ->expects($this->exactly(2))
            ->method('checkConnection')
            ->willReturn(true);

        $this->websocketClient
            ->expects($this->at(0))
            ->method('publish')
            ->with("oro/reminder_remind/{$fooUserId}", [$fooMessage]);

        $this->websocketClient
            ->expects($this->at(1))
            ->method('publish')
            ->with("oro/reminder_remind/{$barUserId}", [$barMessage, $bazMessage]);

        $fooReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_REQUESTED);

        $barReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_REQUESTED);

        $bazReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_REQUESTED);

        $this->processor->process();
    }

    public function testProcessFail()
    {
        $fooUserId = 100;
        $fooUser = $this->createUser($fooUserId);

        $fooReminder = $this->createReminder($fooUser);
        $fooMessage = ['data' => 'foo'];
        $barReminder = $this->createReminder($fooUser);
        $barMessage = ['data' => 'bar'];

        $this->processor->push($fooReminder);
        $this->processor->push($barReminder);

        $this->messageParamsProvider->expects($this->exactly(2))
            ->method('getMessageParams')
            ->will(
                $this->returnValueMap(
                    [
                        [$fooReminder, $fooMessage],
                        [$barReminder, $barMessage]
                    ]
                )
            );

        $exception = new \Exception();

        $this->connectionChecker
            ->expects($this->once())
            ->method('checkConnection')
            ->willReturn(true);

        $this->websocketClient
            ->expects($this->once())
            ->method('publish')
            ->with("oro/reminder_remind/{$fooUserId}", [$fooMessage, $barMessage])
            ->will($this->throwException($exception));

        $fooReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_FAIL);

        $fooReminder->expects($this->once())
            ->method('setFailureException')
            ->with($exception);

        $barReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_FAIL);

        $barReminder->expects($this->once())
            ->method('setFailureException')
            ->with($exception);

        $this->processor->process();
    }

    public function testProcessNoConnection()
    {
        $fooUserId = 100;
        $fooUser = $this->createUser($fooUserId);

        $fooReminder = $this->createReminder($fooUser);
        $fooMessage = ['data' => 'foo'];

        $this->processor->push($fooReminder);

        $this->messageParamsProvider->expects($this->once())
            ->method('getMessageParams')
            ->with($fooReminder)
            ->willReturn($fooMessage);

        $this->connectionChecker
            ->expects($this->once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->websocketClient
            ->expects($this->never())
            ->method($this->anything());

        $fooReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_REQUESTED);

        $this->processor->process();
    }

    public function testGetName()
    {
        $this->assertEquals(
            WebSocketSendProcessor::NAME,
            $this->processor->getName()
        );
    }

    public function testGetLabel()
    {
        $this->assertEquals(
            'oro.reminder.processor.web_socket.label',
            $this->processor->getLabel()
        );
    }

    /**
     * @param $recipient
     * @return Reminder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createReminder(User $recipient)
    {
        $result = $this->createMock(Reminder::class);
        $result->expects($this->atLeastOnce())
            ->method('getRecipient')
            ->willReturn($recipient);
        return $result;
    }

    /**
     * @param int $userId
     * @return User|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createUser($userId)
    {
        $result = $this->createMock(User::class);
        $result->expects($this->atLeastOnce())->method('getId')->willReturn($userId);
        return $result;
    }
}
