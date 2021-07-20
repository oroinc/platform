<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * Represents an identifier that cannot be resolved.
 */
class NotResolvedIdentifier
{
    /** @var mixed */
    private $value;

    /** @var string */
    private $entityClass;

    /**
     * @param mixed  $value
     * @param string $entityClass
     */
    public function __construct($value, string $entityClass)
    {
        $this->value = $value;
        $this->entityClass = $entityClass;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}
