<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Periodic;

use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimer;
use Oro\Bundle\SyncBundle\Topic\WebsocketPingTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Ratchet\Wamp\Topic;

class WebsocketPingTopicTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private const TIMEOUT = 333;

    /** @var TopicPeriodicTimer|\PHPUnit\Framework\MockObject\MockObject */
    private $periodicTimer;

    /** @var WebsocketPingTopic */
    private $websocketPing;

    public function setUp()
    {
        $this->periodicTimer = $this->createMock(TopicPeriodicTimer::class);

        $this->websocketPing = new WebsocketPingTopic('oro_sync.ping', self::TIMEOUT);
        $this->websocketPing->setPeriodicTimer($this->periodicTimer);

        $this->setUpLoggerMock($this->websocketPing);
    }

    public function testGetName(): void
    {
        self::assertSame('oro_sync.ping', $this->websocketPing->getName());
    }

    public function testRegisterPeriodicTimer(): void
    {
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('broadcast');

        $this->assertLoggerNotCalled();

        $this->periodicTimer->expects(self::once())
            ->method('addPeriodicTimer')
            ->with(
                $this->websocketPing,
                'oro_sync.ping',
                self::TIMEOUT,
                $this->callback(
                    function ($callable) {
                        if (!is_callable($callable)) {
                            return false;
                        }
                        $callable();
                        return true;
                    }
                )
            );

        $this->websocketPing->registerPeriodicTimer($topic);
    }

    public function testRegisterPeriodicTimerException(): void
    {
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('broadcast')
            ->willThrowException(new \Exception());

        $this->assertLoggerErrorMethodCalled();

        $this->periodicTimer->expects(self::once())
            ->method('addPeriodicTimer')
            ->with(
                $this->websocketPing,
                'oro_sync.ping',
                self::TIMEOUT,
                $this->callback(
                    function ($callable) {
                        if (!\is_callable($callable)) {
                            return false;
                        }
                        $callable();
                        return true;
                    }
                )
            );

        $this->websocketPing->registerPeriodicTimer($topic);
    }
}
