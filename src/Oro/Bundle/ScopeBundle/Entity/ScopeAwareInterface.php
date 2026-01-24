<?php

namespace Oro\Bundle\ScopeBundle\Entity;

/**
 * Defines the contract for entities that are associated with a single scope.
 *
 * Classes implementing this interface represent entities that operate within a specific
 * scope context. The scope defines the boundaries and context in which the entity exists,
 * such as organization, website, or customer scope. This interface allows the scope
 * management system to identify and work with scope-aware entities uniformly.
 */
interface ScopeAwareInterface
{
    /**
     * @return Scope
     */
    public function getScope();
}
