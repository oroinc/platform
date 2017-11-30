<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ImportExportConfigurationRegistryCompilerPass implements CompilerPassInterface
{
    /**
     * @internal
     */
    const REGISTRY_SERVICE = 'oro_importexport.configuration.registry';

    /**
     * @internal
     */
    const SERVICE_TAG = 'oro_importexport.configuration';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::REGISTRY_SERVICE)) {
            return;
        }

        $definition = $container->findDefinition(self::REGISTRY_SERVICE);
        $services = $container->findTaggedServiceIds(self::SERVICE_TAG);

        foreach ($services as $id => $tags) {
            foreach ($tags as $tag) {
                $definition->addMethodCall('addConfiguration', [
                    new Reference($id),
                    $tag['alias']
                ]);
            }
        }
    }
}
