<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;

/**
 * Disables the "delete" action for dictionary entities.
 */
class AddExcludedActionsForDictionaries implements ProcessorInterface
{
    /** @var ChainDictionaryValueListProvider */
    protected $dictionaryProvider;

    /**
     * @param ChainDictionaryValueListProvider $dictionaryProvider
     */
    public function __construct(ChainDictionaryValueListProvider $dictionaryProvider)
    {
        $this->dictionaryProvider = $dictionaryProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectResourcesContext $context */

        $dictionaryEntities = array_fill_keys($this->dictionaryProvider->getSupportedEntityClasses(), true);
        /** @var ActionsConfig[] $actionsConfig */
        $actionsConfig = $context->get(AddExcludedActions::ACTIONS_CONFIG_KEY);

        $resources = $context->getResult();
        foreach ($resources as $resource) {
            if (isset($dictionaryEntities[$resource->getEntityClass()])) {
                $this->addExcludedAction($resource, 'delete', $actionsConfig);
            }
        }
    }

    /**
     * @param ApiResource     $resource
     * @param string          $actionName
     * @param ActionsConfig[] $actionsConfig
     */
    protected function addExcludedAction(ApiResource $resource, $actionName, array $actionsConfig)
    {
        $excludeActions = $resource->getExcludedActions();
        if (in_array($actionName, $excludeActions, true)) {
            // the action is already added to the exclude list
            return;
        }

        $entityClass = $resource->getEntityClass();
        if (isset($actionsConfig[$entityClass])) {
            $action = $actionsConfig[$entityClass]->getAction($actionName);
            if (null !== $action && $action->hasExcluded()) {
                // the "exclude" flag for the action is set manually in 'Resources/config/oro/api.yml'
                return;
            }
        }

        $excludeActions[] = $actionName;
        $resource->setExcludedActions($excludeActions);
    }
}
