<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * The metadata that represents a link which URL is stored in a specified property of an entity.
 */
class PropertyLinkMetadata extends LinkMetadata
{
    private string $propertyPath;

    public function __construct(string $propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    #[\Override]
    public function toArray(): array
    {
        $result = parent::toArray();
        $result['property_path'] = $this->propertyPath;

        return $result;
    }

    #[\Override]
    public function getHref(DataAccessorInterface $dataAccessor): ?string
    {
        $value = null;
        if (!$dataAccessor->tryGetValue($this->propertyPath, $value)) {
            return null;
        }
        if (!$value) {
            return null;
        }

        return $value;
    }
}
