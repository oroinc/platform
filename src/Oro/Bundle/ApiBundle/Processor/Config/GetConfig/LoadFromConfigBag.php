<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

use Oro\Bundle\ApiBundle\Config\ActionConfig;
use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityDefinitionConfiguration;
use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfigLoader;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\LoadFromConfigBag as BaseLoadFromConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;

/**
 * Loads configuration from "Resources/config/oro/api.yml".
 */
class LoadFromConfigBag extends BaseLoadFromConfigBag
{
    /** @var ConfigBag */
    protected $configBag;

    /**
     * @param ConfigExtensionRegistry          $configExtensionRegistry
     * @param ConfigLoaderFactory              $configLoaderFactory
     * @param EntityHierarchyProviderInterface $entityHierarchyProvider
     * @param ConfigBag                        $configBag
     */
    public function __construct(
        ConfigExtensionRegistry $configExtensionRegistry,
        ConfigLoaderFactory $configLoaderFactory,
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        ConfigBag $configBag
    ) {
        parent::__construct($configExtensionRegistry, $configLoaderFactory, $entityHierarchyProvider);
        $this->configBag = $configBag;
    }

    /**
     * {@inheritdoc}
     */
    protected function saveConfig(ConfigContext $context, array $config)
    {
        $action = $context->getTargetAction();
        if ($action) {
            if (!empty($config[ConfigUtil::ACTIONS][$action])) {
                $config = $this->mergeActionConfig(
                    $config,
                    $config[ConfigUtil::ACTIONS][$action],
                    $context
                );
            }
            $association = $context->getAssociationName();
            if ($association) {
                $parentConfig = $this->loadConfig($context->getParentClassName(), $context->getVersion());
                if (!empty($parentConfig[ConfigUtil::SUBRESOURCES][$association])) {
                    $subresourceConfig = $parentConfig[ConfigUtil::SUBRESOURCES][$association];
                    if (!empty($subresourceConfig[ConfigUtil::ACTIONS][$action])) {
                        $config = $this->mergeActionConfig(
                            $config,
                            $subresourceConfig[ConfigUtil::ACTIONS][$action],
                            $context
                        );
                    }
                    if ($context->hasExtra(FiltersConfigExtra::NAME)
                        && !empty($subresourceConfig[ConfigUtil::FILTERS])
                    ) {
                        $config = $this->mergeFiltersConfig($config, $subresourceConfig[ConfigUtil::FILTERS]);
                    }
                }
            }
        }

        parent::saveConfig($context, $config);
    }

    /**
     * @param array $statusCodesConfig
     *
     * @return StatusCodesConfig
     */
    protected function loadStatusCodes(array $statusCodesConfig)
    {
        $statusCodesLoader = new StatusCodesConfigLoader();

        return $statusCodesLoader->load($statusCodesConfig);
    }

    /**
     * @param array         $config
     * @param array         $actionConfig
     * @param ConfigContext $context
     *
     * @return array
     */
    protected function mergeActionConfig(array $config, array $actionConfig, ConfigContext $context)
    {
        if (!empty($actionConfig[ActionConfig::STATUS_CODES])
            && $context->hasExtra(DescriptionsConfigExtra::NAME)
        ) {
            $config = $this->mergeStatusCodes(
                $config,
                $this->loadStatusCodes($actionConfig[ActionConfig::STATUS_CODES])
            );
        }
        unset($actionConfig[ActionConfig::STATUS_CODES]);

        unset($actionConfig[ActionConfig::EXCLUDE]);
        $actionFields = null;
        if (array_key_exists(ActionConfig::FIELDS, $actionConfig)) {
            $actionFields = $actionConfig[ActionConfig::FIELDS];
            unset($actionConfig[ActionConfig::FIELDS]);
        }
        $config = array_merge($config, $actionConfig);
        if (!empty($actionFields)) {
            $config[EntityDefinitionConfig::FIELDS] = !empty($config[EntityDefinitionConfig::FIELDS])
                ? $this->mergeActionFields($config[EntityDefinitionConfig::FIELDS], $actionFields)
                : $actionFields;
        }

        return $config;
    }

    /**
     * @param array             $config
     * @param StatusCodesConfig $statusCodes
     *
     * @return array
     */
    protected function mergeStatusCodes(array $config, StatusCodesConfig $statusCodes)
    {
        if (!array_key_exists(ActionConfig::STATUS_CODES, $config)) {
            $config[ActionConfig::STATUS_CODES] = $statusCodes;
        } else {
            /** @var StatusCodesConfig $existingStatusCodes */
            $existingStatusCodes = $config[ActionConfig::STATUS_CODES];
            $codes = $statusCodes->getCodes();
            foreach ($codes as $code => $statusCode) {
                $existingStatusCodes->addCode($code, $statusCode);
            }
        }

        return $config;
    }

    /**
     * @param array $fields
     * @param array $actionFields
     *
     * @return array
     */
    protected function mergeActionFields(array $fields, array $actionFields)
    {
        foreach ($actionFields as $key => $value) {
            if (!empty($fields[$key])) {
                if (!empty($value)) {
                    $fields[$key] = array_merge($fields[$key], $value);
                }
            } else {
                $fields[$key] = $value;
            }
        }

        return $fields;
    }

    /**
     * @param array $config
     * @param array $filtersConfig
     *
     * @return array
     */
    protected function mergeFiltersConfig(array $config, array $filtersConfig)
    {
        if (ConfigUtil::isExcludeAll($filtersConfig) || !array_key_exists(ConfigUtil::FILTERS, $config)) {
            $config[ConfigUtil::FILTERS] = $filtersConfig;
        } elseif (!empty($filtersConfig[ConfigUtil::FIELDS])) {
            if (!array_key_exists(ConfigUtil::FIELDS, $config[ConfigUtil::FILTERS])) {
                $config[ConfigUtil::FILTERS][ConfigUtil::FIELDS] = $filtersConfig[ConfigUtil::FIELDS];
            } else {
                $config[ConfigUtil::FILTERS][ConfigUtil::FIELDS] = array_merge(
                    $config[ConfigUtil::FILTERS][ConfigUtil::FIELDS],
                    $filtersConfig[ConfigUtil::FIELDS]
                );
            }
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfig($entityClass, $version)
    {
        return $this->configBag->getConfig($entityClass, $version);
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationTree()
    {
        $configTreeBuilder = new TreeBuilder();
        $configuration     = new EntityConfiguration(
            ApiConfiguration::ENTITIES_SECTION,
            new EntityDefinitionConfiguration(),
            $this->configExtensionRegistry->getConfigurationSettings(),
            $this->configExtensionRegistry->getMaxNestingLevel()
        );
        $configuration->configure($configTreeBuilder->root('entity')->children());

        return $configTreeBuilder->buildTree();
    }
}
