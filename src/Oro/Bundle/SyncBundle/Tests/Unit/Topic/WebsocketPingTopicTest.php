<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Periodic;

use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimer;
use Oro\Bundle\SyncBundle\Topic\WebsocketPingTopic;
use Psr\Log\LoggerInterface;
use Ratchet\Wamp\Topic;

class WebsocketPingTopicTest extends \PHPUnit_Framework_TestCase
{
    const TIMEOUT = 333;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var TopicPeriodicTimer|\PHPUnit_Framework_MockObject_MockObject */
    protected $periodicTimer;

    /** @var WebsocketPingTopic */
    protected $websocketPing;

    public function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->periodicTimer = $this->createMock(TopicPeriodicTimer::class);

        $this->websocketPing = new WebsocketPingTopic('oro_sync.ping', $this->logger, self::TIMEOUT);
        $this->websocketPing->setPeriodicTimer($this->periodicTimer);
    }

    public function testGetName()
    {
        self::assertSame('oro_sync.ping', $this->websocketPing->getName());
    }

    public function testRegisterPeriodicTimer()
    {
        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('broadcast');

        $this->logger->expects($this->never())
            ->method('error');

        $this->periodicTimer->expects($this->once())
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

    public function testRegisterPeriodicTimerException()
    {
        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('broadcast')
            ->willThrowException(new \Exception());

        $this->logger->expects($this->once())
            ->method('error');

        $this->periodicTimer->expects($this->once())
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
}
