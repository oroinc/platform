<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Excludes "update_list" action even if it is enabled in the config.
 */
class ForceExcludeUpdateListAction implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CollectResourcesContext $context */

        /** @var ApiResource[] $resources */
        $resources = $context->getResult();
        foreach ($resources as $resource) {
            $excludedActions = $resource->getExcludedActions();
            if (!\in_array(ApiAction::UPDATE_LIST, $excludedActions, true)) {
                $excludedActions[] = ApiAction::UPDATE_LIST;
                $resource->setExcludedActions($excludedActions);
            }
        }
    }
}
