<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all custom data-type completers.
 */
class CustomDataTypeCompleterCompilerPass implements CompilerPassInterface
{
    private const COMPLETER_HELPER_SERVICE_ID = 'oro_api.complete_definition_helper.custom_data_type';
    private const COMPLETER_TAG               = 'oro.api.custom_data_type_completer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = [];
        $completers = [];
        $taggedServices = $container->findTaggedServiceIds(self::COMPLETER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $services[$id] = new Reference($id);
            foreach ($attributes as $tagAttributes) {
                $completers[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }
        if ($completers) {
            $completers = DependencyInjectionUtil::sortByPriorityAndFlatten($completers);
        }

        $container->findDefinition(self::COMPLETER_HELPER_SERVICE_ID)
            ->replaceArgument(0, $completers)
            ->replaceArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
