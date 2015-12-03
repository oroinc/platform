<?php

namespace Oro\Bundle\SidebarBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Asset\Packages as AssetHelper;

use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;

class RequestHandler
{
    /** @var WidgetDefinitionRegistry */
    private $widgetDefinitionsRegistry = false;

    /** @var AssetHelper */
    private $assetHelper = false;

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
        $definitions = $this->getWidgetDefinitionsRegistry()->getWidgetDefinitions();

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

        $this->getWidgetDefinitionsRegistry()->setWidgetDefinitions($definitions);
    }

    /**
     * @return WidgetDefinitionRegistry
     */
    protected function getWidgetDefinitionsRegistry()
    {
        if ($this->widgetDefinitionsRegistry === false) {
            $this->widgetDefinitionsRegistry = $this->container->get('oro_sidebar.widget_definition.registry');
        }

        return $this->widgetDefinitionsRegistry;
    }

    /**
     * @return AssetHelper
     */
    protected function getAssetHelper()
    {
        if ($this->assetHelper === false) {
            $this->assetHelper = $this->container->get('assets.packages');
        }

        return $this->assetHelper;
    }
}
