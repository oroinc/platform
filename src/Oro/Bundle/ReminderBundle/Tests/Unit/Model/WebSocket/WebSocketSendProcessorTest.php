<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\WebSocket;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;
use Oro\Bundle\ReminderBundle\Model\WebSocket\WebSocketSendProcessor;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;

class WebSocketSendProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebsocketClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $websocketClient;

    /** @var ConnectionChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $connectionChecker;

    /** @var MessageParamsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $messageParamsProvider;

    /** @var WebSocketSendProcessor */
    private $processor;

    protected function setUp(): void
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

        self::assertEquals(
            [
                $fooUserId => [$fooReminder],
                $barUserId => [$barReminder, $bazReminder],
            ],
            ReflectionUtil::getPropertyValue($this->processor, 'remindersByRecipient')
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
            ->willReturnMap([
                [$fooReminder, $fooMessage],
                [$barReminder, $barMessage],
                [$bazReminder, $bazMessage]
            ]);

        $this->connectionChecker->expects($this->exactly(2))
            ->method('checkConnection')
            ->willReturn(true);

        $this->websocketClient->expects($this->exactly(2))
            ->method('publish')
            ->withConsecutive(
                ["oro/reminder_remind/{$fooUserId}", [$fooMessage]],
                ["oro/reminder_remind/{$barUserId}", [$barMessage, $bazMessage]]
            );

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
            ->willReturnMap([
                [$fooReminder, $fooMessage],
                [$barReminder, $barMessage]
            ]);

        $exception = new \Exception();

        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(true);

        $this->websocketClient->expects($this->once())
            ->method('publish')
            ->with("oro/reminder_remind/{$fooUserId}", [$fooMessage, $barMessage])
            ->willThrowException($exception);

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

        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->websocketClient->expects($this->never())
            ->method($this->anything());

        $fooReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_REQUESTED);

        $this->processor->process();
    }

    public function testGetLabel()
    {
        $this->assertEquals(
            'oro.reminder.processor.web_socket.label',
            $this->processor->getLabel()
        );
    }

    /**
     * @return Reminder|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createReminder(User $recipient)
    {
        $result = $this->createMock(Reminder::class);
        $result->expects($this->atLeastOnce())
            ->method('getRecipient')
            ->willReturn($recipient);

        return $result;
    }

    /**
     * @return User|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createUser(int $userId)
    {
        $result = $this->createMock(User::class);
        $result->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($userId);

        return $result;
    }
}
