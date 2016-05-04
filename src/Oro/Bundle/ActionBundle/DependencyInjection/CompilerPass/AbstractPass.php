<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;

abstract class AbstractPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @param string $serviceId
     * @param string $tag
     */
    public function processTypes(ContainerBuilder $container, $serviceId, $tag)
    {
        if (!$container->hasDefinition($serviceId)) {
            return;
        }

        $types = [];
        $conditions = $container->findTaggedServiceIds($tag);

        foreach ($conditions as $id => $attributes) {
            $this->prepareDefinition($container->getDefinition($id));

            foreach ($attributes as $eachTag) {
                $aliases = empty($eachTag['alias']) ? [$id] : explode('|', $eachTag['alias']);

                foreach ($aliases as $alias) {
                    $types[$alias] = $id;
                }
            }
        }

        $extensionDef = $container->getDefinition($serviceId);
        $extensionDef->replaceArgument(1, $types);
    }

    /**
     * @param Definition $definition
     */
    protected function prepareDefinition(Definition $definition)
    {
        $definition->setScope(ContainerInterface::SCOPE_PROTOTYPE)->setPublic(false);
    }
}
