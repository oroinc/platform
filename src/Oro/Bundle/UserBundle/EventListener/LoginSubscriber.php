<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use Oro\Bundle\UserBundle\Entity\LoginInfoInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LoginSubscriber
{
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @param UserManager $userManager
     */
    public function __construct(UserManager $userManager)
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
