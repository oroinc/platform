<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Provides metadata about string attribute type.
 */
class StringAttributeType implements AttributeTypeInterface
{
    #[\Override]
    public function isSearchable(FieldConfigModel $attribute)
    {
        return true;
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
        return $this->getFilterableValue($attribute, $originalValue, $localization);
    }

    #[\Override]
    public function getFilterableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        return (string)$originalValue;
    }

    #[\Override]
    public function getSortableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        return $this->getFilterableValue($attribute, $originalValue, $localization);
    }
}
