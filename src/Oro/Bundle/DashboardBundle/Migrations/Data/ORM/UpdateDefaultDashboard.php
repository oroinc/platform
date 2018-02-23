<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class UpdateDefaultDashboard extends AbstractDashboardFixture implements DependentFixtureInterface
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
        $mainDashboard = $this->findAdminDashboardModel($manager, 'main');

        if ($mainDashboard) {
            $mainDashboard->setIsDefault(true);
            $mainDashboard->setLabel(
                $this->container->get('translator')->trans('oro.dashboard.title.main')
            );

            $manager->flush();
        }
    }
}
