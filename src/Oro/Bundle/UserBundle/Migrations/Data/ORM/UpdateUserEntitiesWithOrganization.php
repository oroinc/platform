<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Sets a default organization to User and Group entities.
 */
class UpdateUserEntitiesWithOrganization extends UpdateWithOrganization implements DependentFixtureInterface
{
    private const BATCH_SIZE = 200;

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class,
            LoadGroupData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->update($manager, User::class);
        $this->update($manager, Group::class);

        $organization = $manager->getRepository(Organization::class)->getFirst();
        $usersQB = $manager->getRepository(User::class)->createQueryBuilder('u');
        $users = new BufferedIdentityQueryResultIterator($usersQB);

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
