<?php

namespace Oro\Bundle\ScopeBundle\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * Defines the contract for entities that are associated with multiple scopes.
 *
 * Classes implementing this interface represent entities that can operate across multiple
 * scope contexts simultaneously. Unlike {@see ScopeAwareInterface} which handles a single scope,
 * this interface allows entities to maintain relationships with a collection of scopes,
 * enabling scope-specific configurations or behaviors across different contexts.
 */
interface ScopeCollectionAwareInterface
{
    /**
     * @return Collection|Scope[]
     */
    public function getScopes();
}
