<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Provides metadata about decimal attribute type.
 */
class DecimalAttributeType implements AttributeTypeInterface
{
    #[\Override]
    public function isSearchable(FieldConfigModel $attribute)
    {
        return false;
    }

    #[\Override]
    public function isFilterable(FieldConfigModel $attribute)
    {
        return true;
    }

    #[\Override]
    public function isSortable(FieldConfigModel $attribute)
    {
        return true;
    }

    #[\Override]
    public function getSearchableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        throw new \RuntimeException('Not supported');
    }

    #[\Override]
    public function getFilterableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        return ($originalValue === null) ? $originalValue : (float)$originalValue;
    }

    #[\Override]
    public function getSortableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        return $this->getFilterableValue($attribute, $originalValue, $localization);
    }
}
