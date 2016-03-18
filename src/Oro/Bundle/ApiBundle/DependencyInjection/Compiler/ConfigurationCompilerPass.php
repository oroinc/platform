<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ApiBundle\DependencyInjection\Configuration;

class ConfigurationCompilerPass implements CompilerPassInterface
{
    const PROCESSOR_BAG_SERVICE_ID          = 'oro_api.processor_bag';
    const ACTION_PROCESSOR_BAG_SERVICE_ID   = 'oro_api.action_processor_bag';
    const ACTION_PROCESSOR_TAG              = 'oro.api.action_processor';
    const FILTER_FACTORY_SERVICE_ID         = 'oro_api.filter_factory';
    const FILTER_FACTORY_TAG                = 'oro.api.filter_factory';
    const EXCLUSION_PROVIDER_SERVICE_ID     = 'oro_api.entity_exclusion_provider';
    const EXCLUSION_PROVIDER_TAG            = 'oro_entity.exclusion_provider.api';
    const VIRTUAL_FIELD_PROVIDER_SERVICE_ID = 'oro_api.virtual_field_provider';
    const VIRTUAL_FIELD_PROVIDER_TAG        = 'oro_entity.virtual_field_provider.api';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $config = $this->getConfig($container);

        $this->registerProcessingGroups($container, $config);

        $this->registerActionProcessors($container);

        $this->registerTaggedServices(
            $container,
            self::FILTER_FACTORY_SERVICE_ID,
            self::FILTER_FACTORY_TAG,
            'addFilterFactory'
        );
        $this->registerTaggedServices(
            $container,
            self::EXCLUSION_PROVIDER_SERVICE_ID,
            self::EXCLUSION_PROVIDER_TAG,
            'addProvider'
        );
        $this->registerTaggedServices(
            $container,
            self::VIRTUAL_FIELD_PROVIDER_SERVICE_ID,
            self::VIRTUAL_FIELD_PROVIDER_TAG,
            'addProvider'
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    protected function registerProcessingGroups(ContainerBuilder $container, array $config)
    {
        $processorBagServiceDef = $this->findDefinition($container, self::PROCESSOR_BAG_SERVICE_ID);
        if (null !== $processorBagServiceDef) {
            foreach ($config['actions'] as $action => $actionConfig) {
                if (isset($actionConfig['processing_groups'])) {
                    foreach ($actionConfig['processing_groups'] as $group => $groupConfig) {
                        $processorBagServiceDef->addMethodCall(
                            'addGroup',
                            [$group, $action, $groupConfig['priority']]
                        );
                    }
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function registerActionProcessors(ContainerBuilder $container)
    {
        $actionProcessorBagServiceDef = $this->findDefinition($container, self::ACTION_PROCESSOR_BAG_SERVICE_ID);
        if (null !== $actionProcessorBagServiceDef) {
            $taggedServices = $container->findTaggedServiceIds(self::ACTION_PROCESSOR_TAG);
            foreach ($taggedServices as $id => $attributes) {
                $actionProcessorBagServiceDef->addMethodCall(
                    'addProcessor',
                    [new Reference($id)]
                );
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $chainServiceId
     * @param string           $tagName
     * @param string           $addMethodName
     */
    protected function registerTaggedServices(ContainerBuilder $container, $chainServiceId, $tagName, $addMethodName)
    {
        $chainServiceDef = $this->findDefinition($container, $chainServiceId);
        if (null !== $chainServiceDef) {
            // find services
            $services = [];
            $taggedServices = $container->findTaggedServiceIds($tagName);
            foreach ($taggedServices as $id => $attributes) {
                $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
                $services[$priority][] = new Reference($id);
            }
            if (empty($services)) {
                return;
            }

            // sort by priority and flatten
            krsort($services);
            $services = call_user_func_array('array_merge', $services);

            // register
            foreach ($services as $service) {
                $chainServiceDef->addMethodCall($addMethodName, [$service]);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    protected function getConfig(ContainerBuilder $container)
    {
        $processor = new Processor();

        return $processor->processConfiguration(
            new Configuration(),
            $container->getExtensionConfig('oro_api')
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $serviceId
     *
     * @return Definition|null
     */
    protected function findDefinition(ContainerBuilder $container, $serviceId)
    {
        return $container->hasDefinition($serviceId) || $container->hasAlias($serviceId)
            ? $container->findDefinition($serviceId)
            : null;

    }
}
