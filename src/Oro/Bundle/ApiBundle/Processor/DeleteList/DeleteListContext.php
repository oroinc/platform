<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Processor\ListContext;

/**
 * The execution context for processors for "delete_list" action.
 */
class DeleteListContext extends ListContext
{
    /**
     * {@inheritdoc}
     */
    public function getAllEntities(bool $primaryOnly = false): array
    {
        $entities = $this->getResult();
        if (null === $entities) {
            return [];
        }

        return $entities;
    }
}
