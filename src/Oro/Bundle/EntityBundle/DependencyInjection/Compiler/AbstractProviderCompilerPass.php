<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

abstract class AbstractProviderCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $chainDefinition = $container->getDefinition($this->getService());
        $taggedServiceIds = $container->findTaggedServiceIds($this->getTag());

        foreach ($taggedServiceIds as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $chainDefinition->addMethodCall(
                    'addProvider',
                    [
                        new Reference($serviceId),
                        $tag['priority']
                    ]
                );
            }
        }
    }

    /**
     * @return string
     */
    abstract protected function getTag();

    /**
     * @return string
     */
    abstract protected function getService();
}
