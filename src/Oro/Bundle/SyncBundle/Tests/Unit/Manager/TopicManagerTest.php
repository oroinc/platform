<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Manager;

use Oro\Bundle\SyncBundle\Manager\TopicManager;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;

class TopicManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WampServerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $app;

    /** @var TopicManager */
    private $manager;

    protected function setUp(): void
    {
        $this->app = $this->createMock(WampServerInterface::class);

        $this->manager = new TopicManager();
        $this->manager->setWampApplication($this->app);
    }

    public function testOnCloseWhenNoSubscribers(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);

        $this->app->expects($this->once())
            ->method('onClose')
            ->with($connection);

        $this->app->expects($this->never())
            ->method('onUnsubscribe');

        $this->manager->onClose($connection);
    }

    public function testOnClose(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->WAMP = new \stdClass();
        $connection->WAMP->subscriptions = new \SplObjectStorage();

        $this->app->expects($this->once())
            ->method('onClose')
            ->with($connection);

        $topicName = 'sample/topic';
        $this->app->expects($this->once())
            ->method('onSubscribe')
            ->willReturnCallback(function (ConnectionInterface $connection, Topic $topic) use ($topicName) {
                $this->assertEquals($topicName, $topic->getId());
            });

        $this->app->expects($this->once())
            ->method('onUnsubscribe')
            ->willReturnCallback(function (ConnectionInterface $connection, Topic $topic) use ($topicName) {
                $this->assertEquals($topicName, $topic->getId());
            });

        $this->manager->onSubscribe($connection, $topicName);

        $this->manager->onClose($connection);
    }
}
