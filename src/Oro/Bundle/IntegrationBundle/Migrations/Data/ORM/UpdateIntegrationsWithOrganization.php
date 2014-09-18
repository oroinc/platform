<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;

class UpdateIntegrationsWithOrganization extends UpdateWithOrganization implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData'
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->update($manager, 'OroIntegrationBundle:Channel');
    }
}
