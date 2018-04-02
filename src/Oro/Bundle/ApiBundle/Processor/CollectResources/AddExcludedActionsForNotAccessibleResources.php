<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Disables all actions for resources which are not accessible through Data API.
 */
class AddExcludedActionsForNotAccessibleResources implements ProcessorInterface
{
    /** @var ActionProcessorBagInterface */
    protected $actionProcessorBag;

    /**
     * @param ActionProcessorBagInterface $actionProcessorBag
     */
    public function __construct(ActionProcessorBagInterface $actionProcessorBag)
    {
        $this->actionProcessorBag = $actionProcessorBag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectResourcesContext $context */

        $accessibleResources = array_fill_keys($context->getAccessibleResources(), true);
        $allActions = $this->actionProcessorBag->getActions();
        $resources = $context->getResult();
        foreach ($resources as $resource) {
            if (!isset($accessibleResources[$resource->getEntityClass()])) {
                $excludedActions = $resource->getExcludedActions();
                if (empty($excludedActions)) {
                    $resource->setExcludedActions($allActions);
                }
            }
        }
    }
}
