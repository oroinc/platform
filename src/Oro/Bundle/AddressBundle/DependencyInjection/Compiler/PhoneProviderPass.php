<?php

namespace Oro\Bundle\AddressBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PhoneProviderPass implements CompilerPassInterface
{
    const SERVICE_KEY = 'oro_address.provider.phone';
    const TAG = 'oro_address.phone_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_KEY)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $id => $attributes) {
            if (empty($attributes[0]['class'])) {
                throw new InvalidConfigurationException(
                    sprintf('Tag attribute "class" is required for "%s" service', $id)
                );
            }
            $priority               = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $providers[$priority][] = [$attributes[0]['class'], new Reference($id)];
        }
        if (empty($providers)) {
            return;
        }

        // sort by priority and flatten
        ksort($providers);
        $providers = call_user_func_array('array_merge', $providers);

        // register
        $serviceDef = $container->getDefinition(self::SERVICE_KEY);
        foreach ($providers as $provider) {
            $serviceDef->addMethodCall('addPhoneProvider', $provider);
        }
    }
}
