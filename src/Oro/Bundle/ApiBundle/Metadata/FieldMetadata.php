<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * The metadata for an entity field.
 */
class FieldMetadata extends PropertyMetadata
{
    private bool $nullable = false;
    private ?int $maxLength = null;

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $result = parent::toArray();
        if ($this->nullable) {
            $result['nullable'] = $this->nullable;
        }
        if (null !== $this->maxLength) {
            $result['max_length'] = $this->maxLength;
        }

        return $result;
    }

    /**
     * Indicates whether a value of the field can be NULL.
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Sets a flag indicates whether a value of the field can be NULL.
     */
    public function setIsNullable(bool $value): void
    {
        $this->nullable = $value;
    }

    /**
     * Gets the maximum length of the field data.
     */
    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    /**
     * Sets the maximum length of the field data.
     */
    public function setMaxLength(?int $maxLength): void
    {
        $this->maxLength = $maxLength;
    }
}
