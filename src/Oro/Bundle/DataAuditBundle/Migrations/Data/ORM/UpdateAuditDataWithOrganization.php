<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;

class UpdateAuditDataWithOrganization extends UpdateWithOrganization implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData'];
    }

    /**
     * Update data audit with organization
     */
    public function load(ObjectManager $manager)
    {
        $this->update($manager, 'OroDataAuditBundle:Audit');
    }
}
