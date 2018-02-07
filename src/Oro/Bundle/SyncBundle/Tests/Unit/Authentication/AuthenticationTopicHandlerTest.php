<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication;

use Psr\Log\LoggerInterface;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampConnection;
use Ratchet\WebSocket\Version\RFC6455\Connection;

//use JDare\ClankBundle\Server\App\Handler\TopicHandler;

use Oro\Bundle\SyncBundle\Authentication\AuthenticationTopicHandler;

class AuthenticationTopicHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AuthenticationTopicHandler */
    private $topicHandler;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $innerTopicHandler;

    /** @var WampConnection */
    private $connection;

    /** @var Topic */
    private $topic;

    protected function setUp()
    {
        $this->markTestSkipped();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->innerTopicHandler = $this->createMock(TopicHandler::class);

        $this->topicHandler = new AuthenticationTopicHandler($this->innerTopicHandler, $this->logger);

        $this->connection = new WampConnection(new Connection($this->createMock(ConnectionInterface::class)));
        $this->connection->remoteAddress = 'localhost';
        $this->connection->resourceId = 45654;

        $this->topic = new Topic('oro/test_topic');
    }

    public function testOnUnSubscribe()
    {
        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                'Unsubscribe client from the topic "{topic}"',
                ['remoteAddress' => 'localhost', 'connectionId' => 45654, 'topic' => 'oro/test_topic']
            );
        $this->innerTopicHandler->expects($this->once())
            ->method('onUnSubscribe')
            ->with($this->connection, $this->topic);

        $this->topicHandler->onUnSubscribe($this->connection, $this->topic);
    }

    public function testOnSubscribeWithAuthenticatedClient()
    {
        $this->connection->Authenticated = true;

        $this->topic->add($this->connection);

        $this->innerTopicHandler->expects($this->once())
            ->method('onSubscribe')
            ->with($this->connection, $this->topic);

        $this->topicHandler->onSubscribe($this->connection, $this->topic);

        $this->assertTrue($this->topic->has($this->connection));
    }

    public function testOnSubscribeClientWithoutAuthentication()
    {
        $this->topic->add($this->connection);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Skip subscribing to the topic "{topic}", because the client is not authenticated',
                ['remoteAddress' => 'localhost', 'connectionId' => 45654, 'topic' => 'oro/test_topic']
            );

        $this->innerTopicHandler->expects($this->once())
            ->method('onSubscribe')
            ->with($this->connection, $this->topic);

        $this->topicHandler->onSubscribe($this->connection, $this->topic);

        $this->assertFalse($this->topic->has($this->connection));
    }

    public function testOnSubscribeWithNotAuthenticatedClient()
    {
        $this->connection->Authenticated = false;

        $this->topic->add($this->connection);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Skip subscribing to the topic "{topic}", because the client is not authenticated',
                ['remoteAddress' => 'localhost', 'connectionId' => 45654, 'topic' => 'oro/test_topic']
            );

        $this->innerTopicHandler->expects($this->once())
            ->method('onSubscribe')
            ->with($this->connection, $this->topic);

        $this->topicHandler->onSubscribe($this->connection, $this->topic);

        $this->assertFalse($this->topic->has($this->connection));
    }

    public function testOnPublishWithoutAuthentication()
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Skip the sending of a message to the topic "{topic}", because the client is not authenticated',
                ['remoteAddress' => 'localhost', 'connectionId' => 45654, 'topic' => 'oro/test_topic']
            );
        $this->innerTopicHandler->expects($this->never())
            ->method('onPublish');

        $this->topicHandler->onPublish($this->connection, $this->topic, 'test_message', [], []);
    }

    public function testOnPublishWithNotAuthenticatedClient()
    {
        $this->connection->Authenticated = false;

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Skip the sending of a message to the topic "{topic}", because the client is not authenticated',
                ['remoteAddress' => 'localhost', 'connectionId' => 45654, 'topic' => 'oro/test_topic']
            );
        $this->innerTopicHandler->expects($this->never())
            ->method('onPublish');

        $this->topicHandler->onPublish($this->connection, $this->topic, 'test_message', [], []);
    }

    public function testOnPublishWithAuthenticatedClient()
    {
        $this->connection->Authenticated = true;

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                'Send a message to the topic "{topic}"',
                ['remoteAddress' => 'localhost', 'connectionId' => 45654, 'topic' => 'oro/test_topic']
            );
        $this->innerTopicHandler->expects($this->once())
            ->method('onPublish')
            ->with($this->connection, $this->topic, 'test_message', [], []);

        $this->topicHandler->onPublish($this->connection, $this->topic, 'test_message', [], []);
    }

    public function testGetSubscribedTopics()
    {
        $this->connection->Authenticated = true;
        $this->topic->add($this->connection);
        $this->innerTopicHandler->expects($this->once())
            ->method('onSubscribe')
            ->with($this->connection, $this->topic);
        $this->topicHandler->onSubscribe($this->connection, $this->topic);

        $collectedTopics = $this->topicHandler->getSubscribedTopics();
        $this->assertEquals(
            ['oro/test_topic' => $this->topic],
            $collectedTopics
        );
    }
}
