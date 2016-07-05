<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Component\ChainProcessor\ToArrayInterface;

class MetaPropertyMetadata implements ToArrayInterface
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $dataType;

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

        return $result;
    }

    /**
     * Gets the name of the property.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of the property.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the data-type of the property.
     *
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * Sets the data-type of the property.
     *
     * @param string $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }
}
