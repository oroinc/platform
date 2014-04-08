<?php

namespace Oro\Bundle\DashboardBundle;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;
use Oro\Bundle\DashboardBundle\Model\DashboardsModelCollection;
use Oro\Bundle\DashboardBundle\Model\WidgetModelFactory;
use Oro\Bundle\DashboardBundle\Model\WidgetsModelCollection;
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
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var WidgetModelFactory
     */
    protected $widgetModelFactory;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * Constructor
     *
     * @param Provider\ConfigProvider                         $configProvider
     * @param SecurityFacade                                  $securityFacade
     * @param EntityManager                                   $entityManager
     * @param WidgetModelFactory                              $widgetModelFactory
     * @param AclHelper $aclHelper
     */
    public function __construct(
        ConfigProvider $configProvider,
        SecurityFacade $securityFacade,
        EntityManager $entityManager,
        WidgetModelFactory $widgetModelFactory,
        AclHelper $aclHelper
    ) {
        $this->securityFacade = $securityFacade;
        $this->entityManager = $entityManager;
        $this->widgetModelFactory = $widgetModelFactory;
        $this->configProvider = $configProvider;
        $this->aclHelper = $aclHelper;
    }

    /**
     * Returns name of default dashboard
     *
     * @return string
     */
    public function getDefaultDashboardName()
    {
        return $this->configProvider->getConfig('default_dashboard');
    }

    /**
     * Returns all dashboards
     *
     * @throws Exception\InvalidConfigurationException
     *
     * @return DashboardsModelCollection
     */
    public function getDashboards()
    {
        $result = [];
        $qb = $this->getDashboardRepository()->createQueryBuilder('d');
        $dashboards = $this->aclHelper->apply($qb)->execute();
        foreach ($dashboards as $dashboard) {
            $dashboardModel = $this->getDashboardModel($dashboard);
            if ($dashboardModel) {
                $result[] = $dashboardModel;
            }
        }

        return new DashboardsModelCollection($result);
    }

    /**
     * @param       $id
     * @param array $widgetState
     * @return bool is widget exist
     */
    public function saveWidget($id, array $widgetState)
    {
        $widget = $this->entityManager->getRepository('OroDashboardBundle:DashboardWidget')
            ->find($id);

        if ($widget == null || !$this->securityFacade->isGranted('EDIT', $widget)) {
            return false;
        }

        if (array_key_exists('position', $widgetState)) {
            $widget->setPosition((int)$widgetState['position']);
        }

        if (array_key_exists('expanded', $widgetState)) {
            $widget->setExpanded((bool)$widgetState['expanded']);
        }

        $this->entityManager->persist($widget);

        return true;
    }

    /**
     * Returns all widgets for the given dashboard
     *
     * @param Entity\Dashboard $dashboard
     *
     * @throws InvalidConfigurationException
     *
     * @return DashboardModel
     */
    public function getDashboardModel(Dashboard $dashboard)
    {
        if (!$this->securityFacade->isGranted('VIEW', $dashboard)) {
            return null;
        }

        $dashboardConfig = $this->configProvider->getDashboardConfigs($dashboard->getName());

        $widgetsCollection = new WidgetsModelCollection($dashboard, $this->widgetModelFactory);

        return new DashboardModel($widgetsCollection, $dashboardConfig, $dashboard);
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

        $widget = $this->configProvider->getWidgetConfigs($widgetName);
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
        $widgetConfig = $this->configProvider->getWidgetConfigs($widgetName);

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

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getDashboardRepository()
    {
        return $this->entityManager->getRepository('OroDashboardBundle:Dashboard');
    }
}
