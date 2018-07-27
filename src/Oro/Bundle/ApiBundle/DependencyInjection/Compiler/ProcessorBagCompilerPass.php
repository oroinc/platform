<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Oro\Component\ChainProcessor\DependencyInjection\ProcessorsLoader;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds all registered Data API processors to the processor bag service.
 * For "customize_loaded_data" processors that do not have
 * the "identifier_only" attribute, it is added with FALSE value.
 * If such processor has this attribute and its value is NULL, the attribute is removed.
 */
class ProcessorBagCompilerPass implements CompilerPassInterface
{
    private const PROCESSOR_BAG_CONFIG_PROVIDER_SERVICE_ID = 'oro_api.processor_bag_config_provider';
    private const CUSTOMIZE_LOADED_DATA_ACTION             = 'customize_loaded_data';
    private const IDENTIFIER_ONLY_ATTRIBUTE                = 'identifier_only';

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
            $processors = ProcessorsLoader::loadProcessors($container, DependencyInjectionUtil::PROCESSOR_TAG);
            $builder = new ProcessorBagConfigBuilder($groups, $processors);
            $processorBagConfigProviderServiceDef->replaceArgument(0, $builder->getGroups());
            $processorBagConfigProviderServiceDef
                ->replaceArgument(1, $this->normalizeProcessors($builder->getProcessors()));
        }
    }

    /**
     * @param array $processors [action => [[processor id, [attribute name => attribute value, ...]], ...], ...]
     *
     * @return array [action => [[processor id, [attribute name => attribute value, ...]], ...], ...]
     */
    private function normalizeProcessors(array $processors): array
    {
        if (!empty($processors[self::CUSTOMIZE_LOADED_DATA_ACTION])) {
            $actionProcessors = $processors[self::CUSTOMIZE_LOADED_DATA_ACTION];
            foreach ($actionProcessors as $key => $item) {
                $attributes = $item[1];
                if (!\array_key_exists(self::IDENTIFIER_ONLY_ATTRIBUTE, $attributes)) {
                    // add "identifier_only" attribute to the beginning of an attributes array,
                    // it will give a small performance gain at the runtime
                    $actionProcessors[$key][1] = [self::IDENTIFIER_ONLY_ATTRIBUTE => false] + $attributes;
                } elseif (null === $attributes[self::IDENTIFIER_ONLY_ATTRIBUTE]) {
                    unset($actionProcessors[$key][1][self::IDENTIFIER_ONLY_ATTRIBUTE]);
                }
            }
            $processors[self::CUSTOMIZE_LOADED_DATA_ACTION] = $actionProcessors;
        }

        return $processors;
    }
}
