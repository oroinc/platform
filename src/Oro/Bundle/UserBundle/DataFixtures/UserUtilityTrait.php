<?php

namespace Oro\Bundle\UserBundle\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Provides a method to get the first user from the database.
 */
trait UserUtilityTrait
{
    /**
     * @param ObjectManager $manager
     * @return User
     * @throws \LogicException
     */
    protected function getFirstUser(ObjectManager $manager)
    {
        $users = $manager->getRepository(User::class)->findBy([], ['id' => 'ASC'], 1);
        if (!$users) {
            throw new \LogicException('There are no users in system');
        }

        return reset($users);
    }
}
