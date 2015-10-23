<?php

namespace Oro\Bundle\DashboardBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class BigNumberProviderPass implements CompilerPassInterface
{
    const TAG                  = 'oro_dashboard.big_number.provider';
    const PROCESSOR_SERVICE_ID = 'oro_dashboard.provider.big_number.processor';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(static::PROCESSOR_SERVICE_ID)) {
            return;
        }

        $definition = $container->getDefinition(static::PROCESSOR_SERVICE_ID);

        $taggedServices = $container->findTaggedServiceIds(self::TAG);

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes['alias'])) {
                    throw new InvalidConfigurationException(
                        sprintf('Tag attribute "alias" is required for "%s" service', $id)
                    );
                }

                $definition->addMethodCall(
                    'addValueProvider',
                    [new Reference($id), $attributes['alias']]
                );
            }
        }
    }
}
