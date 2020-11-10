<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Marks all services defined in "Resources/config/oro/lazy_services.yml" as lazy.
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
        $resources = $configLoader->load(new ContainerBuilderAdapter($container));
        foreach ($resources as $resource) {
            if (empty($resource->data['lazy_services']) || !is_array($resource->data['lazy_services'])) {
                continue;
            }
            foreach ($resource->data['lazy_services'] as $serviceId) {
                if ($container->hasDefinition($serviceId)) {
                    $container->getDefinition($serviceId)->setLazy(true);
                } else {
                    $container->log(
                        $this,
                        sprintf('The service "%s" cannot be marked as lazy due to it does not exist.', $serviceId)
                    );
                }
            }
        }
    }
}
