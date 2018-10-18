<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
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

    /** @var ConfigLoaderFactory */
    protected $configLoaderFactory;

    /** @var ConfigBagRegistry */
    protected $configBagRegistry;

    /**
     * @param ConfigLoaderFactory $configLoaderFactory
     * @param ConfigBagRegistry   $configBagRegistry
     */
    public function __construct(
        ConfigLoaderFactory $configLoaderFactory,
        ConfigBagRegistry $configBagRegistry
    ) {
        $this->configLoaderFactory = $configLoaderFactory;
        $this->configBagRegistry = $configBagRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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
     *
     * @param string $entityClass
     * @param string $version
     * @param RequestType $requestType
     *
     * @return ActionsConfig|null
     */
    protected function getActionsConfig($entityClass, $version, RequestType $requestType)
    {
        $actions = null;
        $config = $this->configBagRegistry->getConfigBag($requestType)->getConfig($entityClass, $version);
        if (null !== $config && !empty($config[ConfigUtil::ACTIONS])) {
            $actionsLoader = $this->configLoaderFactory->getLoader(ConfigUtil::ACTIONS);
            $actions = $actionsLoader->load($config[ConfigUtil::ACTIONS]);
        }

        return $actions;
    }

    /**
     * @param ActionsConfig $actions
     *
     * @return string[]
     */
    protected function getExcludedActions(ActionsConfig $actions)
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
}
