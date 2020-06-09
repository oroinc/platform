<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\EventListener\LoginAttemptsHandler;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginAttemptsHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userManager;

    /** @var UserLoggingInfoProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $infoProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var LoginAttemptsHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->infoProvider = $this->createMock(UserLoggingInfoProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new LoginAttemptsHandler($this->userManager, $this->infoProvider, $this->logger);
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
            ->with(LoginAttemptsHandler::UNSUCCESSFUL_LOGIN_MESSAGE, $userInfo);

        $this->handler->onAuthenticationFailure($event);
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
            ->with(LoginAttemptsHandler::UNSUCCESSFUL_LOGIN_MESSAGE, $userInfo);

        $this->handler->onAuthenticationFailure($event);
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
            ->with(LoginAttemptsHandler::SUCCESSFUL_LOGIN_MESSAGE, $userInfo);

        $this->handler->onInteractiveLogin($event);
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

        $this->handler->onInteractiveLogin($event);
    }
}
