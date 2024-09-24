<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CustomGridFieldValidatorCompilerPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $chainDefinition = $container->getDefinition($this->getService());
        $taggedServiceIds = $container->findTaggedServiceIds($this->getTag());

        foreach ($taggedServiceIds as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $chainDefinition->addMethodCall(
                    'addValidator',
                    [
                        new Reference($serviceId),
                        $tag['entity_name']
                    ]
                );
            }
        }
    }

    protected function getTag()
    {
        return 'oro_entity.custom_grid_field_validator';
    }

    protected function getService()
    {
        return 'oro_entity.manager.entity_field_validator';
    }
}
