<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Component\ChainProcessor\ToArrayInterface;

class FieldMetadata implements ToArrayInterface
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $dataType;

    /** @var bool */
    protected $nullable = false;

    /** @var int|null */
    protected $maxLength;

    /**
     * @param string|null $name
     */
    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * Gets a native PHP array representation of the object.
     *
     * @return array [key => value, ...]
     */
    public function toArray()
    {
        $result = ['name' => $this->name];
        if ($this->dataType) {
            $result['data_type'] = $this->dataType;
        }
        if ($this->nullable) {
            $result['nullable'] = $this->nullable;
        }
        if (null !== $this->maxLength) {
            $result['max_length'] = $this->maxLength;
        }

        return $result;
    }

    /**
     * Gets the name of the field.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of the field.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the data-type of the field.
     *
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * Sets the data-type of the field.
     *
     * @param string $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
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
