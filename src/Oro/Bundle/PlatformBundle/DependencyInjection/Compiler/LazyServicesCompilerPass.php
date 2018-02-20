<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Marks all services definer in "Resources/config/oro/lazy_services.yml" as lazy.
 */
class LazyServicesCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_lazy_services',
            new YamlCumulativeFileLoader('Resources/config/oro/lazy_services.yml')
        );

        $lazyServices = array();
        foreach ($configLoader->load($container) as $resource) {
            if (!empty($resource->data['lazy_services']) && is_array($resource->data['lazy_services'])) {
                $lazyServices = array_merge($lazyServices, $resource->data['lazy_services']);
            }
        }

        foreach ($lazyServices as $serviceId) {
            if ($container->hasDefinition($serviceId)) {
                $container->getDefinition($serviceId)->setLazy(true);
            }
        }
    }
}
