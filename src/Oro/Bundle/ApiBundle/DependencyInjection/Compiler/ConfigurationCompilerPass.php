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
    const ACTION_PROCESSOR_TAG            = 'oro.api.action_processor';
    const ACTION_PROCESSOR_BAG_SERVICE_ID = 'oro_api.action_processor_bag';
    const PROCESSOR_BAG_SERVICE_ID        = 'oro_api.processor_bag';
    const FILTER_FACTORY_TAG              = 'oro.api.filter_factory';
    const FILTER_FACTORY_SERVICE_ID       = 'oro.api.filter_factory';
    const EXCLUSION_PROVIDER_TAG          = 'oro_entity.exclusion_provider.api';
    const EXCLUSION_PROVIDER_SERVICE_ID   = 'oro_api.entity_exclusion_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $config = $this->getConfig($container);
        $this->registerProcessingGroups($container, $config);
        $this->registerActionProcessors($container);
        $this->registerFilterFactories($container);
        $this->registerExclusionProviders($container);
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
     */
    protected function registerFilterFactories(ContainerBuilder $container)
    {
        $filterFactoryServiceDef = $this->findDefinition($container, self::FILTER_FACTORY_SERVICE_ID);
        if (null !== $filterFactoryServiceDef) {
            // find factories
            $factories      = [];
            $taggedServices = $container->findTaggedServiceIds(self::FILTER_FACTORY_TAG);
            foreach ($taggedServices as $id => $attributes) {
                $priority               = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
                $factories[$priority][] = new Reference($id);
            }
            if (empty($factories)) {
                return;
            }

            // sort by priority and flatten
            krsort($factories);
            $factories = call_user_func_array('array_merge', $factories);

            // register
            foreach ($factories as $factory) {
                $filterFactoryServiceDef->addMethodCall('addFilterFactory', [$factory]);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function registerExclusionProviders(ContainerBuilder $container)
    {
        $filterFactoryServiceDef = $this->findDefinition($container, self::EXCLUSION_PROVIDER_SERVICE_ID);
        if (null !== $filterFactoryServiceDef) {
            // find providers
            $providers      = [];
            $taggedServices = $container->findTaggedServiceIds(self::EXCLUSION_PROVIDER_TAG);
            foreach ($taggedServices as $id => $attributes) {
                $priority               = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
                $providers[$priority][] = new Reference($id);
            }
            if (empty($providers)) {
                return;
            }

            // sort by priority and flatten
            krsort($providers);
            $providers = call_user_func_array('array_merge', $providers);

            // register
            foreach ($providers as $provider) {
                $filterFactoryServiceDef->addMethodCall('addProvider', [$provider]);
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
        return $container->hasDefinition($serviceId)
            ? $container->getDefinition($serviceId)
            : null;
    }
}
