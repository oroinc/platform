<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository;
use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\DashboardBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class Manager
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var DashboardRepository
     */
    protected $dashboardRepository;

    /**
     * @var DashboardModelFactory
     */
    protected $dashboardModelFactory;

    /**
     * Constructor
     *
     * @param ConfigProvider        $configProvider
     * @param SecurityFacade        $securityFacade
     * @param DashboardRepository   $dashboardRepository
     * @param DashboardModelFactory $dashboardModelFactory
     */
    public function __construct(
        ConfigProvider $configProvider,
        SecurityFacade $securityFacade,
        DashboardRepository $dashboardRepository,
        DashboardModelFactory $dashboardModelFactory
    ) {
        $this->securityFacade = $securityFacade;
        $this->dashboardRepository = $dashboardRepository;
        $this->configProvider = $configProvider;
        $this->dashboardModelFactory = $dashboardModelFactory;
    }

    /**
     * Returns name of default dashboard
     *
     * @param DashboardModel[] $availableDashboards
     * @throws InvalidArgumentException
     * @return string
     */
    public function findDefaultDashboard(array $availableDashboards)
    {
        $name = $this->configProvider->getConfig('default_dashboard');
        foreach ($availableDashboards as $dashboard) {
            if ($dashboard->getDashboard()->getName() == $name) {
                return $dashboard;
            }
        }

        throw new InvalidArgumentException();
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
     * Returns widget attributes with attribute name converted to use in widget's TWIG template
     *
     * @param string $widgetName The name of widget
     * @return array
     */
    public function getWidgetAttributesForTwig($widgetName)
    {
        $result = [
            'widgetName' => $widgetName
        ];

        $widget = $this->configProvider->getWidgetConfig($widgetName);
        unset($widget['route']);
        unset($widget['route_parameters']);
        unset($widget['acl']);
        unset($widget['items']);

        foreach ($widget as $key => $val) {
            $attrName = 'widget';
            foreach (explode('_', str_replace('-', '_', $key)) as $keyPart) {
                $attrName .= ucfirst($keyPart);
            }
            $result[$attrName] = $val;
        }

        return $result;
    }

    /**
     * Returns a list of items for the given widget
     *
     * @param string $widgetName The name of widget
     * @return array
     */
    public function getWidgetItems($widgetName)
    {
        $widgetConfig = $this->configProvider->getWidgetConfig($widgetName);

        $items = isset($widgetConfig['items']) ? $widgetConfig['items'] : [];

        foreach ($items as $itemName => &$item) {
            if (!isset($item['acl']) || $this->securityFacade->isGranted($item['acl'])) {
                unset($item['acl']);
            } else {
                unset($items[$itemName]);
            }
        }

        return $items;
    }
}
