<?php

namespace Oro\Bundle\CalendarBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CalendarProviderPass implements CompilerPassInterface
{
    const MANAGER_ID = 'oro_calendar.calendar_manager';
    const TAG_NAME   = 'oro_calendar.calendar_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::MANAGER_ID)) {
            return;
        }

        // find providers
        $providers      = [];
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            if (empty($attributes[0]['alias'])) {
                throw new InvalidConfigurationException(
                    sprintf('Tag attribute "alias" is required for "%s" service', $id)
                );
            }
            $priority               = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $providers[$priority][] = [$attributes[0]['alias'], new Reference($id)];
        }
        if (empty($providers)) {
            return;
        }

        // sort by priority and flatten
        ksort($providers);
        $providers = call_user_func_array('array_merge', $providers);

        // register
        $serviceDef = $container->getDefinition(self::MANAGER_ID);
        foreach ($providers as $provider) {
            $serviceDef->addMethodCall('addProvider', $provider);
        }
    }
}
