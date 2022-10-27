<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;

/**
 * Sets groups of the target action context for executing processors
 * responsible to transform the request data to objects.
 */
class SetTransformGroups extends SetGroups
{
    /**
     * {@inheritdoc}
     */
    protected function setGroups(Context $targetContext, string $targetAction): void
    {
        if (ApiAction::CREATE === $targetAction || ApiAction::UPDATE === $targetAction) {
            $targetContext->setFirstGroup(ApiActionGroup::RESOURCE_CHECK);
            $targetContext->setLastGroup(ApiActionGroup::TRANSFORM_DATA);
        }
    }
}
