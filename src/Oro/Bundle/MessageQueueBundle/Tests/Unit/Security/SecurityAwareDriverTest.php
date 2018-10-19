<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Security;

use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SecurityAwareDriverTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DriverInterface */
    private $driver;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface */
    private $tokenStorage;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenSerializerInterface */
    private $tokenSerializer;

    /** @var SecurityAwareDriver */
    private $securityAwareDriver;

    protected function setUp()
    {
        $this->driver = $this->createMock(DriverInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->tokenSerializer = $this->createMock(TokenSerializerInterface::class);

        $this->securityAwareDriver = new SecurityAwareDriver(
            $this->driver,
            ['security_agnostic_topic'],
            $this->tokenStorage,
            $this->tokenSerializer
        );
    }

    public function testSendShouldNotAddSecurityTokenToMessageIfItWasSentToSecurityAgnosticTopic()
    {
        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'security_agnostic_topic');
        $queue = $this->createMock(QueueInterface::class);

        $this->tokenStorage->expects(self::never())
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

    public function testSendShouldAddSecurityTokenToMessageIfItWasNotAddedYet()
    {
        $message = new Message();
        $queue = $this->createMock(QueueInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $serializedToken = 'serialized';

        $this->tokenStorage->expects(self::once())
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

    public function testSendShouldNotAddSecurityTokenToMessageIfItWasAlreadyAdded()
    {
        $message = new Message();
        $queue = $this->createMock(QueueInterface::class);
        $serializedToken = 'serialized';

        $message->setProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN, $serializedToken);

        $this->tokenStorage->expects(self::never())
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

    public function testSendShouldNotAddSecurityTokenToMessageIfNoTokenInTokenStorage()
    {
        $message = new Message();
        $queue = $this->createMock(QueueInterface::class);

        $this->tokenStorage->expects(self::once())
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

    public function testSendShouldNotAddSecurityTokenToMessageIfTokenCannotBeSerialized()
    {
        $message = new Message();
        $queue = $this->createMock(QueueInterface::class);
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $this->tokenSerializer->expects(self::once())
            ->method('serialize')
            ->with(self::identicalTo($token))
            ->willReturn(null);

        $this->driver->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($queue), self::identicalTo($message));

        $this->securityAwareDriver->send($queue, $message);

        self::assertEquals([], $message->getProperties());
    }

    public function testSendShouldSerializeAlreadyAddedSecurityTokenIfItImplementsTokenInterface()
    {
        $message = new Message();
        $queue = $this->createMock(QueueInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $serializedToken = 'serialized';

        $message->setProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN, $token);

        $this->tokenStorage->expects(self::never())
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

    public function testSendShouldRemoveAlreadyAddedSecurityTokenIfItImplementsTokenInterfaceButItCannotBeSerialized()
    {
        $message = new Message();
        $queue = $this->createMock(QueueInterface::class);
        $token = $this->createMock(TokenInterface::class);

        $message->setProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN, $token);

        $this->tokenStorage->expects(self::never())
            ->method('getToken');
        $this->tokenSerializer->expects(self::once())
            ->method('serialize')
            ->with(self::identicalTo($token))
            ->willReturn(null);

        $this->driver->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($queue), self::identicalTo($message));

        $this->securityAwareDriver->send($queue, $message);

        self::assertEquals([], $message->getProperties());
    }

    public function testSendShouldRemoveAlreadyAddedSecurityTokenIfMessageWasSentToSecurityAgnosticTopic()
    {
        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'security_agnostic_topic');
        $message->setProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN, 'serialized');
        $queue = $this->createMock(QueueInterface::class);

        $this->tokenStorage->expects(self::never())
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

    public function testCreateTransportMessage()
    {
        $message = $this->createMock(MessageInterface::class);
        $this->driver->expects(self::once())
            ->method('createTransportMessage')
            ->willReturn($message);

        self::assertSame($message, $this->securityAwareDriver->createTransportMessage());
    }

    public function testCreateQueue()
    {
        $queueName = 'testQueue';
        $queue = $this->createMock(QueueInterface::class);
        $this->driver->expects(self::once())
            ->method('createQueue')
            ->with($queueName)
            ->willReturn($queue);

        self::assertSame($queue, $this->securityAwareDriver->createQueue($queueName));
    }

    public function testGetConfig()
    {
        $config = $this->createMock(Config::class);
        $this->driver->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        self::assertSame($config, $this->securityAwareDriver->getConfig());
    }
}
