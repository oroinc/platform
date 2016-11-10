<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

abstract class PropertyMetadata extends ParameterBag
{
    /** @var string */
    private $name;

    /** @var string */
    private $propertyPath;

    /** @var string */
    private $dataType;

    /**
     * PropertyMetadata constructor.
     *
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
        if ($this->propertyPath) {
            $result['property_path'] = $this->propertyPath;
        }
        if ($this->dataType) {
            $result['data_type'] = $this->dataType;
        }

        return $result;
    }

    /**
     * Gets the name of a property.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of a property.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the name of a property in the source entity.
     *
     * @return string The property path or NULL if the property is not mapped.
     */
    public function getPropertyPath()
    {
        if (null === $this->propertyPath) {
            return $this->name;
        }

        return ConfigUtil::IGNORE_PROPERTY_PATH !== $this->propertyPath
            ? $this->propertyPath
            : null;
    }

    /**
     * Sets the name of a property in the source entity.
     *
     * @param string|null $propertyPath The property path,
     *                                  NULL if the property path equals to name
     *                                  or "_" (ConfigUtil::IGNORE_PROPERTY_PATH) if the property is not mapped.
     */
    public function setPropertyPath($propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    /**
     * Gets the data-type of a property.
     *
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * Sets the data-type of a property.
     *
     * @param string $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }
}
