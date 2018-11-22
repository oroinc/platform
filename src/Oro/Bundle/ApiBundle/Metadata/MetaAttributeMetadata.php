<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Component\ChainProcessor\ToArrayInterface;

/**
 * The metadata for an attribute that can be used to provide some meta information about
 * such elements as associations or links.
 */
class MetaAttributeMetadata implements ToArrayInterface
{
    /** @var string */
    private $name;

    /** @var string */
    private $propertyPath;

    /** @var string */
    private $dataType;

    /**
     * @param string      $name         The name of the meta property in the result data.
     * @param string|null $dataType     The data-type of the meta property.
     * @param string|null $propertyPath The property path.
     *                                  It can starts with "_." to get access to an entity data.
     *                                  The "__type__" property can be used to get an entity type.
     *                                  The "__class__" property can be used to get an entity class.
     *                                  The "__id__" property can be used to get an entity identifier.
     *                                  See {@see \Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface}.
     */
    public function __construct(string $name, string $dataType = null, string $propertyPath = null)
    {
        $this->name = $name;
        $this->dataType = $dataType;
        $this->propertyPath = $propertyPath;
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
     * Gets the name of the meta property.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the name of the meta property in source data.
     *
     * @return string The property path.
     */
    public function getPropertyPath()
    {
        if (null === $this->propertyPath) {
            return $this->getName();
        }

        return $this->propertyPath;
    }

    /**
     * Gets the data-type of the meta property.
     *
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }
}
