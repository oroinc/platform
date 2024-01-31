<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Updates dashboard "main" widget.
 */
class UpdateDefaultDashboard extends AbstractDashboardFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadDashboardData::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $mainDashboard = $this->findAdminDashboardModel($manager, 'main');
        if ($mainDashboard) {
            $mainDashboard->setIsDefault(true);
            $mainDashboard->setLabel($this->container->get('translator')->trans('oro.dashboard.title.main'));
            $manager->flush();
        }
    }
}
