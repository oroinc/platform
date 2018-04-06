<?php

namespace Oro\Bundle\FilterBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FilterTypesPass implements CompilerPassInterface
{
    const FILTER_EXTENSION_ID = 'oro_filter.extension.orm_filter';
    const TAG_NAME            = 'oro_filter.extension.orm_filter.filter';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        /**
         * Find and add available filters to extension
         */
        $extension = $container->getDefinition(self::FILTER_EXTENSION_ID);
        if ($extension) {
            $filters = $container->findTaggedServiceIds(self::TAG_NAME);
            foreach ($filters as $serviceId => $tags) {
                $tagAttrs = reset($tags);
                if (isset($tagAttrs['datasource']) && 'orm' !== $tagAttrs['datasource']) {
                    continue;
                }
                if ($container->hasDefinition($serviceId)) {
                    $container->getDefinition($serviceId)->setPublic(false);
                }
                $extension->addMethodCall('addFilter', array($tagAttrs['type'], new Reference($serviceId)));
            }
        }
    }
}
