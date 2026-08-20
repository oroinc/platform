<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityDefinitionConfiguration;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;

/**
 * Provides functionality to merge two configurations loaded from
 * "entities" section of "Resources/config/oro/api.yml".
 */
class EntityConfigMerger
{
    private ConfigExtensionRegistry $configExtensionRegistry;
    private ?NodeInterface $configurationTree = null;

    public function __construct(ConfigExtensionRegistry $configExtensionRegistry)
    {
        $this->configExtensionRegistry = $configExtensionRegistry;
    }

    /**
     * Merges the given configs.
     */
    public function merge(array $config, array $parentConfig): array
    {
        return (new Processor())->process(
            $this->getConfigurationTree(),
            [$this->prepareParentConfigToMerge($parentConfig, $config), $config]
        );
    }

    private function getConfigurationTree(): NodeInterface
    {
        if (null === $this->configurationTree) {
            $this->configurationTree = $this->createConfigurationTree();
        }

        return $this->configurationTree;
    }

    private function createConfigurationTree(): NodeInterface
    {
        $configTreeBuilder = new TreeBuilder('root');
        $configuration = new EntityConfiguration(
            ApiConfiguration::ENTITIES_SECTION,
            new EntityDefinitionConfiguration(),
            $this->configExtensionRegistry->getConfigurationSettings(),
            $this->configExtensionRegistry->getMaxNestingLevel()
        );
        $configuration->configure(
            $configTreeBuilder->getRootNode()->children()
        );

        return $configTreeBuilder->buildTree();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function prepareParentConfigToMerge(array $parentConfig, array $config): array
    {
        if (!empty($config[ConfigUtil::FIELDS]) && !empty($parentConfig[ConfigUtil::FIELDS])) {
            foreach ($config[ConfigUtil::FIELDS] as $fieldName => $fieldConfig) {
                if (!\is_array($fieldConfig) || empty($fieldConfig[ConfigUtil::PROPERTY_PATH])) {
                    continue;
                }
                $propertyPath = $fieldConfig[ConfigUtil::PROPERTY_PATH];
                if (ConfigUtil::IGNORE_PROPERTY_PATH === $propertyPath) {
                    continue;
                }
                $parentFieldName = $this->findField($parentConfig, $propertyPath);
                if (null === $parentFieldName || $fieldName === $parentFieldName) {
                    continue;
                }
                if (
                    \array_key_exists($parentFieldName, $config[ConfigUtil::FIELDS])
                    || \array_key_exists($fieldName, $parentConfig[ConfigUtil::FIELDS])
                ) {
                    continue;
                }
                $parentFieldConfig = $parentConfig[ConfigUtil::FIELDS][$parentFieldName];
                unset($parentConfig[ConfigUtil::FIELDS][$parentFieldName]);
                $parentConfig[ConfigUtil::FIELDS][$fieldName] = $parentFieldConfig;
            }
        }

        return $parentConfig;
    }

    private function findField(array $config, string $propertyPath): ?string
    {
        if (\array_key_exists($propertyPath, $config[ConfigUtil::FIELDS])) {
            return $propertyPath;
        }

        $foundFieldName = null;
        foreach ($config[ConfigUtil::FIELDS] as $fieldName => $fieldConfig) {
            if (!\is_array($fieldConfig) || empty($fieldConfig[ConfigUtil::PROPERTY_PATH])) {
                continue;
            }
            if ($fieldConfig[ConfigUtil::PROPERTY_PATH] === $propertyPath) {
                $foundFieldName = $fieldName;
                break;
            }
        }

        return $foundFieldName;
    }
}
