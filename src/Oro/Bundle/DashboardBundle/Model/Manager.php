<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\DashboardBundle\Entity\ActiveDashboard;
use Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository;
use Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\DashboardBundle\Provider\ConfigProvider;
use Oro\Bundle\UserBundle\Entity\User;

class Manager
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var DashboardRepository
     */
    protected $dashboardRepository;

    /**
     * @var DashboardModelFactory
     */
    protected $dashboardModelFactory;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Constructor
     *
     * @param ConfigProvider              $configProvider
     * @param DashboardRepository         $dashboardRepository
     * @param DashboardModelFactory       $dashboardModelFactory
     * @param EntityManager $entityManager
     */
    public function __construct(
        ConfigProvider $configProvider,
        DashboardRepository $dashboardRepository,
        DashboardModelFactory $dashboardModelFactory,
        EntityManager $entityManager
    ) {
        $this->dashboardRepository = $dashboardRepository;
        $this->configProvider = $configProvider;
        $this->dashboardModelFactory = $dashboardModelFactory;
        $this->entityManager = $entityManager;
    }

    /**
     * Returns all dashboards
     *
     * @throws InvalidConfigurationException
     * @return DashboardModel[]
     */
    public function getDashboards()
    {
        $result = [];
        foreach ($this->dashboardRepository->getAvailableDashboards() as $dashboard) {
            $result[] = $this->dashboardModelFactory->getDashboardModel($dashboard);
        }

        return $result;
    }

    /**
     * @param User $user
     * @param int  $dashboardId
     * @return bool
     */
    public function setUserActiveDashboard(User $user, $dashboardId)
    {
        $dashboard = $this->dashboardRepository->find($dashboardId);

        if (!$dashboard) {
            return false;
        }

        $activeDashboard = $this->entityManager->getRepository('OroDashboardBundle:ActiveDashboard')
            ->findOneBy(array('userId' => $user->getId()));

        if (!$activeDashboard) {
            $activeDashboard = new ActiveDashboard();
            $activeDashboard->setUser($user);
        }

        $activeDashboard->setDashboard($dashboard);

        $this->entityManager->persist($activeDashboard);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @param User $user
     * @return DashboardModel
     */
    public function getUserDashboard(User $user)
    {
        $activeDashboard = $this->entityManager->getRepository('OroDashboardBundle:ActiveDashboard')
            ->findOneBy(array('userId' => $user->getId()));

        if (!$activeDashboard) {
            $name = $this->configProvider->getConfig('default_dashboard');
            $dashboard = $this->dashboardRepository->findOneBy(array('name' => $name));
            return $this->dashboardModelFactory->getDashboardModel($dashboard);
        }

        return $this->dashboardModelFactory->getDashboardModel($activeDashboard->getDashboard());
    }
}
