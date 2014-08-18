<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

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
    }
}
