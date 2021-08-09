<?php

namespace Oro\Bundle\EntityConfigBundle\Metadata;

/**
 * Represents an entity field metadata for configurable entities.
 */
final class FieldMetadata implements \Serializable
{
    public string $class;
    public string $name;
    public ?string $mode = null;
    public ?array $defaultValues = null;

    public function __construct(string $class, string $name)
    {
        $this->class = $class;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            $this->class,
            $this->name,
            $this->mode,
            $this->defaultValues
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        [
            $this->class,
            $this->name,
            $this->mode,
            $this->defaultValues
        ] = unserialize($str, ['allowed_classes' => false]);
    }
}
