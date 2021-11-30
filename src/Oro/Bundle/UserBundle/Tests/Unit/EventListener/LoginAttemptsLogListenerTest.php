<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\EventListener\LoginAttemptsLogListener;
use Oro\Bundle\UserBundle\Security\LoginAttemptsHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginAttemptsLogListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoginAttemptsHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $handler;

    /** @var LoginAttemptsLogListener */
    private $listener;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(LoginAttemptsHandlerInterface::class);

        $this->listener = new LoginAttemptsLogListener($this->handler);
    }

    public function testOnInteractiveLogin(): void
    {
        $event = new InteractiveLoginEvent(
            $this->createMock(Request::class),
            $this->createMock(TokenInterface::class)
        );

        $this->handler->expects(self::once())
            ->method('onInteractiveLogin')
            ->with(self::identicalTo($event));

        $this->listener->onInteractiveLogin($event);
    }

    public function testOnAuthenticationFailure(): void
    {
        $event = new AuthenticationFailureEvent(
            $this->createMock(UsernamePasswordToken::class),
            $this->createMock(AuthenticationException::class)
        );

        $this->handler->expects(self::once())
            ->method('onAuthenticationFailure')
            ->with(self::identicalTo($event));

        $this->listener->onAuthenticationFailure($event);
    }
}
