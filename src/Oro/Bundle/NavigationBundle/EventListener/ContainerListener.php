<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Bundle\NavigationBundle\Provider\ConfigurationProvider;

use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;

class ContainerListener
{
    /** @var ConfigurationProvider */
    private $configurationProvider;

    /** @var ConfigMetadataDumperInterface */
    private $dumper;

    /**
     * @param ConfigurationProvider         $configurationProvider
     * @param ConfigMetadataDumperInterface $dumper
     */
    public function __construct(ConfigurationProvider $configurationProvider, ConfigMetadataDumperInterface $dumper)
    {
        $this->configurationProvider = $configurationProvider;
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

            // Reload navigation config to warm-up the cache
            $this->configurationProvider->loadConfiguration($container);
            $this->dumper->dump($container);
        }
    }
}
