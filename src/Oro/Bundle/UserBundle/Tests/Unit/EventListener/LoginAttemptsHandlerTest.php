<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\EventListener\LoginAttemptsHandler;
use Oro\Bundle\UserBundle\EventListener\LoginAttemptsHandlerInterface;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginAttemptsHandlerTest extends \PHPUnit\Framework\TestCase
{
    private UserManager|\PHPUnit\Framework\MockObject\MockObject $userManager;

    private UserLoggingInfoProvider|\PHPUnit\Framework\MockObject\MockObject $infoProvider;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private LoginAttemptsHandler $handler;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->infoProvider = $this->createMock(UserLoggingInfoProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new LoginAttemptsHandler($this->userManager, $this->infoProvider, $this->logger);
    }

    public function testOnAuthenticationFailure(): void
    {
        $user = new User();
        $userInfo = ['user', 'info', 'that', 'must', 'be', 'written', 'into', 'log'];

        $token = $this->createMock(UsernamePasswordToken::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $event = new AuthenticationFailureEvent($token, $this->createMock(AuthenticationException::class));

        $this->infoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn($userInfo);

        $this->logger->expects(self::once())
            ->method('notice')
            ->with(LoginAttemptsHandlerInterface::UNSUCCESSFUL_LOGIN_MESSAGE, $userInfo);

        $this->handler->onAuthenticationFailure($event);
    }

    public function testOnAuthenticationFailureWithUserAsString(): void
    {
        $user = 'some wrong username';
        $userInfo = [
            'username' => 'some wrong username',
            'ipaddress' => '127.0.0.1',
        ];

        $token = $this->createMock(UsernamePasswordToken::class);
        $token->expects(self::atLeastOnce())
            ->method('getUser')
            ->willReturn($user);

        $event = new AuthenticationFailureEvent($token, $this->createMock(AuthenticationException::class));

        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with($user)
            ->willReturn(null);

        $this->infoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn($userInfo);

        $this->logger->expects(self::once())
            ->method('notice')
            ->with(LoginAttemptsHandlerInterface::UNSUCCESSFUL_LOGIN_MESSAGE, $userInfo);

        $this->handler->onAuthenticationFailure($event);
    }

    public function testOnInteractiveLogin(): void
    {
        $user = new User();
        $userInfo = ['user', 'info', 'that', 'must', 'be', 'written', 'into', 'log'];

        $this->infoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($user)
            ->willReturn($userInfo);

        $token = $this->createMock(UsernamePasswordToken::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $event = new InteractiveLoginEvent(new Request(), $token);

        $this->logger->expects(self::once())
            ->method('info')
            ->with(LoginAttemptsHandlerInterface::SUCCESSFUL_LOGIN_MESSAGE, $userInfo);

        $this->handler->onInteractiveLogin($event);
    }

    public function testOnInteractiveLoginWrongUser(): void
    {
        $user = 'some wrong username';

        $token = $this->createMock(UsernamePasswordToken::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $event = new InteractiveLoginEvent(new Request(), $token);

        $this->logger->expects(self::never())
            ->method('info');

        $this->handler->onInteractiveLogin($event);
    }
}
