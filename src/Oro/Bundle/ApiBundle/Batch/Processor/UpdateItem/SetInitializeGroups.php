<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;

/**
 * Sets groups of the target action context for executing processors
 * responsible to initialize the target action.
 */
class SetInitializeGroups extends SetGroups
{
    /**
     * {@inheritdoc}
     */
    protected function setGroups(Context $targetContext, string $targetAction): void
    {
        $targetContext->setFirstGroup(null);
        if (ApiAction::CREATE === $targetAction || ApiAction::UPDATE === $targetAction) {
            $targetContext->setLastGroup(ApiActionGroup::INITIALIZE);
        }
    }
}
