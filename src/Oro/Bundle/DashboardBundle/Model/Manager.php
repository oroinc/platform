<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

use Oro\Bundle\DashboardBundle\Entity\ActiveDashboard;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\UserBundle\Entity\User;

class Manager
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Constructor
     *
     * @param Factory $factory
     * @param EntityManager $entityManager
     * @param AclHelper $aclHelper
     */
    public function __construct(Factory $factory, EntityManager $entityManager, AclHelper $aclHelper)
    {
        $this->factory = $factory;
        $this->entityManager = $entityManager;
        $this->aclHelper = $aclHelper;
    }

    /**
     * Find dashboard model by id
     *
     * @param integer $id
     * @return DashboardModel|null
     */
    public function findDashboardModel($id)
    {
        $entity = $this->entityManager->getRepository('OroDashboardBundle:Dashboard')->find($id);

        if ($entity) {
            return $this->getDashboardModel($entity);
        }

        return null;
    }

    /**
     * Find dashboard widget model by id
     *
     * @param integer $id
     * @return WidgetModel|null
     */
    public function findWidgetModel($id)
    {
        $entity = $this->entityManager->getRepository('OroDashboardBundle:Widget')->find($id);

        if ($entity) {
            return $this->getWidgetModel($entity);
        }

        return null;
    }

    /**
     * Get dashboard
     *
     * @param Dashboard $entity
     * @return DashboardModel
     */
    public function getDashboardModel(Dashboard $entity)
    {
        return $this->factory->createDashboardModel($entity);
    }

    /**
     * Get dashboard widget
     *
     * @param Widget $entity
     * @return WidgetModel
     */
    public function getWidgetModel(Widget $entity)
    {
        return $this->factory->createWidgetModel($entity);
    }

    /**
     * Get all dashboards
     *
     * @param array $entities
     * @return DashboardModel[]
     */
    public function getDashboardModels(array $entities)
    {
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $this->getDashboardModel($entity);
        }

        return $result;
    }

    /**
     * Create dashboard
     *
     * @return DashboardModel
     */
    public function createDashboardModel()
    {
        $dashboard = new Dashboard();

        return $this->getDashboardModel($dashboard);
    }

    /**
     * Create dashboard widget
     *
     * @param string $widgetName
     * @return WidgetModel
     */
    public function createWidgetModel($widgetName)
    {
        $widget = new Widget();

        $widget->setExpanded(true);
        $widget->setName($widgetName);

        return $this->getWidgetModel($widget);
    }

    /**
     * @param IEntityModel $entityModel
     */
    public function save(IEntityModel $entityModel)
    {
        $this->entityManager->persist($entityModel->getEntity());
        $this->entityManager->flush($entityModel->getEntity());
    }

    /**
     * @param IEntityModel $entityModel
     */
    public function remove(IEntityModel $entityModel)
    {
        $this->entityManager->remove($entityModel->getEntity());
    }

    /**
     * Find active dashboard or default dashboard
     *
     * @param User $user
     * @return DashboardModel|null
     */
    public function findUserActiveOrDefaultDashboard(User $user)
    {
        $activeDashboard = $this->findUserActiveDashboard($user);
        return $activeDashboard ? $activeDashboard : $this->findDefaultDashboard();
    }

    /**
     * Find active dashboard
     *
     * @param User $user
     * @return DashboardModel|null
     */
    public function findUserActiveDashboard(User $user)
    {
        $dashboard = $this->entityManager->getRepository('OroDashboardBundle:ActiveDashboard')
            ->findOneBy(array('user' => $user));

        if ($dashboard) {
            return $this->getDashboardModel($dashboard->getDashboard());
        }

        return null;
    }

    /**
     * Find default dashboard
     *
     * @return DashboardModel|null
     */
    public function findDefaultDashboard()
    {
        $dashboard = $this->entityManager->getRepository('OroDashboardBundle:Dashboard')
            ->findDefaultDashboard();

        if ($dashboard) {
            return $this->getDashboardModel($dashboard);
        }

        return null;
    }

    /**
     * @param string $permission
     * @return DashboardModel[]
     */
    public function findAllowedDashboards($permission = 'VIEW')
    {
        $qb = $this->entityManager->getRepository('OroDashboardBundle:Dashboard')->createQueryBuilder('dashboard');
        return $this->getDashboardModels($this->aclHelper->apply($qb, $permission)->execute());
    }

    /**
     * Set current dashboard as active for passed user
     *
     * @param DashboardModel $dashboard
     * @param User $user
     * @return bool
     */
    public function setUserActiveDashboard(DashboardModel $dashboard, User $user)
    {
        $activeDashboard = $this->entityManager
            ->getRepository('OroDashboardBundle:ActiveDashboard')
            ->findOneBy(array('user' => $user));

        if (!$activeDashboard) {
            $activeDashboard = new ActiveDashboard();
            $activeDashboard->setUser($user);

            $this->entityManager->persist($activeDashboard);
        }

        $activeDashboard->setDashboard($dashboard->getEntity());

        $this->entityManager->flush();
    }
}
