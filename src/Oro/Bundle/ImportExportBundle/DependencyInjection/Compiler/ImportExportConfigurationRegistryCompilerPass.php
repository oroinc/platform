<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that registers import/export configuration providers with the registry.
 *
 * This pass collects all services tagged with `oro_importexport.configuration`,
 * extracts their alias from the tag, and registers them with the configuration registry
 * service. This allows the system to discover and aggregate configurations from multiple
 * providers during the dependency injection compilation phase.
 */
class ImportExportConfigurationRegistryCompilerPass implements CompilerPassInterface
{
    /**
     * @internal
     */
    public const REGISTRY_SERVICE = 'oro_importexport.configuration.registry';

    /**
     * @internal
     */
    public const SERVICE_TAG = 'oro_importexport.configuration';

    #[\Override]
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
