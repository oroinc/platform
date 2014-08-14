<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;

class UpdateUserWithOrganization extends UpdateWithOrganization implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData'];
    }

    /**
     * Update users with organization
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->update($manager, 'OroUserBundle:User');

        $organization   = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $users          = $manager->getRepository('OroUserBundle:User')->findAll();
        foreach ($users as $user) {
            $user->setOrganizations(new ArrayCollection(array($organization)));
            $manager->persist($user);
        }
        $manager->flush();
    }
}
