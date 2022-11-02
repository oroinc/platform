<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

/**
 * A collection of query components.
 */
class QueryComponentCollection
{
    /** @var QueryComponent[] [alias => QueryComponent, ...] */
    private $elements = [];

    /**
     * Gets a native PHP array representation of the collection.
     */
    public function toArray(): array
    {
        return $this->elements;
    }

    public function has(string $alias): bool
    {
        return isset($this->elements[$alias]);
    }

    public function get(string $alias): QueryComponent
    {
        return $this->elements[$alias];
    }

    public function add(string $alias, QueryComponent $queryComponent): void
    {
        if (isset($this->elements[$alias])) {
            throw new \LogicException(\sprintf('The query component for the alias "%s" already exists.', $alias));
        }

        $this->elements[$alias] = $queryComponent;
    }
}
