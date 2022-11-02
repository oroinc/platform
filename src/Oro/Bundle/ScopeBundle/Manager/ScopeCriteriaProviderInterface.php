<?php

namespace Oro\Bundle\ScopeBundle\Manager;

/**
 * Represents a provider that is used to fill ScopeCriteria object.
 */
interface ScopeCriteriaProviderInterface
{
    /**
     * @return string
     */
    public function getCriteriaField();

    /**
     * @return object|null
     */
    public function getCriteriaValue();

    /**
     * @return string
     */
    public function getCriteriaValueType();
}
