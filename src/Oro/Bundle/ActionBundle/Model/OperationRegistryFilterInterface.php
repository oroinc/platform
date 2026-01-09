<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria;

/**
 * Defines the contract for filtering operations based on search criteria.
 */
interface OperationRegistryFilterInterface
{
    /**
     * @param array|Operation[] $operations
     * @param OperationFindCriteria $findCriteria
     * @return array|Operation[] of filtered operations
     */
    public function filter(array $operations, OperationFindCriteria $findCriteria);
}
