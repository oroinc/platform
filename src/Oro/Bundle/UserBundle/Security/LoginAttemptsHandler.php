<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Exception\UserHolderExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * The default implementation of {@see LoginAttemptsHandlerInterface} that just logs success and failed logins.
 */
class LoginAttemptsHandler implements LoginAttemptsHandlerInterface
{
    private BaseUserManager $userManager;
    private UserLoginAttemptLogger $userLoginAttemptLogger;
    private SkippedLogAttemptsFirewallsProvider $skippedLogAttemptsFirewallsProvider;

    /** @var iterable|LoginSourceProviderForSuccessRequestInterface[] */
    private iterable $loginSourceProvidersForSuccessRequest;

    /** @var iterable|LoginSourceProviderForFailedRequestInterface[] */
    private iterable $loginSourceProvidersForFailedRequest;

    public function __construct(
        BaseUserManager $userManager,
        UserLoginAttemptLogger $userLoginAttemptLogger,
        SkippedLogAttemptsFirewallsProvider $skippedLogAttemptsFirewallsProvider,
        iterable $loginSourceProvidersForSuccessRequest,
        iterable $loginSourceProvidersForFailedRequest
    ) {
        $this->userManager = $userManager;
        $this->userLoginAttemptLogger = $userLoginAttemptLogger;
        $this->skippedLogAttemptsFirewallsProvider = $skippedLogAttemptsFirewallsProvider;
        $this->loginSourceProvidersForSuccessRequest = $loginSourceProvidersForSuccessRequest;
        $this->loginSourceProvidersForFailedRequest = $loginSourceProvidersForFailedRequest;
    }

    /**
     * {@inheritDoc}
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $token = $event->getAuthenticationToken();
        $user = $token->getUser();

        if ($user instanceof AbstractUser && $this->shouldAttemptBeLogged($token)) {
            $source = null;
            foreach ($this->loginSourceProvidersForSuccessRequest as $loginSourceProviderByToken) {
                $source = $loginSourceProviderByToken->getLoginSourceForSuccessRequest($token);
                if (null !== $source) {
                    break;
                }
            }
            if (null === $source) {
                $source = 'general';
            }
            $this->userLoginAttemptLogger->logSuccessLoginAttempt($user, $source);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $token = $event->getAuthenticationToken();
        $user = $token->getUser();

        if (\is_string($user)) {
            $user = $this->userManager->findUserByUsernameOrEmail($user);
        }

        $exception = $event->getAuthenticationException();
        $source = null;
        foreach ($this->loginSourceProvidersForFailedRequest as $loginSourceProviderByException) {
            $source = $loginSourceProviderByException->getLoginSourceForFailedRequest($token, $exception);
            if (null !== $source) {
                break;
            }
        }
        if (null === $source) {
            $source = 'general';
        }

        $user = $user instanceof AbstractUser ? $user : $token->getUser();
        if (null === $user && $exception instanceof UserHolderExceptionInterface) {
            $user = $exception->getUser();
        }
        $this->userLoginAttemptLogger->logFailedLoginAttempt($user, $source);
    }

    private function shouldAttemptBeLogged(TokenInterface $token): bool
    {
        $skippedFirewalls = $this->skippedLogAttemptsFirewallsProvider->getSkippedFirewalls();
        $shouldBeSkipped = count($skippedFirewalls)
            && is_a($token, UsernamePasswordToken::class)
            && \in_array($token->getFirewallName(), $skippedFirewalls);

        return !$shouldBeSkipped;
    }
}
