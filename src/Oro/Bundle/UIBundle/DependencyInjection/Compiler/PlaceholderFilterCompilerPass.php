<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class PlaceholderFilterCompilerPass implements CompilerPassInterface
{
    const PLACEHOLDER_FILTER_TAG = 'oro_ui_placeholder.filter';
    const PROVIDER_SERVICE = 'oro_ui.placeholder.provider';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $providerDefinition = $container->getDefinition(self::PROVIDER_SERVICE);
        $stepArguments = array();
        foreach ($container->findTaggedServiceIds(self::PLACEHOLDER_FILTER_TAG) as $id => $attributes) {
            $stepArguments[] = new Reference($id);
        }
        $providerDefinition->replaceArgument(1, $stepArguments);
    }
}
