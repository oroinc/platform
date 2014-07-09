<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

use Oro\Bundle\IntegrationBundle\Exception\LogicException;

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

        $this->processChannelTypes($manager, $container);
        $this->processTransportTypes($manager, $container);
        $this->processConnectorTypes($manager, $container);
    }

    /**
     * Pass integration types to a manager
     *
     * @param Definition       $managerDefinition
     * @param ContainerBuilder $container
     *
     * @throws LogicException
     */
    protected function processChannelTypes(Definition $managerDefinition, ContainerBuilder $container)
    {
        $integrations = $container->findTaggedServiceIds(self::CHANNEL_TYPES_TAG_NAME);

        foreach ($integrations as $serviceId => $tags) {
            $ref = new Reference($serviceId);
            foreach ($tags as $tagAttrs) {
                if (!isset($tagAttrs['type'])) {
                    throw new LogicException(sprintf('Could not retrieve type attribute for "%s"', $serviceId));
                }

                $managerDefinition->addMethodCall('addChannelType', [$tagAttrs['type'], $ref]);
            }
        }
    }

    /**
     * Pass transport types to a manager
     *
     * @param Definition       $managerDefinition
     * @param ContainerBuilder $container
     *
     * @throws LogicException
     */
    protected function processTransportTypes(Definition $managerDefinition, ContainerBuilder $container)
    {
        $transports = $container->findTaggedServiceIds(self::TRANSPORT_TYPES_TAG_NAME);

        foreach ($transports as $serviceId => $tags) {
            $ref = new Reference($serviceId);
            foreach ($tags as $tagAttrs) {
                if (!isset($tagAttrs['type'])) {
                    throw new LogicException(sprintf('Could not retrieve "type" attribute for "%s"', $serviceId));
                } elseif (!isset($tagAttrs['channel_type'])) {
                    throw new LogicException(
                        sprintf(
                            'Could not retrieve "channel_type" attribute for "%s"',
                            $serviceId
                        )
                    );
                }

                $managerDefinition->addMethodCall(
                    'addTransportType',
                    [$tagAttrs['type'], $tagAttrs['channel_type'], $ref]
                );
            }
        }
    }

    /**
     * Pass connector types to manager
     *
     * @param Definition       $managerDefinition
     * @param ContainerBuilder $container
     *
     * @throws LogicException
     */
    protected function processConnectorTypes(Definition $managerDefinition, ContainerBuilder $container)
    {
        $connectors = $container->findTaggedServiceIds(self::CONNECTOR_TYPES_TAG_NAME);

        foreach ($connectors as $serviceId => $tags) {
            $ref = new Reference($serviceId);
            foreach ($tags as $tagAttrs) {
                if (!isset($tagAttrs['type'])) {
                    throw new LogicException(sprintf('Could not retrieve "type" attribute for "%s"', $serviceId));
                } elseif (!isset($tagAttrs['channel_type'])) {
                    throw new LogicException(
                        sprintf(
                            'Could not retrieve "channel_type" attribute for "%s"',
                            $serviceId
                        )
                    );
                }

                $managerDefinition->addMethodCall(
                    'addConnectorType',
                    [$tagAttrs['type'], $tagAttrs['channel_type'], $ref]
                );
            }
        }
    }
}
