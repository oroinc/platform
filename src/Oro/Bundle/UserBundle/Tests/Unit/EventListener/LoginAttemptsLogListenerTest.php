<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Authentication\Authenticator\UsernamePasswordOrganizationAuthenticator;
use Oro\Bundle\UserBundle\EventListener\LoginAttemptsLogListener;
use Oro\Bundle\UserBundle\Security\LoginAttemptsHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class LoginAttemptsLogListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoginAttemptsHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $handler;

    /** @var LoginAttemptsLogListener */
    private $listener;

    #[\Override]
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
        $authenticator = $this->createMock(UsernamePasswordOrganizationAuthenticator::class);
        $event = new LoginFailureEvent(
            $this->createMock(AuthenticationException::class),
            $authenticator,
            new Request(),
            null,
            'main'
        );

        $this->handler->expects(self::once())
            ->method('onAuthenticationFailure')
            ->with(self::identicalTo($event));

        $this->listener->onAuthenticationFailure($event);
    }
}
