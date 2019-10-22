<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;

/**
 * The execution context for processors for "delete" action.
 */
class DeleteContext extends SingleItemContext
{
    /**
     * {@inheritdoc}
     */
    public function getAllEntities(bool $primaryOnly = false): array
    {
        $entity = $this->getResult();
        if (null === $entity) {
            return [];
        }

        return [$entity];
    }
}
