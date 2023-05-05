<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Excludes "update_list" action in case it is not configured manually
 * or both "create" and "update" actions are excluded for a resource.
 */
class ExcludeUpdateListAction implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CollectResourcesContext $context */

        /** @var ActionsConfig[] $actionsConfig */
        $actionsConfig = $context->get(AddExcludedActions::ACTIONS_CONFIG_KEY);

        /** @var ApiResource[] $resources */
        $resources = $context->getResult();
        foreach ($resources as $resource) {
            $excludedActions = $resource->getExcludedActions();
            if (!\in_array(ApiAction::UPDATE_LIST, $excludedActions, true)
                && $this->shouldUpdateListActionBeExcluded(
                    $excludedActions,
                    $actionsConfig[$resource->getEntityClass()] ?? null
                )
            ) {
                $excludedActions[] = ApiAction::UPDATE_LIST;
                $resource->setExcludedActions($excludedActions);
            }
        }
    }

    /**
     * @param string[]           $excludedActions
     * @param ActionsConfig|null $actionsConfig
     *
     * @return bool
     */
    private function shouldUpdateListActionBeExcluded(array $excludedActions, ?ActionsConfig $actionsConfig): bool
    {
        if (null === $actionsConfig || null === $actionsConfig->getAction(ApiAction::UPDATE_LIST)) {
            return true;
        }

        return
            \in_array(ApiAction::CREATE, $excludedActions, true)
            && \in_array(ApiAction::UPDATE, $excludedActions, true);
    }
}
