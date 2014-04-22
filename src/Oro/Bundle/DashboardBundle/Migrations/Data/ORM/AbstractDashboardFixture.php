<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;
use Oro\Bundle\UserBundle\Entity\User;

abstract class AbstractDashboardFixture extends AbstractFixture
{
    /**
     * Get administrator user
     *
     * @param ObjectManager $manager
     * @return User
     * @throws InvalidArgumentException
     */
    protected function getAdminUser(ObjectManager $manager)
    {
        $repository = $manager->getRepository('OroUserBundle:Role');
        $role       = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);

        if (!$role) {
            throw new InvalidArgumentException('Administrator role should exist.');
        }

        $user = $repository->getFirstMatchedUser($role);

        if (!$user) {
            throw new InvalidArgumentException(
                'Administrator user should exist to load dashboard configuration.'
            );
        }

        return $user;
    }

    /**
     * Create dashboard entity with admin user
     *
     * @param ObjectManager $manager
     * @param string $dashboardName
     * @return Dashboard
     */
    protected function createAdminDashboard(ObjectManager $manager, $dashboardName)
    {
        $result = new Dashboard();
        $result->setName($dashboardName);
        $result->setOwner($this->getAdminUser($manager));

        $manager->persist($result);

        return $result;
    }

    /**
     * Add new dashboard widget
     *
     * @param ObjectManager $manager
     * @param Dashboard $dashboard
     * @param string $widgetName
     * @return DashboardWidget
     */
    protected function addNewDashboardWidget(ObjectManager $manager, Dashboard $dashboard, $widgetName)
    {
        $result = new DashboardWidget();
        $result->setName($widgetName);

        $dashboard->addWidget($result);

        $manager->persist($result);

        return $result;
    }

    /**
     * Get dashboard of administrator
     *
     * @param ObjectManager $manager
     * @param string $dashboardName
     * @return Dashboard
     */
    protected function findAdminDashboard(ObjectManager $manager, $dashboardName)
    {
        $admin = $this->getAdminUser($manager);

        $result = $manager->getRepository('OroDashboardBundle:Dashboard')
            ->findOneBy(array('name' => $dashboardName, 'owner' => $admin));

        return $result;
    }
}
