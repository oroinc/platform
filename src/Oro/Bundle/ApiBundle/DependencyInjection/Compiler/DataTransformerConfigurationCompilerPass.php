<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

class DataTransformerConfigurationCompilerPass implements CompilerPassInterface
{
    const DATA_TRANSFORMER_SERVICE_ID = 'oro_api.data_transformer_registry';
    const DATA_TRANSFORMER_TAG        = 'oro.api.data_transformer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $chainServiceDef = DependencyInjectionUtil::findDefinition($container, self::DATA_TRANSFORMER_SERVICE_ID);
        if (null === $chainServiceDef) {
            return;
        }

        // find services
        $services = [];
        $taggedServices = $container->findTaggedServiceIds(self::DATA_TRANSFORMER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $dataType = $attributes[0]['dataType'];
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $services[$priority][] = [$dataType, new Reference($id)];
        }
        if (empty($services)) {
            return;
        }

        // sort by priority and flatten
        krsort($services);
        $services = call_user_func_array('array_merge', $services);

        // register
        foreach ($services as $serviceInfo) {
            $chainServiceDef->addMethodCall('addDataTransformer', $serviceInfo);
        }
    }
}
