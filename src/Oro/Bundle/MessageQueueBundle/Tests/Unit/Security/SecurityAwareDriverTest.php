<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Security;

use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver;
use Oro\Bundle\MessageQueueBundle\Security\SecurityTokenProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\SecurityBundle\Exception\InvalidTokenSerializationException;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SecurityAwareDriverTest extends TestCase
{
    private DriverInterface&MockObject $driver;
    private SecurityTokenProviderInterface&MockObject $tokenProvider;
    private TokenSerializerInterface&MockObject $tokenSerializer;
    private SecurityAwareDriver $securityAwareDriver;

    #[\Override]
    protected function setUp(): void
    {
        $this->driver = $this->createMock(DriverInterface::class);
        $this->tokenProvider = $this->createMock(SecurityTokenProviderInterface::class);
        $this->tokenSerializer = $this->createMock(TokenSerializerInterface::class);

        $this->securityAwareDriver = new SecurityAwareDriver(
            $this->driver,
            ['security_agnostic_topic'],
            $this->tokenProvider,
            $this->tokenSerializer
        );
    }

    public function testSendShouldNotAddSecurityTokenToMessageIfItWasSentToSecurityAgnosticTopic(): void
    {
        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'security_agnostic_topic');
        $queue = $this->createMock(QueueInterface::class);

        $this->tokenProvider->expects(self::never())
            ->method('getToken');
        $this->tokenSerializer->expects(self::never())
            ->method('serialize');

        $this->driver->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($queue), self::identicalTo($message));

        $this->securityAwareDriver->send($queue, $message);

        self::assertEquals(
            [Config::PARAMETER_TOPIC_NAME => 'security_agnostic_topic'],
            $message->getProperties()
        );
    }

    public function testSendShouldAddSecurityTokenToMessageIfItWasNotAddedYet(): void
    {
        $message = new Message();
        $queue = $this->createMock(QueueInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $serializedToken = 'serialized';

        $this->tokenProvider->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $this->tokenSerializer->expects(self::once())
            ->method('serialize')
            ->with(self::identicalTo($token))
            ->willReturn($serializedToken);

        $this->driver->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($queue), self::identicalTo($message));

        $this->securityAwareDriver->send($queue, $message);

        self::assertEquals(
            $serializedToken,
            $message->getProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN)
        );
    }

    public function testSendShouldNotAddSecurityTokenToMessageIfItWasAlreadyAdded(): void
    {
        $message = new Message();
        $queue = $this->createMock(QueueInterface::class);
        $serializedToken = 'serialized';

        $message->setProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN, $serializedToken);

        $this->tokenProvider->expects(self::never())
            ->method('getToken');
        $this->tokenSerializer->expects(self::never())
            ->method('serialize');

        $this->driver->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($queue), self::identicalTo($message));

        $this->securityAwareDriver->send($queue, $message);

        self::assertEquals(
            $serializedToken,
            $message->getProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN)
        );
    }

    public function testSendShouldNotAddSecurityTokenToMessageIfNoToken(): void
    {
        $message = new Message();
        $queue = $this->createMock(QueueInterface::class);

        $this->tokenProvider->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $this->tokenSerializer->expects(self::never())
            ->method('serialize');

        $this->driver->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($queue), self::identicalTo($message));

        $this->securityAwareDriver->send($queue, $message);

        self::assertEquals([], $message->getProperties());
    }

    public function testSendShouldNotAddSecurityTokenToMessageIfTokenCannotBeSerialized(): void
    {
        $message = new Message();
        $queue = $this->createMock(QueueInterface::class);
        $token = $this->createMock(TokenInterface::class);

        $this->tokenProvider->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $this->tokenSerializer->expects(self::once())
            ->method('serialize')
            ->willThrowException(new InvalidTokenSerializationException('Exception message.'));

        $this->driver->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($queue), self::identicalTo($message));

        $this->securityAwareDriver->send($queue, $message);

        self::assertEquals([], $message->getProperties());
    }

    public function testSendShouldSerializeAlreadyAddedSecurityTokenIfItImplementsTokenInterface(): void
    {
        $message = new Message();
        $queue = $this->createMock(QueueInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $serializedToken = 'serialized';

        $message->setProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN, $token);

        $this->tokenProvider->expects(self::never())
            ->method('getToken');
        $this->tokenSerializer->expects(self::once())
            ->method('serialize')
            ->with(self::identicalTo($token))
            ->willReturn($serializedToken);

        $this->driver->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($queue), self::identicalTo($message));

        $this->securityAwareDriver->send($queue, $message);

        self::assertEquals(
            $serializedToken,
            $message->getProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN)
        );
    }

    public function testSendShouldRemoveAlreadyAddedTokenIfItImplementsTokenInterfaceButItCannotBeSerialized(): void
    {
        $message = new Message();
        $queue = $this->createMock(QueueInterface::class);
        $token = $this->createMock(TokenInterface::class);

        $message->setProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN, $token);

        $this->tokenProvider->expects(self::never())
            ->method('getToken');
        $this->tokenSerializer->expects(self::once())
            ->method('serialize')
            ->willThrowException(new InvalidTokenSerializationException('Exception message.'));

        $this->driver->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($queue), self::identicalTo($message));

        $this->securityAwareDriver->send($queue, $message);

        self::assertEquals([], $message->getProperties());
    }

    public function testSendShouldRemoveAlreadyAddedTokenIfMessageWasSentToSecurityAgnosticTopic(): void
    {
        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'security_agnostic_topic');
        $message->setProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN, 'serialized');
        $queue = $this->createMock(QueueInterface::class);

        $this->tokenProvider->expects(self::never())
            ->method('getToken');
        $this->tokenSerializer->expects(self::never())
            ->method('serialize');

        $this->driver->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($queue), self::identicalTo($message));

        $this->securityAwareDriver->send($queue, $message);

        self::assertEquals(
            [Config::PARAMETER_TOPIC_NAME => 'security_agnostic_topic'],
            $message->getProperties()
        );
    }

    public function testCreateTransportMessage(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $this->driver->expects(self::once())
            ->method('createTransportMessage')
            ->willReturn($message);

        self::assertSame($message, $this->securityAwareDriver->createTransportMessage());
    }

    public function testCreateQueue(): void
    {
        $queueName = 'testQueue';
        $queue = $this->createMock(QueueInterface::class);
        $this->driver->expects(self::once())
            ->method('createQueue')
            ->with($queueName)
            ->willReturn($queue);

        self::assertSame($queue, $this->securityAwareDriver->createQueue($queueName));
    }

    public function testGetConfig(): void
    {
        $config = $this->createMock(Config::class);
        $this->driver->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        self::assertSame($config, $this->securityAwareDriver->getConfig());
    }
}
