<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\IntegrationBundle\Exception\LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers synchronization processors with the processor registry during dependency injection compilation.
 *
 * This compiler pass collects all services tagged with `oro_integration.sync_processor` and
 * registers them with the sync processor registry. Each processor is associated with an integration
 * type, allowing the system to route synchronization requests to the appropriate processor based
 * on the integration type being synchronized.
 */
class ProcessorsPass implements CompilerPassInterface
{
    public const SYNC_PROCESSOR_TAG = 'oro_integration.sync_processor';
    public const SYNC_PROCESSOR_REGISTRY = 'oro_integration.processor_registry';
    public const INTEGRATION_NAME = 'integration';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $syncProcessors = $container->findTaggedServiceIds(self::SYNC_PROCESSOR_TAG);
        $processorRegistry = $container->getDefinition(self::SYNC_PROCESSOR_REGISTRY);

        foreach ($syncProcessors as $serviceId => $tags) {
            $ref = new Reference($serviceId);
            foreach ($tags as $tagAttrs) {
                if (!isset($tagAttrs[self::INTEGRATION_NAME])) {
                    throw new LogicException(sprintf('Could not retrieve type attribute for "%s"', $serviceId));
                }

                $processorRegistry->addMethodCall('addProcessor', [$tagAttrs[self::INTEGRATION_NAME], $ref]);
            }
        }
    }
}
