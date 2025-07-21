<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Authenticator\UsernamePasswordOrganizationAuthenticator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Exception\ImpersonationAuthenticationException;
use Oro\Bundle\UserBundle\Security\ImpersonationTokenInterface;
use Oro\Bundle\UserBundle\Security\ImpersonationUsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Security\LoginAttemptsHandler;
use Oro\Bundle\UserBundle\Security\LoginSourceProviderForFailedRequestInterface;
use Oro\Bundle\UserBundle\Security\LoginSourceProviderForSuccessRequestInterface;
use Oro\Bundle\UserBundle\Security\SkippedLogAttemptsFirewallsProvider;
use Oro\Bundle\UserBundle\Security\UserLoginAttemptLogger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class LoginAttemptsHandlerTest extends TestCase
{
    private UserManager&MockObject $userManager;
    private UserLoginAttemptLogger&MockObject $userLoginAttemptLogger;
    private SkippedLogAttemptsFirewallsProvider&MockObject $skippedLogAttemptsFirewallsProvider;
    private LoginAttemptsHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->userLoginAttemptLogger = $this->createMock(UserLoginAttemptLogger::class);
        $this->skippedLogAttemptsFirewallsProvider = $this->createMock(SkippedLogAttemptsFirewallsProvider::class);
        $loginSourceProvidersForSuccessRequest = $this
            ->createMock(LoginSourceProviderForSuccessRequestInterface::class);
        $loginSourceProvidersForFailedRequest = $this
            ->createMock(LoginSourceProviderForFailedRequestInterface::class);

        $loginSourceProvidersForSuccessRequest->expects(self::any())
            ->method('getLoginSourceForSuccessRequest')
            ->willReturnCallback(function (TokenInterface $token) {
                if (is_a($token, ImpersonationTokenInterface::class)) {
                    return 'impersonation';
                }

                return null;
            });
        $loginSourceProvidersForFailedRequest->expects(self::any())
            ->method('getLoginSourceForFailedRequest')
            ->willReturnCallback(function (AuthenticatorInterface $authenticator, \Exception $exception) {
                if ($exception instanceof ImpersonationAuthenticationException) {
                    return 'impersonation';
                }

                return null;
            });

        $this->handler = new LoginAttemptsHandler(
            $this->userManager,
            $this->userLoginAttemptLogger,
            $this->skippedLogAttemptsFirewallsProvider,
            [$loginSourceProvidersForSuccessRequest],
            [$loginSourceProvidersForFailedRequest]
        );
    }

    public function testOnInteractiveLogin(): void
    {
        $user = new User();
        $token = new UsernamePasswordToken($user, 'main');

        $this->userLoginAttemptLogger->expects(self::once())
            ->method('logSuccessLoginAttempt')
            ->with($user, 'general');

        $this->handler->onInteractiveLogin(
            new InteractiveLoginEvent(new Request(), $token)
        );
    }

    public function testOnInteractiveLoginWhenTheAttemptShouldNotBeLogged(): void
    {
        $user = new User();
        $token = new UsernamePasswordToken($user, 'main');

        $this->skippedLogAttemptsFirewallsProvider->expects(self::once())
            ->method('getSkippedFirewalls')
            ->willReturn(['test_firewall', 'main', 'another']);

        $this->userLoginAttemptLogger->expects(self::never())
            ->method('logSuccessLoginAttempt');

        $this->handler->onInteractiveLogin(
            new InteractiveLoginEvent(new Request(), $token)
        );
    }

    public function testOnInteractiveLoginWithImpersonateToken(): void
    {
        $user = new User();
        $token = new ImpersonationUsernamePasswordOrganizationToken($user, 'main', new Organization());

        $this->userLoginAttemptLogger->expects(self::once())
            ->method('logSuccessLoginAttempt')
            ->with($user, 'impersonation');

        $this->handler->onInteractiveLogin(
            new InteractiveLoginEvent(new Request(), $token)
        );
    }

    public function testOnInteractiveLoginForUnsupportedUserType(): void
    {
        $user = $this->createMock(UserInterface::class);
        $token = new UsernamePasswordToken($user, 'main');

        $this->userLoginAttemptLogger->expects(self::never())
            ->method('logSuccessLoginAttempt');

        $this->handler->onInteractiveLogin(
            new InteractiveLoginEvent(new Request(), $token)
        );
    }

    public function testOnAuthenticationFailure(): void
    {
        $user = new User();
        $request = new Request();
        $request->attributes->set('user', $user);
        $this->userManager->expects(self::never())
            ->method('findUserByUsernameOrEmail');

        $this->userLoginAttemptLogger->expects(self::once())
            ->method('logFailedLoginAttempt')
            ->with($user, 'general');

        $this->handler->onAuthenticationFailure(
            new LoginFailureEvent(
                $this->createMock(AuthenticationException::class),
                $this->getAuthenticatorMock(),
                $request,
                null,
                'main',
            )
        );
    }

    public function testOnAuthenticationFailureWithImpersonationAuthenticationException(): void
    {
        $user = new User();
        $request = new Request();
        $request->attributes->set('user', $user);
        $this->userManager->expects(self::never())
            ->method('findUserByUsernameOrEmail');

        $this->userLoginAttemptLogger->expects(self::once())
            ->method('logFailedLoginAttempt')
            ->with($user, 'impersonation');

        $this->handler->onAuthenticationFailure(
            new LoginFailureEvent(
                $this->createMock(ImpersonationAuthenticationException::class),
                $this->getAuthenticatorMock(),
                $request,
                null,
                'main',
            )
        );
    }

    public function testOnAuthenticationFailureWithUserAsStringAndUserFound(): void
    {
        $username = 'john';
        $user = new User();
        $request = new Request();
        $request->attributes->set(Security::LAST_USERNAME, $username);
        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn($user);

        $this->userLoginAttemptLogger->expects(self::once())
            ->method('logFailedLoginAttempt')
            ->with($user, 'general');

        $this->handler->onAuthenticationFailure(
            new LoginFailureEvent(
                $this->createMock(AuthenticationException::class),
                $this->getAuthenticatorMock(),
                $request,
                null,
                'main'
            )
        );
    }

    public function testOnAuthenticationFailureWithUserAsStringAndUserNotFound(): void
    {
        $username = 'john';
        $request = new Request();
        $request->attributes->set(Security::LAST_USERNAME, $username);
        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn(null);

        $this->userLoginAttemptLogger->expects(self::once())
            ->method('logFailedLoginAttempt')
            ->with($username, 'general');

        $this->handler->onAuthenticationFailure(
            new LoginFailureEvent(
                $this->createMock(AuthenticationException::class),
                $this->getAuthenticatorMock(),
                $request,
                null,
                'main'
            )
        );
    }

    private function getAuthenticatorMock()
    {
        return $this->createMock(UsernamePasswordOrganizationAuthenticator::class);
    }
}
