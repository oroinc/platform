<?php

namespace Oro\Bundle\ApiBundle\Processor;

/**
 * The base execution context for processors for actions work with one entity,
 * such as "get", "update", "create" and "delete".
 */
class SingleItemContext extends Context
{
    private mixed $id = null;

    /**
     * Gets an identifier of an entity.
     */
    public function getId(): mixed
    {
        return $this->id;
    }

    /**
     * Sets an identifier of an entity.
     */
    public function setId(mixed $id): void
    {
        $this->id = $id;
    }
}
