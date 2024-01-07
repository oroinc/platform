<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;

/**
 * Assign exists users and groups to the default organization
 */
class UpdateUserEntitiesWithOrganization extends UpdateWithOrganization implements DependentFixtureInterface
{
    const BATCH_SIZE = 200;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData',
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadGroupData'
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->update($manager, User::class);
        $this->update($manager, Group::class);
        $this->update($manager, UserApi::class);

        $organization = $manager->getRepository(Organization::class)->getFirst();
        $usersQB      = $manager->getRepository(User::class)->createQueryBuilder('u');
        $users        = new BufferedIdentityQueryResultIterator($usersQB);

        $iteration = 0;
        /** @var User $user */
        foreach ($users as $user) {
            $iteration++;

            if (!$user->hasOrganization($organization)) {
                $user->addOrganization($organization);
                $manager->persist($user);
            }

            if (0 === $iteration % self::BATCH_SIZE) {
                $manager->flush();
                $manager->clear(User::class);
            }
        }

        $manager->flush();
        $manager->clear(User::class);
    }
}
