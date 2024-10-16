<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DashboardBundle\Entity\ActiveDashboard;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\MigrationBundle\Fixture\RenamedFixtureInterface;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;

/**
 * Sets a default organization to Dashboard and ActiveDashboard entities.
 */
class UpdateDashboardsWithOrganization extends UpdateWithOrganization implements
    DependentFixtureInterface,
    RenamedFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadOrganizationAndBusinessUnitData::class,
            LoadDashboardData::class
        ];
    }

    #[\Override]
    public function getPreviousClassNames(): array
    {
        return [
            'Oro\\Bundle\\SidebarBundle\\Migrations\\Data\\ORM\\UpdateDashboardsWithOrganization',
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->update($manager, Dashboard::class);
        $this->update($manager, ActiveDashboard::class);
    }
}
