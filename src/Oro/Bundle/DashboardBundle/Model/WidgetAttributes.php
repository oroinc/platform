<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class WidgetAttributes
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
     * @param ConfigProvider $configProvider
     * @param SecurityFacade $securityFacade
     */
    public function __construct(ConfigProvider $configProvider, SecurityFacade $securityFacade)
    {
        $this->configProvider = $configProvider;
        $this->securityFacade = $securityFacade;
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
