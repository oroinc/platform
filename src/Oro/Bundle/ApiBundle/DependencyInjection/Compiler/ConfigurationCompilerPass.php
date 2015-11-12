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
    const ACTION_HANDLER_SERVICE_ID = 'oro_api.action_handler';
    const PROCESSOR_BAG_SERVICE_ID = 'oro_api.processor_bag';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $config                  = $this->getConfig($container);
        $actionHandlerServiceDef = $this->findDefinition($container, self::ACTION_HANDLER_SERVICE_ID);
        $processorBagServiceDef  = $this->findDefinition($container, self::PROCESSOR_BAG_SERVICE_ID);
        foreach ($config['actions'] as $action => $actionConfig) {
            if (null !== $actionHandlerServiceDef) {
                $actionHandlerServiceDef->addMethodCall(
                    'addProcessor',
                    [$action, new Reference($actionConfig['processor'])]
                );
            }
            if (null !== $processorBagServiceDef && isset($actionConfig['processing_groups'])) {
                foreach ($actionConfig['processing_groups'] as $group => $groupConfig) {
                    $processorBagServiceDef->addMethodCall(
                        'addGroup',
                        [$group, $action, $groupConfig['priority']]
                    );
                }
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
