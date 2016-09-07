<?php

namespace Oro\Bundle\LayoutBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface;

class ContainerListener
{
    /** @var ResourceProviderInterface */
    protected $provider;

    /** @var ConfigMetadataDumperInterface */
    protected $dumper;

    /**
     * @param ResourceProviderInterface $provider
     * @param ConfigMetadataDumperInterface $dumper
     */
    public function __construct(ResourceProviderInterface $provider, ConfigMetadataDumperInterface $dumper)
    {
        $this->provider = $provider;
        $this->dumper = $dumper;
    }

    /**
     * Executes event on kernel request
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->dumper->isFresh()) {
            $container = new ContainerBuilder();

            $this->provider->loadResources($container);
            $this->dumper->dump($container);
        }
    }
}
