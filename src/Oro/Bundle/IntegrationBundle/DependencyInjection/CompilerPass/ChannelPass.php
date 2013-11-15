<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class ChannelPass implements CompilerPassInterface
{
    const MANAGER_ID = 'oro_integration.manager.channel_type';
    const TAG_NAME   = 'oro_integration.channel';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $manager = $container->getDefinition(self::MANAGER_ID);
        if ($manager) {
            $channels = [];

            $properties = $container->findTaggedServiceIds(self::TAG_NAME);
            foreach ($properties as $serviceId => $tags) {
                $tagAttrs = reset($tags);
                if (isset($channels[$tagAttrs['type']])) {
                    throw new \LogicException(sprintf('Could not redefine "%s" channel type', $tagAttrs['type']));
                }
                $channels[$tagAttrs['type']] = new Reference($serviceId);
            }

            $manager->replaceArgument(0, $channels);
        }
    }
}
