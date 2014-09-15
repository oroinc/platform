<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Config\Resolver\ResolverInterface;

class WidgetAttributes
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ResolverInterface */
    protected $resolver;

    /**
     * @param ConfigProvider    $configProvider
     * @param SecurityFacade    $securityFacade
     * @param ResolverInterface $resolver
     */
    public function __construct(
        ConfigProvider $configProvider,
        SecurityFacade $securityFacade,
        ResolverInterface $resolver
    ) {
        $this->configProvider = $configProvider;
        $this->securityFacade = $securityFacade;
        $this->resolver       = $resolver;
    }

    /**
     * Returns widget attributes with attribute name converted to use in widget's TWIG template
     *
     * @param string $widgetName The name of widget
     *
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
     *
     * @return array
     */
    public function getWidgetItems($widgetName)
    {
        $widgetConfig = $this->configProvider->getWidgetConfig($widgetName);

        $securityFacade = $this->securityFacade;
        $resolver       = $this->resolver;

        $items = isset($widgetConfig['items']) ? $widgetConfig['items'] : [];
        $items = array_filter(
            $items,
            function (&$item) use ($securityFacade, $resolver) {
                $accessGranted = !isset($item['acl']) || $securityFacade->isGranted($item['acl']);
                $applicable    = true;
                if (isset($item['applicable'])) {
                    $resolved   = $resolver->resolve([$item['applicable']]);
                    $applicable = reset($resolved);
                }

                unset ($item['acl'], $item['applicable']);

                return $accessGranted && $applicable;
            }
        );

        return $items;
    }
}
