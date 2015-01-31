<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ConfigurationPass implements CompilerPassInterface
{
    const RENDERER_REGISTRY_SERVICE = 'oro_layout.layout_renderer_registry';
    const PHP_RENDERER_SERVICE = 'oro_layout.php.layout_renderer';
    const TWIG_RENDERER_SERVICE = 'oro_layout.twig.layout_renderer';
    const BLOCK_TYPE_FACTORY_SERVICE = 'oro_layout.block_type_factory';
    const BLOCK_TYPE_TAG_NAME = 'layout.block_type';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // register renderers
        if ($container->hasDefinition(self::RENDERER_REGISTRY_SERVICE)) {
            $registryDef = $container->getDefinition(self::RENDERER_REGISTRY_SERVICE);
            if ($container->hasDefinition(self::PHP_RENDERER_SERVICE)) {
                $registryDef->addMethodCall(
                    'addRenderer',
                    ['php', new Reference(self::PHP_RENDERER_SERVICE)]
                );
            }
            if ($container->hasDefinition(self::TWIG_RENDERER_SERVICE)) {
                $registryDef->addMethodCall(
                    'addRenderer',
                    ['twig', new Reference(self::TWIG_RENDERER_SERVICE)]
                );
            }
        }
        // register block types
        if ($container->hasDefinition(self::BLOCK_TYPE_FACTORY_SERVICE)) {
            $types = array();
            foreach ($container->findTaggedServiceIds(self::BLOCK_TYPE_TAG_NAME) as $serviceId => $tag) {
                $alias = isset($tag[0]['alias'])
                    ? $tag[0]['alias']
                    : $serviceId;

                $types[$alias] = $serviceId;
            }

            $factoryDef = $container->getDefinition(self::BLOCK_TYPE_FACTORY_SERVICE);
            $factoryDef->replaceArgument(1, $types);
        }
    }
}
