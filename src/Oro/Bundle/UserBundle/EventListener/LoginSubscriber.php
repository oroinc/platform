<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\LoginInfoInterface;
use Oro\Bundle\UserBundle\Entity\PasswordRecoveryInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * This listener save last login time and login count, clear confirmation token on success login
 */
class LoginSubscriber
{
    /**
     * @var BaseUserManager
     */
    protected $userManager;

    public function __construct(BaseUserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function onLogin(InteractiveLoginEvent $event)
    {
        /** @var UserInterface $user */
        $user = $event->getAuthenticationToken()->getUser();
        $isChanged = false;

        if ($user instanceof LoginInfoInterface) {
            $user
                ->setLastLogin(new \DateTime('now', new \DateTimeZone('UTC')))
                ->setLoginCount($user->getLoginCount() + 1);
            $isChanged = true;
        }

        if ($user instanceof PasswordRecoveryInterface && $user->getConfirmationToken()) {
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);
            $isChanged = true;
        }

        if ($isChanged) {
            $this->userManager->updateUser($user);
        }
    }
}
