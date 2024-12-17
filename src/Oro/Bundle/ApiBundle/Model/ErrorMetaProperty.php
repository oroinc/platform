<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * Represents a meta property for an error occurred when processing an API action.
 */
final class ErrorMetaProperty
{
    public function __construct(
        private mixed $value,
        private readonly string $dataType = 'string'
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }
}
