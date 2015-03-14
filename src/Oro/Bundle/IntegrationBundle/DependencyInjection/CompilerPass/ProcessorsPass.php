<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\IntegrationBundle\Exception\LogicException;

class ProcessorsPass implements CompilerPassInterface
{
    const SYNC_PROCESSOR_TAG = 'oro_integration.sync_processor';
    const SYNC_PROCESSOR_REGISTRY = 'oro_integration.processor_registry';
    const INTEGRATION_NAME = 'integration';

    /**
     * {@inheritDoc}
     */
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
