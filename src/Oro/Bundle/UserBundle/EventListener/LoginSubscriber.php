<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\LoginInfoInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;

class LoginSubscriber
{
    /**
     * @var BaseUserManager
     */
    protected $userManager;

    /**
     * @param BaseUserManager $userManager
     */
    public function __construct(BaseUserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof LoginInfoInterface) {
            $user
                ->setLastLogin(new \DateTime('now', new \DateTimeZone('UTC')))
                ->setLoginCount($user->getLoginCount() + 1);

            /** @var UserInterface $user */
            $this->userManager->updateUser($user);
        }
    }
}
