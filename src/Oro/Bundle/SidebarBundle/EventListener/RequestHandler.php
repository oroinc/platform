<?php

namespace Oro\Bundle\SidebarBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Templating\Asset\PackageInterface;

use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;

class RequestHandler
{
    /**
     * @var WidgetDefinitionRegistry
     */
    protected $widgetDefinitionsRegistry;

    /**
     * @var PackageInterface
     */
    protected $assetHelper;

    /**
     * @param WidgetDefinitionRegistry $widgetDefinitionsRegistry
     * @param PackageInterface $assetHelper
     */
    public function __construct(WidgetDefinitionRegistry $widgetDefinitionsRegistry, PackageInterface $assetHelper)
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
