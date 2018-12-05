<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Oro\Component\ChainProcessor\DependencyInjection\ProcessorsLoader;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * Adds all registered Data API processors to the processor bag service.
 * By performance reasons "customize_loaded_data" processors with "collection" attribute equals to TRUE
 * are moved to "collection" group and other processors to "item" group. The "collection" attribute is removed.
 * For "customize_loaded_data" processors that do not have "identifier_only" attribute,
 * it is added with FALSE value. If such processor has this attribute and its value is NULL,
 * the attribute is removed.
 * @see \Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Handler\EntityHandler
 */
class ProcessorBagCompilerPass implements CompilerPassInterface
{
    private const PROCESSOR_BAG_CONFIG_PROVIDER_SERVICE_ID = 'oro_api.processor_bag_config_provider';
    private const CUSTOMIZE_LOADED_DATA_ACTION             = 'customize_loaded_data';
    private const IDENTIFIER_ONLY_ATTRIBUTE                = 'identifier_only';
    private const COLLECTION_ATTRIBUTE                     = 'collection';
    private const GROUP_ATTRIBUTE                          = 'group';

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
            $groups[self::CUSTOMIZE_LOADED_DATA_ACTION] = ['item' => 0, 'collection' => -1];
            $processors = ProcessorsLoader::loadProcessors($container, DependencyInjectionUtil::PROCESSOR_TAG);
            $builder = new ProcessorBagConfigBuilder($groups, $processors);
            $processorBagConfigProviderServiceDef->replaceArgument(0, $builder->getGroups());
            $processorBagConfigProviderServiceDef
                ->replaceArgument(1, $this->normalizeProcessors($builder->getProcessors()));
        }
    }

    /**
     * @param array $allProcessors [action => [[processor id, [attribute name => attribute value, ...]], ...], ...]
     *
     * @return array [action => [[processor id, [attribute name => attribute value, ...]], ...], ...]
     */
    private function normalizeProcessors(array $allProcessors): array
    {
        // normalize "customize_loaded_data" processors
        // and split processors to "item" and "collection" groups
        if (!empty($allProcessors[self::CUSTOMIZE_LOADED_DATA_ACTION])) {
            $itemProcessors = [];
            $collectionProcessors = [];
            $processors = $allProcessors[self::CUSTOMIZE_LOADED_DATA_ACTION];
            foreach ($processors as $key => $item) {
                if (array_key_exists(self::GROUP_ATTRIBUTE, $item[1])) {
                    throw new LogicException(sprintf(
                        'The "%s" processor uses the "%s" tag attribute that is not allowed'
                        . ' for "%s" action. Use "%s" tag attribute instead.',
                        $item[0],
                        self::GROUP_ATTRIBUTE,
                        self::CUSTOMIZE_LOADED_DATA_ACTION,
                        self::COLLECTION_ATTRIBUTE
                    ));
                }
                $isCollectionProcessor = array_key_exists(self::COLLECTION_ATTRIBUTE, $item[1])
                    && $item[1][self::COLLECTION_ATTRIBUTE];
                unset($item[1][self::COLLECTION_ATTRIBUTE]);
                if ($isCollectionProcessor) {
                    $item[1][self::GROUP_ATTRIBUTE] = 'collection';
                    // "identifier_only" attribute is not supported for collections
                    unset($item[1][self::IDENTIFIER_ONLY_ATTRIBUTE]);
                    $collectionProcessors[] = $item;
                } else {
                    $item[1][self::GROUP_ATTRIBUTE] = 'item';
                    // normalize "identifier_only" attribute
                    if (!array_key_exists(self::IDENTIFIER_ONLY_ATTRIBUTE, $item[1])) {
                        // add "identifier_only" attribute to the beginning of an attributes array,
                        // it will give a small performance gain at the runtime
                        $item[1] = [self::IDENTIFIER_ONLY_ATTRIBUTE => false] + $item[1];
                    } elseif (null === $item[1][self::IDENTIFIER_ONLY_ATTRIBUTE]) {
                        unset($item[1][self::IDENTIFIER_ONLY_ATTRIBUTE]);
                    }
                    $itemProcessors[] = $item;
                }
            }
            $allProcessors[self::CUSTOMIZE_LOADED_DATA_ACTION] = array_merge($itemProcessors, $collectionProcessors);
        }

        ksort($allProcessors);

        return $allProcessors;
    }
}
