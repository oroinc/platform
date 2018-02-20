<?php

namespace Oro\Bundle\LayoutBundle\EventListener;

use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ContainerListener
{
    /** @var ConfigMetadataDumperInterface */
    private $dumper;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param ConfigMetadataDumperInterface $dumper
     * @param ContainerInterface            $container
     */
    public function __construct(ConfigMetadataDumperInterface $dumper, ContainerInterface $container)
    {
        $this->dumper = $dumper;
        $this->container = $container;
    }

    /**
     * Executes event on kernel request
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest() && !$this->dumper->isFresh()) {
            $container = new ContainerBuilder();
            $this->getResourceProvider()->loadResources($container);
            $this->dumper->dump($container);
        }
    }

    /**
     * @return ResourceProviderInterface
     */
    private function getResourceProvider()
    {
        return $this->container->get('oro_layout.theme_extension.resource_provider.theme');
    }
}
