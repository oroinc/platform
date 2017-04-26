<?php

namespace Oro\Bundle\SidebarBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Asset\Packages as AssetHelper;

use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;

class RequestHandler
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $widgetDefinitionsRegistry = $this->getWidgetDefinitionsRegistry();
        $definitions = $widgetDefinitionsRegistry->getWidgetDefinitions();
        if ($definitions->isEmpty()) {
            return;
        }

        $definitions = $definitions->toArray();

        $assertHelper = $this->getAssetHelper();
        foreach ($definitions as &$definition) {
            if (!empty($definition['icon'])) {
                $definition['icon'] = $assertHelper->getUrl($definition['icon']);
            }
        }

        $widgetDefinitionsRegistry->setWidgetDefinitions($definitions);
    }

    /**
     * @return WidgetDefinitionRegistry
     */
    protected function getWidgetDefinitionsRegistry()
    {
        return $this->container->get('oro_sidebar.widget_definition.registry');
    }

    /**
     * @return AssetHelper
     */
    protected function getAssetHelper()
    {
        return $this->container->get('assets.packages');
    }
}
