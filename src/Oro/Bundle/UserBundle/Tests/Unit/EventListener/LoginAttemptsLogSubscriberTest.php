<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\EventListener\LoginAttemptsHandlerInterface;
use Oro\Bundle\UserBundle\EventListener\LoginAttemptsLogSubscriber;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginAttemptsLogSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoginAttemptsHandlerInterface */
    private $handler;

    /** @var LoginAttemptsLogSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(LoginAttemptsHandlerInterface::class);
        $this->subscriber = new LoginAttemptsLogSubscriber($this->handler);
    }

    public function testGetSubscribedEvents()
    {
        self::assertSame(
            [
                AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
                SecurityEvents::INTERACTIVE_LOGIN            => 'onInteractiveLogin',
            ],
            $this->subscriber::getSubscribedEvents()
        );
    }

    public function testOnAuthenticationFailure()
    {
        $event = new AuthenticationFailureEvent(
            $this->createMock(UsernamePasswordToken::class),
            $this->createMock(AuthenticationException::class)
        );

        $this->handler->expects(self::once())
            ->method('onAuthenticationFailure')
            ->with($event);

        $this->subscriber->onAuthenticationFailure($event);
    }

    public function testOnInteractiveLogin()
    {
        $event = $this->createMock(InteractiveLoginEvent::class);

        $this->handler->expects(self::once())
            ->method('onInteractiveLogin')
            ->with($event);

        $this->subscriber->onInteractiveLogin($event);
    }
}
