<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * The default implementation of {@see LoginAttemptsHandlerInterface} that just logs success and failed logins.
 */
class LoginAttemptsHandler implements LoginAttemptsHandlerInterface
{
    private BaseUserManager $userManager;
    private UserLoggingInfoProviderInterface $loggingInfoProvider;
    private LoggerInterface $logger;

    public function __construct(
        BaseUserManager $userManager,
        UserLoggingInfoProviderInterface $loggingInfoProvider,
        LoggerInterface $logger
    ) {
        $this->userManager = $userManager;
        $this->loggingInfoProvider = $loggingInfoProvider;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $token = $event->getAuthenticationToken();
        $user = $token->getUser();

        if ($user instanceof AbstractUser) {
            $this->logger->info('Successful login', $this->loggingInfoProvider->getUserLoggingInfo($user));
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

        $this->logger->notice(
            'Unsuccessful login',
            $this->loggingInfoProvider->getUserLoggingInfo(
                $user instanceof AbstractUser ? $user : $token->getUser()
            )
        );
    }
}
