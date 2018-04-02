<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository;
use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;
use Oro\Bundle\DashboardBundle\Model\Factory;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Model\WidgetModel;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractDashboardFixture extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Create dashboard entity with admin user
     *
     * @param ObjectManager $manager
     * @param string $dashboardName
     * @return DashboardModel
     */
    protected function createAdminDashboardModel(ObjectManager $manager, $dashboardName)
    {
        $adminUser = $this->getAdminUser($manager);

        $dashboard = $this->getDashboardManager()
            ->createDashboardModel()
            ->setName($dashboardName)
            ->setOwner($adminUser)
            ->setOrganization($adminUser->getOrganization());

        $this->getDashboardManager()->save($dashboard);

        return $dashboard;
    }

    /**
     * Create dashboard entity with admin user
     *
     * @param string $widgetName
     * @param array $layoutPosition
     * @return WidgetModel
     */
    protected function createWidgetModel($widgetName, array $layoutPosition = null)
    {
        $widget = $this->getDashboardManager()
            ->createWidgetModel($widgetName);

        if (null !== $layoutPosition) {
            $widget->setLayoutPosition($layoutPosition);
        }

        $this->getDashboardManager()->save($widget);

        return $widget;
    }

    /**
     * Find dashboard of administrator
     *
     * @param ObjectManager $manager
     * @param string $dashboardName
     * @return DashboardModel|null
     */
    protected function findAdminDashboardModel(ObjectManager $manager, $dashboardName)
    {
        $dashboard = $this->getDashboardRepository()
            ->findOneBy(['name' => $dashboardName, 'owner' => $this->getAdminUser($manager)]);

        if ($dashboard) {
            $widgets = new ArrayCollection();
            foreach ($dashboard->getWidgets() as $widget) {
                $model = $this->getFactory()->createWidgetModel($widget);
                $widgets->add($model);
            }

            return new DashboardModel(
                $dashboard,
                $widgets,
                []
            );
        }

        return null;
    }

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
     * @return Factory
     */
    protected function getFactory()
    {
        return $this->container->get('oro_dashboard.factory');
    }

    /**
     * @return Manager
     */
    protected function getDashboardManager()
    {
        return $this->container->get('oro_dashboard.manager');
    }

    /**
     * @return DashboardRepository
     */
    protected function getDashboardRepository()
    {
        return $this->container->get('doctrine')->getRepository('OroDashboardBundle:Dashboard');
    }
}
