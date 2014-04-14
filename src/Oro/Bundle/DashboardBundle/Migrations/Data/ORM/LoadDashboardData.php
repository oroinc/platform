<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class LoadDashboardData extends AbstractDashboardFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $mainDashboard = $this->createAdminDashboard($manager, 'main');

        if ($mainDashboard) {
            $this->addNewDashboardWidget($manager, $mainDashboard, 'quick_launchpad')
            ->setLayoutPosition([0, 10]);

            $manager->flush();
        }
    }
}
