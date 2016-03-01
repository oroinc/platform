<?php

namespace Oro\Bundle\UserBundle\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\User;

trait UserUtilityTrait
{
    /**
     * @param ObjectManager $manager
     * @return User
     * @throws \LogicException
     */
    protected function getFirstUser(ObjectManager $manager)
    {
        $users = $manager->getRepository('OroUserBundle:User')->findBy([], ['id' => 'ASC'], 1);
        if (!$users) {
            throw new \LogicException('There are no users in system');
        }

        return reset($users);
    }
}
