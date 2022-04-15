<?php

namespace Oro\Bundle\WsseAuthenticationBundle\EventListener;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\UserLoginAttemptLogger;
use Oro\Bundle\WsseAuthenticationBundle\Security\WsseToken;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

/**
 * Logs the WSSE login attempts.
 */
class AuthenticationListener
{
    private UserLoginAttemptLogger $logger;

    public function __construct(UserLoginAttemptLogger $logger)
    {
        $this->logger = $logger;
    }

    public function onAuthenticationSuccess(AuthenticationEvent $event): void
    {
        $token = $event->getAuthenticationToken();
        if (!is_a($token, WsseToken::class)) {
            return;
        }

        if ($token->getUser() instanceof User) {
            $this->logger->logSuccessLoginAttempt($token->getUser(), 'wsse');
        }
    }
}
