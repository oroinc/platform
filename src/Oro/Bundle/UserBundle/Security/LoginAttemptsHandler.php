<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Exception\UserHolderExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

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
    public function onAuthenticationFailure(LoginFailureEvent $event): void
    {
        $user = $event->getRequest()->attributes->get('user');
        $contextInnerUser = $event->getRequest()->attributes->get(Security::LAST_USERNAME, '');

        if (null === $user && \is_string($contextInnerUser)) {
            $user = $this->userManager->findUserByUsernameOrEmail($contextInnerUser);
        }
        $exception = $event->getException();
        $source = null;
        foreach ($this->loginSourceProvidersForFailedRequest as $loginSourceProviderByException) {
            $source = $loginSourceProviderByException->getLoginSourceForFailedRequest(
                $event->getAuthenticator(),
                $exception
            );
            if (null !== $source) {
                break;
            }
        }
        if (null === $source) {
            $source = 'general';
        }
        $user = $user instanceof AbstractUser ? $user : $contextInnerUser;
        if (!$user instanceof AbstractUser && $exception instanceof UserHolderExceptionInterface) {
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
