<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Config\ActionsConfigExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;

/**
 * Adds info about actions which must not be available to resources.
 */
class AddExcludedActions implements ProcessorInterface
{
    /** the name of the Context item to store the configuration of "actions" section */
    const ACTIONS_CONFIG_KEY = 'actions_config';

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectResourcesContext $context */

        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $configExtras = [new ActionsConfigExtra()];

        $actionsConfig = [];
        $resources = $context->getResult();
        /** @var ApiResource $resource */
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();

            $config = $this->configProvider->getConfig($entityClass, $version, $requestType, $configExtras);
            $actions = $config->getActions();
            $actionsConfig[$entityClass] = $actions;

            $excludedActions = $this->getExcludedActions($actions);
            if (!empty($excludedActions)) {
                $resource->setExcludedActions($excludedActions);
            }
        }
        $context->set(self::ACTIONS_CONFIG_KEY, $actionsConfig);
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
