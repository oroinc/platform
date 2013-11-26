<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class TypesPass implements CompilerPassInterface
{
    const MANAGER_ID               = 'oro_integration.manager.types_registry';
    const CHANNEL_TYPES_TAG_NAME   = 'oro_integration.channel';
    const TRANSPORT_TYPES_TAG_NAME = 'oro_integration.transport';
    const CONNECTOR_TYPES_TAG_NAME = 'oro_integration.connector';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $manager = $container->getDefinition(self::MANAGER_ID);
        if ($manager) {
            $channels = $container->findTaggedServiceIds(self::CHANNEL_TYPES_TAG_NAME);
            foreach ($channels as $serviceId => $tags) {
                $ref = new Reference($serviceId);
                foreach ($tags as $tagAttrs) {
                    if (!isset($tagAttrs['type'])) {
                        throw new \LogicException(sprintf('Could not retrieve type attribute for "%s"', $serviceId));
                    }
                    $manager->addMethodCall('addChannelType', [$tagAttrs['type'], $ref]);
                }
            }

            $transports = $container->findTaggedServiceIds(self::TRANSPORT_TYPES_TAG_NAME);
            foreach ($transports as $serviceId => $tags) {
                $ref = new Reference($serviceId);
                foreach ($tags as $tagAttrs) {
                    if (!isset($tagAttrs['type'])) {
                        throw new \LogicException(sprintf('Could not retrieve "type" attribute for "%s"', $serviceId));
                    } elseif (!isset($tagAttrs['channel_type'])) {
                        throw new \LogicException(
                            sprintf(
                                'Could not retrieve "channel_type" attribute for "%s"',
                                $serviceId
                            )
                        );
                    }
                    $manager->addMethodCall(
                        'addTransportType',
                        [$tagAttrs['type'], $tagAttrs['channel_type'], $ref]
                    );
                }
            }

            $connectors = $container->findTaggedServiceIds(self::CONNECTOR_TYPES_TAG_NAME);
            foreach ($connectors as $serviceId => $tags) {
                $ref = new Reference($serviceId);
                foreach ($tags as $tagAttrs) {
                    if (!isset($tagAttrs['type'])) {
                        throw new \LogicException(sprintf('Could not retrieve "type" attribute for "%s"', $serviceId));
                    } elseif (!isset($tagAttrs['channel_type'])) {
                        throw new \LogicException(
                            sprintf(
                                'Could not retrieve "channel_type" attribute for "%s"',
                                $serviceId
                            )
                        );
                    }
                    $manager->addMethodCall(
                        'addConnectorType',
                        [$tagAttrs['type'], $tagAttrs['channel_type'], $ref]
                    );
                }
            }
        }
    }
}
