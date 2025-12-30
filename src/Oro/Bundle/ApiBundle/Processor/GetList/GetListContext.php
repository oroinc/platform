<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Bundle\ApiBundle\Processor\ListContext;

/**
 * The execution context for processors for "get_list" action.
 */
class GetListContext extends ListContext
{
    /** a callback that can be used to initialize the Criteria object */
    private const INITIALIZE_CRITERIA_CALLBACK = 'initializeCriteriaCallback';

    /**
     * Gets a callback that can be used to initialize the Criteria object.
     *
     * @return callable|null function (Criteria $criteria): void
     */
    public function getInitializeCriteriaCallback(): ?callable
    {
        return $this->get(self::INITIALIZE_CRITERIA_CALLBACK);
    }

    /**
     * Sets a callback that can be used to initialize the Criteria object.
     *
     * @param callable|null $initializeCriteria function (Criteria $criteria): void
     */
    public function setInitializeCriteriaCallback(?callable $initializeCriteria): void
    {
        $this->set(self::INITIALIZE_CRITERIA_CALLBACK, $initializeCriteria);
    }
}
