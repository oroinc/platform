<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * A collection of Scope entities that uses ScopeCriteria object as a key for Scope entity.
 */
class ScopeCollection
{
    /** @var Scope[] [criteria key => scope, ...] */
    private $scopes = [];

    public function get(ScopeCriteria $criteria): ?Scope
    {
        return $this->scopes[$criteria->getIdentifier()] ?? null;
    }

    /**
     * @return Scope[]
     */
    public function getAll(): array
    {
        return array_values($this->scopes);
    }

    public function add(Scope $scope, ScopeCriteria $criteria): void
    {
        $this->scopes[$criteria->getIdentifier()] = $scope;
    }

    public function remove(ScopeCriteria $criteria): void
    {
        unset($this->scopes[$criteria->getIdentifier()]);
    }

    public function isEmpty(): bool
    {
        return empty($this->scopes);
    }

    public function clear(): void
    {
        $this->scopes = [];
    }
}
