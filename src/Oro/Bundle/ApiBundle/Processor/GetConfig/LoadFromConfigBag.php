<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityConfigMerger;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig\MergeActionConfigHelper;
use Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig\MergeParentResourceHelper;
use Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig\MergeSubresourceConfigHelper;
use Oro\Bundle\ApiBundle\Provider\ConfigBagRegistry;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads configuration from "Resources/config/oro/api.yml".
 */
class LoadFromConfigBag implements ProcessorInterface
{
    private ConfigExtensionRegistry $configExtensionRegistry;
    private ConfigLoaderFactory $configLoaderFactory;
    private EntityConfigMerger $entityConfigMerger;
    private ConfigBagRegistry $configBagRegistry;
    private ResourcesProvider $resourcesProvider;
    private MergeParentResourceHelper $mergeParentResourceHelper;
    private MergeActionConfigHelper $mergeActionConfigHelper;
    private MergeSubresourceConfigHelper $mergeSubresourceConfigHelper;
    private ?string $entityClass = null;
    private ?string $parentResourceClass = null;

    public function __construct(
        ConfigExtensionRegistry $configExtensionRegistry,
        ConfigLoaderFactory $configLoaderFactory,
        ConfigBagRegistry $configBagRegistry,
        ResourcesProvider $resourcesProvider,
        EntityConfigMerger $entityConfigMerger,
        MergeParentResourceHelper $mergeParentResourceHelper,
        MergeActionConfigHelper $mergeActionConfigHelper,
        MergeSubresourceConfigHelper $mergeSubresourceConfigHelper
    ) {
        $this->configExtensionRegistry = $configExtensionRegistry;
        $this->configLoaderFactory = $configLoaderFactory;
        $this->entityConfigMerger = $entityConfigMerger;
        $this->configBagRegistry = $configBagRegistry;
        $this->resourcesProvider = $resourcesProvider;
        $this->mergeParentResourceHelper = $mergeParentResourceHelper;
        $this->mergeActionConfigHelper = $mergeActionConfigHelper;
        $this->mergeSubresourceConfigHelper = $mergeSubresourceConfigHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        if ($context->hasResult()) {
            // a config already exists
            return;
        }

        $this->processConfig($context);
    }

    private function processConfig(ConfigContext $context): void
    {
        $this->entityClass = $context->getClassName();
        try {
            $config = $this->buildConfig($this->entityClass, $context->getVersion(), $context->getRequestType());
            $parentResourceClass = $this->parentResourceClass;
        } finally {
            $this->entityClass = null;
            $this->parentResourceClass = null;
        }

        $this->saveConfig($context, $config);
        if (null !== $parentResourceClass) {
            $this->mergeParentResourceHelper->mergeParentResourceConfig($context, $parentResourceClass);
        }
    }

    private function buildConfig(string $entityClass, string $version, RequestType $requestType): array
    {
        $config = $this->getConfig($entityClass, $version, $requestType);
        $isInherit = true;
        if (null === $config) {
            $config = [];
        } else {
            $isInherit = $this->getInheritAndThenRemoveIt($config);
        }
        if ($isInherit) {
            $configs = [$config];
            $parentClass = (new \ReflectionClass($entityClass))->getParentClass();
            while ($parentClass) {
                $config = $this->getConfig($parentClass->getName(), $version, $requestType);
                if (false === $config) {
                    break;
                }
                if (!empty($config)) {
                    $isInherit = $this->getInheritAndThenRemoveIt($config);
                    $configs[] = $config;
                    if (!$isInherit) {
                        break;
                    }
                }
                $parentClass = $parentClass->getParentClass();
            }
            if (\count($configs) === 1) {
                $config = $configs[0];
            } else {
                $config = array_pop($configs);
                while (!empty($configs)) {
                    $config = $this->mergeConfigs(array_pop($configs), $config);
                }
            }
        }

        return $config;
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array|false|null Returns an array if a configuration exists,
     *                          null if if a configuration does not exist,
     *                          and FALSE if loading of a configuration of parent classes should be stopped
     */
    private function getConfig(string $entityClass, string $version, RequestType $requestType): array|null|false
    {
        if ($this->entityClass
            && $entityClass !== $this->entityClass
            && !$this->parentResourceClass
            && $this->resourcesProvider->isResourceKnown($entityClass, $version, $requestType)
        ) {
            /**
             * remember the class name of parent API resource and stop processing of other parents
             * @see processConfig
             */
            $this->parentResourceClass = $entityClass;

            return false;
        }

        return $this->configBagRegistry->getConfigBag($requestType)->getConfig($entityClass, $version);
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function saveConfig(ConfigContext $context, array $config): void
    {
        $hasDescriptionsConfigExtra = $context->hasExtra(DescriptionsConfigExtra::NAME);
        $action = $context->getTargetAction();
        if ($action) {
            if (!empty($config[ConfigUtil::ACTIONS][$action])) {
                $config = $this->mergeActionConfigHelper->mergeActionConfig(
                    $config,
                    $config[ConfigUtil::ACTIONS][$action],
                    $hasDescriptionsConfigExtra
                );
            }
            $association = $context->getAssociationName();
            if ($association) {
                $parentConfig = $this->buildConfig(
                    $context->getParentClassName(),
                    $context->getVersion(),
                    $context->getRequestType()
                );
                if (!empty($parentConfig[ConfigUtil::SUBRESOURCES][$association])) {
                    $config = $this->mergeSubresourceConfigHelper->mergeSubresourcesConfig(
                        $config,
                        $parentConfig[ConfigUtil::SUBRESOURCES][$association],
                        $action,
                        $hasDescriptionsConfigExtra,
                        $context->hasExtra(FiltersConfigExtra::NAME),
                        $context->hasExtra(SortersConfigExtra::NAME)
                    );
                }
            }
        }

        $extras = $context->getExtras();
        foreach ($extras as $extra) {
            if ($extra instanceof ConfigExtraSectionInterface) {
                $sectionName = $extra->getName();
                if (!empty($config[$sectionName]) && !$context->has($sectionName)) {
                    $context->set(
                        $sectionName,
                        $this->loadConfigObject($extra->getConfigType(), $config[$sectionName])
                    );
                }
            }
        }

        $sectionNames = $this->configExtensionRegistry->getConfigSectionNames();
        foreach ($sectionNames as $sectionName) {
            unset($config[$sectionName]);
        }

        if (!$hasDescriptionsConfigExtra) {
            unset($config[ConfigUtil::DOCUMENTATION_RESOURCE]);
        }

        if (!empty($config)) {
            $context->setResult(
                $this->loadConfigObject(ConfigUtil::DEFINITION, $config)
            );
        }
    }

    private function mergeConfigs(array $config, array $parentConfig): array
    {
        return $this->entityConfigMerger->merge($config, $parentConfig);
    }

    private function loadConfigObject(string $configType, array $config): object
    {
        return $this->configLoaderFactory->getLoader($configType)->load($config);
    }

    private function getInheritAndThenRemoveIt(array &$config): bool
    {
        if (\array_key_exists(ConfigUtil::INHERIT, $config)) {
            $isInherit = $config[ConfigUtil::INHERIT];
            unset($config[ConfigUtil::INHERIT]);
        } else {
            $isInherit = true;
        }

        return $isInherit;
    }
}
