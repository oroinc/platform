<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Provides metadata about date attribute type.
 */
class DateAttributeType implements AttributeTypeInterface
{
    #[\Override]
    public function isSearchable(FieldConfigModel $attribute)
    {
        return false;
    }

    #[\Override]
    public function isFilterable(FieldConfigModel $attribute)
    {
        return false;
    }

    #[\Override]
    public function isSortable(FieldConfigModel $attribute)
    {
        return true;
    }

    #[\Override]
    public function getSearchableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null)
    {
        throw new \RuntimeException('Not supported');
    }

    #[\Override]
    public function getFilterableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null)
    {
        throw new \RuntimeException('Not supported');
    }

    #[\Override]
    public function getSortableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null)
    {
        if ($originalValue === null) {
            return null;
        }

        if (!$originalValue instanceof \DateTime) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    \DateTime::class,
                    is_object($originalValue) ? get_class($originalValue) : gettype($originalValue)
                )
            );
        }

        return clone $originalValue;
    }
}
