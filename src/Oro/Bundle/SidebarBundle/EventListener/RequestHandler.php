<?php

namespace Oro\Bundle\SidebarBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Asset\Packages as AssetHelper;

use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;

class RequestHandler
{
    /**
     * @var WidgetDefinitionRegistry
     */
    protected $widgetDefinitionsRegistry;

    /**
     * @var AssetHelper
     */
    protected $assetHelper;

    /**
     * @param WidgetDefinitionRegistry $widgetDefinitionsRegistry
     * @param AssetHelper $assetHelper
     */
    public function __construct(WidgetDefinitionRegistry $widgetDefinitionsRegistry, AssetHelper $assetHelper)
    {
        $this->widgetDefinitionsRegistry = $widgetDefinitionsRegistry;
        $this->assetHelper = $assetHelper;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $definitions = $this->widgetDefinitionsRegistry->getWidgetDefinitions();

        if ($definitions->isEmpty()) {
            return;
        }

        $definitions = $definitions->toArray();

        foreach ($definitions as &$definition) {
            if (!empty($definition['icon'])) {
                $definition['icon'] = $this->assetHelper->getUrl($definition['icon']);
            }
        }

        $this->widgetDefinitionsRegistry->setWidgetDefinitions($definitions);
    }
}
