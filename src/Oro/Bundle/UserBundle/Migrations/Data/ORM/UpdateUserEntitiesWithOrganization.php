<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;

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
        $this->update($manager, 'OroUserBundle:User');
        $this->update($manager, 'OroUserBundle:Group');
        $this->update($manager, 'OroUserBundle:UserApi');

        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $usersQB      = $manager->getRepository('OroUserBundle:User')->createQueryBuilder('u');
        $users        = new BufferedQueryResultIterator($usersQB);

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
                $manager->clear('OroUserBundle:User');
            }
        }

        $manager->flush();
        $manager->clear('OroUserBundle:User');
    }
}
