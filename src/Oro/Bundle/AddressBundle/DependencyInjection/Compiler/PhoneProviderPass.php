<?php

namespace Oro\Bundle\AddressBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all phone providers.
 */
class PhoneProviderPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $tagName = 'oro_address.phone_provider';
        $providers = [];
        $services = [];
        $taggedServices = $container->findTaggedServiceIds($tagName);
        foreach ($taggedServices as $id => $tags) {
            $services[$id] = new Reference($id);
            foreach ($tags as $attributes) {
                $providers[$this->getPriorityAttribute($attributes)][] = [
                    $id,
                    $this->getRequiredAttribute($attributes, 'class', $id, $tagName)
                ];
            }
        }
        if ($providers) {
            $providers = $this->inverseSortByPriorityAndFlatten($providers);
        }

        $map = [];
        foreach ($providers as list($id, $class)) {
            $map[$class][] = $id;
        }

        $container->getDefinition('oro_address.provider.phone')
            ->setArgument(0, $map)
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
