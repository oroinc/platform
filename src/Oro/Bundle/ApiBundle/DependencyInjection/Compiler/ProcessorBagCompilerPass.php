<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\ChainProcessor\DependencyInjection\ProcessorsLoader;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

/**
 * Adds all registered Data API processors to the processor bag service.
 */
class ProcessorBagCompilerPass implements CompilerPassInterface
{
    private const PROCESSOR_BAG_CONFIG_PROVIDER_SERVICE_ID = 'oro_api.processor_bag_config_provider';
    private const PROCESSOR_TAG                            = 'oro.api.processor';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorBagConfigProviderServiceDef = DependencyInjectionUtil::findDefinition(
            $container,
            self::PROCESSOR_BAG_CONFIG_PROVIDER_SERVICE_ID
        );
        if (null !== $processorBagConfigProviderServiceDef) {
            $groups = [];
            $config = DependencyInjectionUtil::getConfig($container);
            foreach ($config['actions'] as $action => $actionConfig) {
                if (isset($actionConfig['processing_groups'])) {
                    foreach ($actionConfig['processing_groups'] as $group => $groupConfig) {
                        $groups[$action][$group] = DependencyInjectionUtil::getPriority($groupConfig);
                    }
                }
            }
            $processors = ProcessorsLoader::loadProcessors($container, self::PROCESSOR_TAG);
            $builder = new ProcessorBagConfigBuilder($groups, $processors);
            $processorBagConfigProviderServiceDef->replaceArgument(0, $builder->getGroups());
            $processorBagConfigProviderServiceDef->replaceArgument(1, $builder->getProcessors());
        }
    }
}
