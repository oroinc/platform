<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;

class LoadRoleData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [LoadUser::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= 3; $i++) {
            $role = new Role();
            $role->setLabel('Role ' . $i);
            $role->setRole('ROLE_' . $i, false);
            $manager->persist($role);
            $this->setReference('role' . $i, $role);
        }
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        for ($i = 1; $i <= 2; $i++) {
            $user->addUserRole($this->getReference('role' . $i));
        }
        $manager->flush();
    }
}
