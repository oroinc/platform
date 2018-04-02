<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Provider\ConfigurationProvider;
use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
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
            $this->getConfigurationProvider()->loadConfiguration($container);
            $this->dumper->dump($container);
        }
    }

    /**
     * @return ConfigurationProvider
     */
    private function getConfigurationProvider()
    {
        return $this->container->get('oro_datagrid.configuration.provider');
    }
}
