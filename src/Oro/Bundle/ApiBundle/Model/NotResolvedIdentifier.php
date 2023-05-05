<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * Represents an identifier that cannot be resolved.
 */
class NotResolvedIdentifier
{
    private mixed $value;
    private string $entityClass;

    public function __construct(mixed $value, string $entityClass)
    {
        $this->value = $value;
        $this->entityClass = $entityClass;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}
