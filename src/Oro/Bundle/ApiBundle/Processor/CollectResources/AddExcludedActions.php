<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig\MergeActionConfigHelper;
use Oro\Bundle\ApiBundle\Provider\ConfigBagRegistry;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds info about actions which must not be available to resources.
 */
class AddExcludedActions implements ProcessorInterface
{
    /** the name of the context item to store the configuration of "actions" section */
    public const ACTIONS_CONFIG_KEY = 'actions_config';

    private ConfigLoaderFactory $configLoaderFactory;
    private ConfigBagRegistry $configBagRegistry;
    private MergeActionConfigHelper $mergeActionConfigHelper;

    public function __construct(
        ConfigLoaderFactory $configLoaderFactory,
        ConfigBagRegistry $configBagRegistry,
        MergeActionConfigHelper $mergeActionConfigHelper
    ) {
        $this->configLoaderFactory = $configLoaderFactory;
        $this->configBagRegistry = $configBagRegistry;
        $this->mergeActionConfigHelper = $mergeActionConfigHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CollectResourcesContext $context */

        $actionsConfig = [];
        $version = $context->getVersion();

        $resources = $context->getResult();
        $requestType = $context->getRequestType();
        /** @var ApiResource $resource */
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();
            $actions = $this->getActionsConfig($entityClass, $version, $requestType);
            if (null !== $actions) {
                $actionsConfig[$entityClass] = $actions;
                $excludedActions = $this->getExcludedActions($actions);
                if (!empty($excludedActions)) {
                    $resource->setExcludedActions($excludedActions);
                }
            }
        }
        $context->set(self::ACTIONS_CONFIG_KEY, $actionsConfig);
    }

    /**
     * Loads configuration from the "actions" section from "Resources/config/oro/api.yml"
     */
    private function getActionsConfig(string $entityClass, string $version, RequestType $requestType): ?ActionsConfig
    {
        $actions = null;
        $configs = $this->mergeActionConfigs(
            $this->getConfigs($entityClass, $version, $requestType)
        );
        if ($configs) {
            $actions = $this->configLoaderFactory->getLoader(ConfigUtil::ACTIONS)->load($configs);
        }

        return $actions;
    }

    /**
     * @param ActionsConfig $actions
     *
     * @return string[]
     */
    private function getExcludedActions(ActionsConfig $actions): array
    {
        $result = [];

        $items = $actions->getActions();
        foreach ($items as $actionName => $action) {
            if ($action->isExcluded()) {
                $result[] = $actionName;
            }
        }

        return $result;
    }

    private function getConfigs(string $entityClass, string $version, RequestType $requestType): array
    {
        $configs = [];
        $config = $this->getConfig($entityClass, $version, $requestType);
        if ($config) {
            $configs[] = $config;
        }
        if ($this->isInherit($config)) {
            $parentClass = (new \ReflectionClass($entityClass))->getParentClass();
            while ($parentClass) {
                $config = $this->getConfig($parentClass->getName(), $version, $requestType);
                if ($config) {
                    $configs[] = $config;
                }
                if (!$this->isInherit($config)) {
                    break;
                }
                $parentClass = $parentClass->getParentClass();
            }
        }

        return $configs;
    }

    private function getConfig(string $entityClass, string $version, RequestType $requestType): ?array
    {
        return $this->configBagRegistry->getConfigBag($requestType)->getConfig($entityClass, $version);
    }

    private function isInherit(?array $config): bool
    {
        if (null !== $config && \array_key_exists(ConfigUtil::INHERIT, $config)) {
            return $config[ConfigUtil::INHERIT];
        }

        return true;
    }

    private function mergeActionConfigs(array $configs): array
    {
        $result = [];
        $configs = array_reverse($configs);
        foreach ($configs as $config) {
            if (empty($config[ConfigUtil::ACTIONS])) {
                continue;
            }
            $actionConfigs = $config[ConfigUtil::ACTIONS];
            foreach ($actionConfigs as $action => $actionConfig) {
                if ($actionConfig) {
                    if (isset($result[$action])) {
                        $result[$action] = $this->mergeActionConfigHelper->mergeActionConfig(
                            $result[$action],
                            $actionConfig,
                            false
                        );
                        if (isset($actionConfig[ConfigUtil::EXCLUDE])) {
                            $result[$action][ConfigUtil::EXCLUDE] = $actionConfig[ConfigUtil::EXCLUDE];
                        }
                    } else {
                        $result[$action] = $actionConfig;
                    }
                }
            }
        }

        return $result;
    }
}
