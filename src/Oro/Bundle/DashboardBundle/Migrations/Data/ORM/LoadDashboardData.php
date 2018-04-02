<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadDashboardData extends AbstractDashboardFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData',
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\UpdateUserEntitiesWithOrganization'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $mainDashboard = $this->createAdminDashboardModel($manager, 'main');
        $mainDashboard->addWidget($this->createWidgetModel('quick_launchpad', [0, 10]));

        $manager->flush();
    }
}
