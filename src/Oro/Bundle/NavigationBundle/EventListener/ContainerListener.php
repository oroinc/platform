<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Bundle\NavigationBundle\Provider\ConfigurationProvider;

use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;

class ContainerListener
{
    /** @var ConfigurationProvider */
    private $confProvider;

    /** @var ConfigMetadataDumperInterface */
    private $dumper;

    /**
     * @param ConfigurationProvider         $provider
     * @param ConfigMetadataDumperInterface $dumper
     */
    public function __construct(ConfigurationProvider $provider, ConfigMetadataDumperInterface $dumper)
    {
        $this->confProvider = $provider;
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
            $temporaryConfigContainer = new ContainerBuilder();

            // Reload navigation config to warm-up the cache
            $this->confProvider->loadConfiguration($temporaryConfigContainer);
            $this->dumper->dump($temporaryConfigContainer);
        }
    }
}
