<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;
use Oro\Bundle\DashboardBundle\Model\Factory;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Model\WidgetModel;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * The base class for fixtures that load dashboard widgets.
 */
abstract class AbstractDashboardFixture extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Creates a dashboard.
     */
    protected function createAdminDashboardModel(ObjectManager $manager, string $dashboardName): DashboardModel
    {
        $dashboardManager = $this->getDashboardManager();
        $user = $this->getAdminUser($manager);
        $dashboard = $dashboardManager->createDashboardModel()
            ->setName($dashboardName)
            ->setLabel($dashboardName)
            ->setOwner($user)
            ->setOrganization($user->getOrganization());
        $dashboard->getEntity()->setDashboardType(
            $manager->getRepository(ExtendHelper::buildEnumValueClassName('dashboard_type'))
                ->findOneBy(['id' => 'widgets'])
        );

        $dashboardManager->save($dashboard);

        return $dashboard;
    }

    /**
     * Creates a dashboard widget.
     */
    protected function createWidgetModel(string  $widgetName, array $layoutPosition = null): WidgetModel
    {
        $dashboardManager = $this->getDashboardManager();
        $widget = $dashboardManager->createWidgetModel($widgetName);
        if (null !== $layoutPosition) {
            $widget->setLayoutPosition($layoutPosition);
        }
        $dashboardManager->save($widget);

        return $widget;
    }

    /**
     * Finds a dashboard.
     */
    protected function findAdminDashboardModel(ObjectManager $manager, string $dashboardName): ?DashboardModel
    {
        $dashboard = $this->container->get('doctrine')->getRepository(Dashboard::class)
            ->findOneBy(['name' => $dashboardName, 'owner' => $this->getAdminUser($manager)]);
        if (null === $dashboard) {
            return null;
        }

        $widgets = new ArrayCollection();
        foreach ($dashboard->getWidgets() as $widget) {
            $widgets->add($this->getFactory()->createWidgetModel($widget));
        }

        return new DashboardModel($dashboard, $widgets, []);
    }

    private function getAdminUser(ObjectManager $manager): User
    {
        $repository = $manager->getRepository(Role::class);
        $role = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);
        if (!$role) {
            throw new InvalidArgumentException('Administrator role should exist.');
        }

        $user = $repository->getFirstMatchedUser($role);
        if (!$user) {
            throw new InvalidArgumentException('Administrator user should exist to load dashboard configuration.');
        }

        return $user;
    }

    private function getFactory(): Factory
    {
        return $this->container->get('oro_dashboard.factory');
    }

    private function getDashboardManager(): Manager
    {
        return $this->container->get('oro_dashboard.manager');
    }
}
