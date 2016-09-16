<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Request\ApiActions;

/**
 * Builds a list of resources accessible through Data API.
 */
class LoadAccessibleResources implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectResourcesContext $context */

        $accessibleResources = $context->getAccessibleResources();
        if (!empty($accessibleResources)) {
            // the accessible resources are already built
            return;
        }

        $resources = $context->getResult();
        foreach ($resources as $resource) {
            if (!in_array(ApiActions::GET, $resource->getExcludedActions(), true)) {
                $accessibleResources[] = $resource->getEntityClass();
            }
        }
        $context->setAccessibleResources($accessibleResources);
    }
}
