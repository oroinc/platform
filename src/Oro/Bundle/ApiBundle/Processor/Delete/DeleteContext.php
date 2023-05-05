<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\ChangeContextInterface;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;

/**
 * The execution context for processors for "delete" action.
 */
class DeleteContext extends SingleItemContext implements ChangeContextInterface
{
    /**
     * {@inheritDoc}
     */
    public function getAllEntities(bool $mainOnly = false): array
    {
        $entity = $this->getResult();
        if (null === $entity) {
            return [];
        }

        return [$entity];
    }
}
