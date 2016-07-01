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
                $actionConfig = $config[ConfigUtil::ACTIONS][$action];
                $actionConfig[ActionConfig::STATUS_CODES] = $this->loadStatusCodes($context, $actionConfig);
                $config = $this->mergeActionConfig($config, $actionConfig);
            }
            $association = $context->getAssociationName();
            if ($association
                && !empty($config[ConfigUtil::SUBRESOURCES][$association][ConfigUtil::ACTIONS][$action])
            ) {
                $subresourceConfig = $config[ConfigUtil::SUBRESOURCES][$association][ConfigUtil::ACTIONS][$action];
                $subresourceConfig[ActionConfig::STATUS_CODES] = $this->loadStatusCodes($context, $subresourceConfig);
                $config = $this->mergeActionConfig($config, $subresourceConfig);
            }
        }

        parent::saveConfig($context, $config);
    }

    /**
     * @param ConfigContext $context
     * @param array         $actionConfig
     *
     * @return StatusCodesConfig|null
     */
    protected function loadStatusCodes(ConfigContext $context, array $actionConfig)
    {
        $statusCodes = null;
        if (array_key_exists(ActionConfig::STATUS_CODES, $actionConfig)
            && $context->hasExtra(DescriptionsConfigExtra::NAME)
        ) {
            $statusCodesLoader = new StatusCodesConfigLoader();
            $statusCodes = $statusCodesLoader->load($actionConfig[ActionConfig::STATUS_CODES]);
        }

        return $statusCodes;
    }

    /**
     * @param array $config
     * @param array $actionConfig
     *
     * @return array
     */
    protected function mergeActionConfig(array $config, array $actionConfig)
    {
        if (array_key_exists(ActionConfig::STATUS_CODES, $actionConfig)
            && null !== $actionConfig[ActionConfig::STATUS_CODES]
        ) {
            $config = $this->mergeStatusCodes($config, $actionConfig[ActionConfig::STATUS_CODES]);
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
