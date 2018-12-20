<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\EventListener\AuthenticationFailureListener;
use Oro\Bundle\UserBundle\Exception\BadCredentialsException as BadUserCredentialsException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class AuthenticationFailureListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    private $providerKey = 'test_provider_key';

    /** @var string */
    private $messageKey = 'test.message.key';

    /** @var AuthenticationFailureListener */
    private $listener;

    protected function setUp()
    {
        $this->listener = new AuthenticationFailureListener($this->providerKey, $this->messageKey);
    }

    public function testOnAuthenticationFailure()
    {
        /** @var UsernamePasswordToken|\PHPUnit\Framework\MockObject\MockObject $token */
        $token = $this->createMock(UsernamePasswordToken::class);
        $token->expects($this->once())
            ->method('getProviderKey')
            ->willReturn($this->providerKey);

        $exception = new BadUserCredentialsException();
        $exception->setMessageKey($this->messageKey);

        $this->expectExceptionObject($exception);

        $this->listener->onAuthenticationFailure(new AuthenticationFailureEvent($token, new BadCredentialsException()));
    }

    public function testOnAuthenticationFailureUnsupportedException()
    {
        /** @var UsernamePasswordToken|\PHPUnit\Framework\MockObject\MockObject $token */
        $token = $this->createMock(UsernamePasswordToken::class);
        $token->expects($this->never())
            ->method('getProviderKey');

        $this->listener->onAuthenticationFailure(new AuthenticationFailureEvent($token, new AuthenticationException()));
    }

    public function testOnAuthenticationFailureUnsupportedToken()
    {
        /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject $token */
        $token = $this->createMock(UsernamePasswordToken::class);

        $this->listener->onAuthenticationFailure(new AuthenticationFailureEvent($token, new AuthenticationException()));
    }

    public function testOnAuthenticationFailureUnsupportedProviderKey()
    {
        /** @var UsernamePasswordToken|\PHPUnit\Framework\MockObject\MockObject $token */
        $token = $this->createMock(UsernamePasswordToken::class);
        $token->expects($this->once())
            ->method('getProviderKey')
            ->willReturn('unknown');

        $this->listener->onAuthenticationFailure(new AuthenticationFailureEvent($token, new BadCredentialsException()));
    }
}
