<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;

class UpdateUserEntitiesWithOrganization extends UpdateWithOrganization implements DependentFixtureInterface
{
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

        $organization   = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $users          = $manager->getRepository('OroUserBundle:User')->findAll();
        foreach ($users as $user) {
            if (!$user->hasOrganization($organization)) {
                $user->addOrganization($organization);
                $manager->persist($user);
            }
        }

        $manager->flush();
    }
}
