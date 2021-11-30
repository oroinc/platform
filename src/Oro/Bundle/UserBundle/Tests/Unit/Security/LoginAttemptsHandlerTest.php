<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProviderInterface;
use Oro\Bundle\UserBundle\Security\LoginAttemptsHandler;
use Oro\Component\Testing\Logger\BufferingLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginAttemptsHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userManager;

    /** @var UserLoggingInfoProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $loggingInfoProvider;

    /** @var BufferingLogger */
    private $logger;

    /** @var LoginAttemptsHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->loggingInfoProvider = $this->createMock(UserLoggingInfoProviderInterface::class);
        $this->logger = new BufferingLogger();

        $this->handler = new LoginAttemptsHandler($this->userManager, $this->loggingInfoProvider, $this->logger);
    }


    public function testOnInteractiveLogin(): void
    {
        $user = new User();
        $token = new UsernamePasswordToken($user, 'password', 'main');
        $logContext = ['user' => ['username' => 'john'], 'ipaddress' => '127.0.0.1'];

        $this->loggingInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with(self::identicalTo($user))
            ->willReturn($logContext);

        $this->handler->onInteractiveLogin(
            new InteractiveLoginEvent(new Request(), $token)
        );

        self::assertEquals(
            [
                ['info', 'Successful login', $logContext]
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testOnInteractiveLoginForUnsupportedUserType(): void
    {
        $user = $this->createMock(UserInterface::class);
        $token = new UsernamePasswordToken($user, 'password', 'main');

        $this->handler->onInteractiveLogin(
            new InteractiveLoginEvent(new Request(), $token)
        );

        self::assertEquals([], $this->logger->cleanLogs());
    }

    public function testOnAuthenticationFailure(): void
    {
        $user = new User();
        $token = new UsernamePasswordToken($user, 'wrongPassword', 'main');
        $logContext = ['user' => ['username' => 'john'], 'ipaddress' => '127.0.0.1'];

        $this->userManager->expects(self::never())
            ->method('findUserByUsernameOrEmail');

        $this->loggingInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with(self::identicalTo($user))
            ->willReturn($logContext);

        $this->handler->onAuthenticationFailure(
            new AuthenticationFailureEvent($token, $this->createMock(AuthenticationException::class))
        );

        self::assertEquals(
            [
                ['notice', 'Unsuccessful login', $logContext]
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testOnAuthenticationFailureWithUserAsStringAndUserFound(): void
    {
        $username = 'john';
        $user = new User();
        $token = new UsernamePasswordToken($username, 'wrongPassword', 'main');
        $logContext = ['user' => ['username' => $username], 'ipaddress' => '127.0.0.1'];

        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn($user);

        $this->loggingInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with(self::identicalTo($user))
            ->willReturn($logContext);

        $this->handler->onAuthenticationFailure(
            new AuthenticationFailureEvent($token, $this->createMock(AuthenticationException::class))
        );

        self::assertEquals(
            [
                ['notice', 'Unsuccessful login', $logContext]
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testOnAuthenticationFailureWithUserAsStringAndUserNotFound(): void
    {
        $username = 'john';
        $token = new UsernamePasswordToken($username, 'wrongPassword', 'main');
        $logContext = ['username' => $username, 'ipaddress' => '127.0.0.1'];

        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn(null);

        $this->loggingInfoProvider->expects(self::once())
            ->method('getUserLoggingInfo')
            ->with($username)
            ->willReturn($logContext);

        $this->handler->onAuthenticationFailure(
            new AuthenticationFailureEvent($token, $this->createMock(AuthenticationException::class))
        );

        self::assertEquals(
            [
                ['notice', 'Unsuccessful login', $logContext]
            ],
            $this->logger->cleanLogs()
        );
    }
}
