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

    /**
     * {@inheritdoc}
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }
}
