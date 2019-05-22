<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Security;

use Oro\Bundle\MessageQueueBundle\Consumption\Exception\InvalidSecurityTokenException;
use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareConsumptionExtension;
use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @dbIsolationPerTest
 */
class SecurityAwareConsumptionExtensionTest extends WebTestCase
{
    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var MessageProcessorInterface
     */
    private $messageProcessor;

    /**
     * @var QueueConsumer
     */
    private $consumer;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    protected function setUp(): void
    {
        $this->initClient();
        $this->clearMessages();
        $container = self::getContainer();
        $this->logger = self::createMock(LoggerInterface::class);
        $this->consumer = $container->get('oro_test.consumption.queue_consumer');
        $this->producer = $container->get('oro_test.client.message_producer');
        $this->messageProcessor = $container->get('oro_message_queue.client.delegate_message_processor');
    }

    protected function tearDown(): void
    {
        $this->getTokenStorage()->setToken(null);
        $this->clearMessages();
    }

    public function testWithValidToken(): void
    {
        $serializedToken = 'organizationId=1;userId=1;userClass=Oro\\Bundle\\UserBundle\\Entity\\User;roles=';
        $this->producer->send('topic', $this->createMessage($serializedToken));
        $this->consumer->bind('oro.default', $this->messageProcessor);
        $this->consumer->consume(new ChainExtension([
            new LimitConsumedMessagesExtension(1),
            new SecurityAwareConsumptionExtension(
                [],
                $this->getTokenStorage(),
                $this->getTokenSerializer()
            ),
        ]));
    }

    public function testWithInvalidToken(): void
    {
        $this->producer->send('topic', $this->createMessage('Invalid token;'));
        // This message stop consumer using an extension LimitConsumedMessagesExtension
        // and checks whether the consumer continues to work after an unsuccessful deserialization of the token
        $this->producer->send('topic', 'message');
        $this->consumer->bind('oro.default', $this->messageProcessor);
        self::expectException(InvalidSecurityTokenException::class);
        self::expectExceptionMessage('Security token is invalid');

        $this->consumer->consume(new ChainExtension([
            new LimitConsumedMessagesExtension(1),
            new SecurityAwareConsumptionExtension(
                [],
                $this->getTokenStorage(),
                $this->getTokenSerializer()
            ),
        ]));
    }

    /**
     * @return TokenStorageInterface
     */
    private function getTokenStorage(): TokenStorageInterface
    {
        return $this->getContainer()->get('security.token_storage');
    }

    /**
     * @return TokenSerializerInterface
     */
    private function getTokenSerializer(): TokenSerializerInterface
    {
        return $this->getContainer()->get('oro_security.token_serializer');
    }

    /**
     * @param string $serializedToken
     *
     * @return Message
     */
    private function createMessage($serializedToken = ''): Message
    {
        $message = new Message();
        $message->setProperties([SecurityAwareDriver::PARAMETER_SECURITY_TOKEN => $serializedToken]);

        return $message;
    }

    private function clearMessages()
    {
        $connection = self::getContainer()->get(
            'oro_message_queue.transport.dbal.connection',
            ContainerInterface::NULL_ON_INVALID_REFERENCE
        );
        if ($connection instanceof DbalConnection) {
            $connection->getDBALConnection()->executeQuery('DELETE FROM ' . $connection->getTableName());
        }
    }
}
