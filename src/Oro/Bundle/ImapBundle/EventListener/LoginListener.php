<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Oro\Bundle\ImapBundle\OriginSyncCredentials\SyncCredentialsIssueManager;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Listener that runs processing the invalid email origins that was failed during sync for the logged user.
 */
class LoginListener
{
    /** @var SyncCredentialsIssueManager */
    private $syncCredentialsManager;

    public function __construct(SyncCredentialsIssueManager $syncCredentialsManager)
    {
        $this->syncCredentialsManager = $syncCredentialsManager;
    }

    /**
     * Run processing the invalid email origins that was failed during sync for the logged user.
     */
    public function onLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        if ($user instanceof User) {
            $this->syncCredentialsManager->processInvalidOriginsForUser($user);
        }
    }
}
