<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\EventListener\LoginAttemptsLogSubscriber;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginAttemptsLogSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userManager;

    /** @var UserLoggingInfoProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $infoProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var LoginAttemptsLogSubscriber */
    private $subscriber;

    public function setUp()
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->infoProvider = $this->createMock(UserLoggingInfoProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new LoginAttemptsLogSubscriber($this->userManager, $this->infoProvider, $this->logger);
    }

    public function testGetSubscribedEvents()
    {
        self::assertSame([
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            SecurityEvents::INTERACTIVE_LOGIN            => 'onInteractiveLogin',
        ], $this->subscriber::getSubscribedEvents());
    }

    public function testOnAuthenticationFailure()
    {
        $user = new User();
        $userInfo = ['user', 'info', 'that', 'must', 'be', 'written', 'into', 'log'];

        $token = $this->createMock(UsernamePasswordToken::class);
        $token->expects($this->once())->method('getUser')->willReturn($user);

        $event = new AuthenticationFailureEvent($token, $this->createMock(AuthenticationException::class));

        $this->infoProvider->expects($this->once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn($userInfo);

        $this->logger->expects($this->once())
            ->method('notice')
            ->with(LoginAttemptsLogSubscriber::UNSUCCESSFUL_LOGIN_MESSAGE, $userInfo);

        $this->subscriber->onAuthenticationFailure($event);
    }

    public function testOnAuthenticationFailureWithUserAsString()
    {
        $user = 'some wrong username';
        $userInfo = [
            'username' => 'some wrong username',
            'ipaddress' => '127.0.0.1'
        ];

        $token = $this->createMock(UsernamePasswordToken::class);
        $token->expects($this->atLeastOnce())->method('getUser')->willReturn($user);

        $event = new AuthenticationFailureEvent($token, $this->createMock(AuthenticationException::class));

        $this->userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with($user)
            ->willReturn(null);

        $this->infoProvider->expects($this->once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn($userInfo);

        $this->logger->expects($this->once())
            ->method('notice')
            ->with(LoginAttemptsLogSubscriber::UNSUCCESSFUL_LOGIN_MESSAGE, $userInfo);

        $this->subscriber->onAuthenticationFailure($event);
    }

    public function testOnInteractiveLogin()
    {
        $user = new User();
        $userInfo = ['user', 'info', 'that', 'must', 'be', 'written', 'into', 'log'];

        $this->infoProvider->expects($this->once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn($userInfo);

        $token = $this->createMock(UsernamePasswordToken::class);
        $token->expects($this->once())->method('getUser')->willReturn($user);

        $event = $this->createMock(InteractiveLoginEvent::class);
        $event->expects($this->once())->method('getAuthenticationToken')->willReturn($token);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(LoginAttemptsLogSubscriber::SUCCESSFUL_LOGIN_MESSAGE, $userInfo);

        $this->subscriber->onInteractiveLogin($event);
    }

    public function testOnInteractiveLoginWrongUser()
    {
        $user = 'some wrong username';

        $token = $this->createMock(UsernamePasswordToken::class);
        $token->expects($this->once())->method('getUser')->willReturn($user);

        $event = $this->createMock(InteractiveLoginEvent::class);
        $event->expects($this->once())->method('getAuthenticationToken')->willReturn($token);

        $this->logger->expects($this->never())
            ->method('info');

        $this->subscriber->onInteractiveLogin($event);
    }
}
