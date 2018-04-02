<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Component\ChainProcessor\ToArrayInterface;

/**
 * The metadata for an entity field.
 */
class FieldMetadata extends PropertyMetadata implements ToArrayInterface
{
    /** @var bool */
    private $nullable = false;

    /** @var int|null */
    private $maxLength;

    /**
     * Gets a native PHP array representation of the object.
     *
     * @return array [key => value, ...]
     */
    public function toArray()
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
     * Whether a value of the field can be NULL.
     *
     * @return bool
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * Sets a flag indicates whether a value of the field can be NULL.
     *
     * @param bool $value
     */
    public function setIsNullable($value)
    {
        $this->nullable = $value;
    }

    /**
     * Gets the maximum length of the field data.
     *
     * @return int|null
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * Sets the maximum length of the field data.
     *
     * @param int|null $maxLength
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;
    }
}
