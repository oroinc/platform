<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * The metadata for an entity property that provides some meta information about this entity.
 */
class MetaPropertyMetadata extends PropertyMetadata
{
    private ?string $resultName = null;

    /**
     * @param string|null $name         The name of the meta property in the result data.
     * @param string|null $dataType     The data-type of the meta property.
     * @param string|null $propertyPath The property path.
     *                                  It can starts with "_." to get access to an entity data.
     *                                  The "__type__" property can be used to get an entity type.
     *                                  The "__class__" property can be used to get an entity class.
     *                                  The "__id__" property can be used to get an entity identifier.
     *                                  See {@see \Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface}.
     */
    public function __construct(?string $name = null, ?string $dataType = null, ?string $propertyPath = null)
    {
        parent::__construct($name);
        if (null !== $dataType) {
            $this->setDataType($dataType);
        }
        if (null !== $propertyPath) {
            $this->setPropertyPath($propertyPath);
        }
    }

    /**
     * Gets the name by which the meta property should be returned in the response.
     */
    public function getResultName(): ?string
    {
        return $this->resultName ?? $this->getName();
    }

    /**
     * Sets the name by which the meta property should be returned in the response.
     */
    public function setResultName(?string $resultName): void
    {
        $this->resultName = $resultName;
    }
}
