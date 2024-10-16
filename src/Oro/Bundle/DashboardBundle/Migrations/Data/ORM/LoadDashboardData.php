<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\UpdateUserEntitiesWithOrganization;

/**
 * Creates "main" dashboard.
 */
class LoadDashboardData extends AbstractDashboardFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class,
            UpdateUserEntitiesWithOrganization::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $mainDashboard = $this->createAdminDashboardModel($manager, 'main');
        $mainDashboard->addWidget($this->createWidgetModel('quick_launchpad', [0, 10]));
        $manager->flush();
    }
}
