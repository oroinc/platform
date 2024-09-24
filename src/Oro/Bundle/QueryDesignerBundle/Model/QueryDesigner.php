<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

/**
 * Represents a simple query designer object.
 */
final class QueryDesigner extends AbstractQueryDesigner
{
    /** @var string|null */
    private $entity;

    /** @var string|null */
    private $definition;

    public function __construct(string $entity = null, string $definition = null)
    {
        $this->entity = $entity;
        $this->definition = $definition;
    }

    #[\Override]
    public function getEntity()
    {
        return $this->entity;
    }

    #[\Override]
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    #[\Override]
    public function getDefinition()
    {
        return $this->definition;
    }

    #[\Override]
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }
}
