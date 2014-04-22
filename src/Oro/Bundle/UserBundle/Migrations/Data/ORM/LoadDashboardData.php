<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\DashboardBundle\Migrations\Data\ORM\AbstractDashboardFixture;

class LoadDashboardData extends AbstractDashboardFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\DashboardBundle\Migrations\Data\ORM\LoadDashboardData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $mainDashboard = $this->findAdminDashboard($manager, 'main');

        if ($mainDashboard) {
            $this->addNewDashboardWidget($manager, $mainDashboard, 'recent_emails')
                ->setLayoutPosition([0, 30]);

            $manager->flush();
        }
    }
}
