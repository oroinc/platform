<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\LoginHistory;

class LoginHistoryManager
{
    /** @var ObjectManager $objectManager */
    protected $objectManager;

    /**
     * @param  ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param  UserInterface $user
     * @param  bool $isSuccessful
     * @return LoginHistory
     */
    public function logUserLogin(UserInterface $user, $isSuccessful)
    {
        $history = new LoginHistory();
        $history->setUser($user);
        $history->setSuccessful($isSuccessful);

        $this->objectManager->persist($history);
        $this->objectManager->flush();

        return $history;
    }
}
