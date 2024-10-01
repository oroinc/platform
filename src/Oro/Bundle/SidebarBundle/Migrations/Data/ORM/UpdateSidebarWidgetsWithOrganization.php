<?php

namespace Oro\Bundle\SidebarBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;
use Oro\Bundle\SidebarBundle\Entity\Widget;

/**
 * Sets a default organization to Widget entity.
 */
class UpdateSidebarWidgetsWithOrganization extends UpdateWithOrganization implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganizationAndBusinessUnitData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->update($manager, Widget::class);
    }
}
