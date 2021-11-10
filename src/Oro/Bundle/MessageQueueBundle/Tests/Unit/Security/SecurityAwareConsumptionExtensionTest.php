<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Security;

use Oro\Bundle\MessageQueueBundle\Consumption\Exception\InvalidSecurityTokenException;
use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareConsumptionExtension;
use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SecurityAwareConsumptionExtensionTest extends \PHPUnit\Framework\TestCase
{
    private TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject $tokenStorage;

    private TokenSerializerInterface|\PHPUnit\Framework\MockObject\MockObject $tokenSerializer;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private SecurityAwareConsumptionExtension $extension;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->tokenSerializer = $this->createMock(TokenSerializerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->extension = new SecurityAwareConsumptionExtension(
            ['security_agnostic_processor'],
            $this->tokenStorage,
            $this->tokenSerializer
        );
    }

    public function testOnPreReceivedShouldNotSetSecurityTokenForSecurityAgnosticProcessor(): void
    {
        $message = new Message();
        $message->setProperties(
            [
                SecurityAwareDriver::PARAMETER_SECURITY_TOKEN => 'serialized',
            ]
        );

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessorName('security_agnostic_processor');
        $context->setMessage($message);

        $this->tokenSerializer->expects(self::never())
            ->method('deserialize');
        $this->tokenStorage->expects(self::never())
            ->method('setToken');

        $this->extension->onPreReceived($context);
    }

    public function testOnPreReceivedShouldSetSecurityToken(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $serializedToken = 'serialized';

        $message = new Message();
        $message->setProperties([SecurityAwareDriver::PARAMETER_SECURITY_TOKEN => $serializedToken]);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessage($message);
        $context->setLogger($this->logger);

        $this->tokenSerializer->expects(self::once())
            ->method('deserialize')
            ->with($serializedToken)
            ->willReturn($token);
        $this->tokenStorage->expects(self::once())
            ->method('setToken')
            ->with(self::identicalTo($token));
        $this->logger->expects(self::once())
            ->method('debug')
            ->with('Set security token');

        $this->extension->onPreReceived($context);
    }

    public function testOnPreReceivedShouldRejectMessageIfSecurityTokenCannotBeDeserialized(): void
    {
        $this->expectException(InvalidSecurityTokenException::class);
        $serializedToken = 'serialized';

        $message = new Message();
        $message->setProperties([SecurityAwareDriver::PARAMETER_SECURITY_TOKEN => $serializedToken]);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessage($message);
        $context->setLogger($this->logger);

        $this->tokenSerializer->expects(self::once())
            ->method('deserialize')
            ->with($serializedToken)
            ->willReturn(null);
        $this->tokenStorage->expects(self::never())
            ->method('setToken');
        $this->logger->expects(self::once())
            ->method('error')
            ->with('Security token is invalid');

        $this->extension->onPreReceived($context);
    }

    public function testOnPreReceivedShouldDoNothingIdMessageDoesNotContainSecurityToken(): void
    {
        $message = new Message();

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessage($message);

        $this->tokenSerializer->expects(self::never())
            ->method('deserialize');
        $this->tokenStorage->expects(self::never())
            ->method('setToken');

        $this->extension->onPreReceived($context);
    }

    public function testOnPostReceivedShouldSetSecurityTokenToNull(): void
    {
        $context = new Context($this->createMock(SessionInterface::class));

        $this->tokenStorage->expects(self::once())
            ->method('setToken')
            ->with(null);

        $this->extension->onPostReceived($context);
    }
}
