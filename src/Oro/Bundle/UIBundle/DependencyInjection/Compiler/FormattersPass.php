<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FormattersPass implements CompilerPassInterface
{
    const FORMATTER_MANAGER_SERVICE_KEY = 'oro_ui.formatter';
    const FORMATTER_TAG                 = 'oro_formatter';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        // find formatters
        $formatters     = [];
        $taggedServices = $container->findTaggedServiceIds(self::FORMATTER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            if ($container->hasDefinition($id)) {
                $container->getDefinition($id)->setPublic(false);
            }
            $formatters[$attributes[0]['formatter']] = new Reference($id);
        }
        if (empty($formatters)) {
            return;
        }
        // register
        $serviceDef = $container->getDefinition(self::FORMATTER_MANAGER_SERVICE_KEY);
        foreach ($formatters as $formatterName => $formatter) {
            $serviceDef->addMethodCall('addFormatter', [$formatterName, $formatter]);
        }
    }
}
