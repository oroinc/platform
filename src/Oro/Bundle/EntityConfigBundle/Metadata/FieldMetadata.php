<?php

namespace Oro\Bundle\EntityConfigBundle\Metadata;

/**
 * Represents an entity field metadata for configurable entities.
 */
final class FieldMetadata
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

    public function __serialize(): array
    {
        return [
            $this->class,
            $this->name,
            $this->mode,
            $this->defaultValues
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->class,
            $this->name,
            $this->mode,
            $this->defaultValues
        ] = $serialized;
    }
}
