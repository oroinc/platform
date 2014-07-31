<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;

class UpdateGroupWithOrganization extends UpdateWithOrganization implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadGroupData'];
    }

    /**
     * Update groups with organization
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->update($manager, 'OroUserBundle:Group');
    }
}
