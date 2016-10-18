<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProvider;

class ContainerListener
{
    protected $confProvider;

    protected $dumper;

    /**
     * ContainerListener constructor.
     *
     * @param ConfigurationProvider $provider
     * @param ConfigMetadataDumperInterface $dumper
     */
    public function __construct(ConfigurationProvider $provider, ConfigMetadataDumperInterface $dumper)
    {
        $this->confProvider = $provider;
        $this->dumper       = $dumper;
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
            // Reload datagrid config to worm-up the cache
            $this->confProvider->loadConfiguration($temporaryConfigContainer);
            $this->dumper->dump($temporaryConfigContainer);
        }
    }
}
