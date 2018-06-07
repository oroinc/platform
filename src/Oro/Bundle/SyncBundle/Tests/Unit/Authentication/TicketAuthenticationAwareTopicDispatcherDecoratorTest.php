<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface;
use Oro\Bundle\SyncBundle\Authentication\TicketAuthenticationAwareTopicDispatcherDecorator;
use Oro\Bundle\SyncBundle\Registry\SubscribedTopicsRegistryInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampConnection;
use Ratchet\WebSocket\Version\RFC6455\Connection;

class TicketAuthenticationAwareTopicDispatcherDecoratorTest extends \PHPUnit_Framework_TestCase
{
    use LoggerAwareTraitTestTrait;

    private const PAYLOAD = 'test_message';

    /**
     * @var TopicDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $decoratedTopicDispatcher;

    /**
     * @var TicketAuthenticationAwareTopicDispatcherDecorator
     */
    private $decorator;

    /**
     * @var WampConnection
     */
    private $connection;

    /**
     * @var WampRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    private $wampRequest;

    /**
     * @var Topic
     */
    private $topic;

    protected function setUp()
    {
        $this->decoratedTopicDispatcher = $this->createMock(TopicDispatcherInterface::class);

        $this->decorator = new TicketAuthenticationAwareTopicDispatcherDecorator(
            $this->decoratedTopicDispatcher
        );

        $this->setUpLoggerMock($this->decorator);

        $conn = $this->createMock(ConnectionInterface::class);
        $conn->WebSocket = new \StdClass();
        $conn->WebSocket->request = null;
        $conn->WebSocket->established = false;
        $conn->WebSocket->closing = false;

        $this->connection = new WampConnection(new Connection($conn));
        $this->connection->remoteAddress = 'localhost';
        $this->connection->resourceId = 45654;

        $this->wampRequest = $this->createMock(WampRequest::class);

        $this->topic = new Topic('oro/test_topic');
    }

    public function testOnUnSubscribe()
    {
        $this->assertLoggerDebugMethodCalled();

        $this->decoratedTopicDispatcher
            ->expects(self::once())
            ->method('onUnSubscribe')
            ->with($this->connection, $this->topic, $this->wampRequest);

        $this->decorator
            ->onUnSubscribe($this->connection, $this->topic, $this->wampRequest);
    }

    public function testOnSubscribeWithAuthenticatedClient()
    {
        $this->connection->Authenticated = true;

        $this->topic->add($this->connection);

        $this->decoratedTopicDispatcher
            ->expects(self::once())
            ->method('onSubscribe')
            ->with($this->connection, $this->topic, $this->wampRequest);

        $this->decorator
            ->onSubscribe($this->connection, $this->topic, $this->wampRequest);

        self::assertTrue($this->topic->has($this->connection));
    }

    public function testOnSubscribeClientWithoutAuthentication()
    {
        $this->topic->add($this->connection);

        $this->assertLoggerWarningMethodCalled();

        $this->decoratedTopicDispatcher->expects(self::never())->method('onSubscribe');

        $this->decorator
            ->onSubscribe($this->connection, $this->topic, $this->wampRequest);

        self::assertFalse($this->topic->has($this->connection));
    }

    public function testOnSubscribeWithNotAuthenticatedClient()
    {
        $this->connection->Authenticated = false;

        $this->topic->add($this->connection);

        $this->assertLoggerWarningMethodCalled();

        $this->decoratedTopicDispatcher->expects(self::never())->method('onSubscribe');

        $this->decorator
            ->onSubscribe($this->connection, $this->topic, $this->wampRequest);

        self::assertFalse($this->topic->has($this->connection));
    }

    public function testOnPublishWithoutAuthentication()
    {
        $this->assertLoggerWarningMethodCalled();

        $this->decoratedTopicDispatcher
            ->expects(self::never())
            ->method('onPublish');

        $this->decorator
            ->onPublish($this->connection, $this->topic, $this->wampRequest, self::PAYLOAD, [], []);
    }

    public function testOnPublishWithNotAuthenticatedClient()
    {
        $this->connection->Authenticated = false;

        $this->assertLoggerWarningMethodCalled();

        $this->decoratedTopicDispatcher
            ->expects(self::never())
            ->method('onPublish');

        $this->decorator
            ->onPublish($this->connection, $this->topic, $this->wampRequest, self::PAYLOAD, [], []);
    }

    public function testOnPublishWithAuthenticatedClient()
    {
        $this->connection->Authenticated = true;

        $this->assertLoggerDebugMethodCalled();

        $this->decoratedTopicDispatcher
            ->expects(self::once())
            ->method('onPublish')
            ->with($this->connection, $this->topic, $this->wampRequest, self::PAYLOAD, [], []);

        $this->decorator
            ->onPublish($this->connection, $this->topic, $this->wampRequest, self::PAYLOAD, [], []);
    }

    public function testDispatch()
    {
        $calledMethod = 'sampleMethod';
        $this->decoratedTopicDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with($calledMethod, $this->connection, $this->topic, $this->wampRequest, self::PAYLOAD, [], []);

        $this->decorator
            ->dispatch($calledMethod, $this->connection, $this->topic, $this->wampRequest, self::PAYLOAD, [], []);
    }
}
