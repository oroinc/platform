<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Provides metadata about boolean attribute type.
 */
class BooleanAttributeType implements AttributeTypeInterface
{
    public const TRUE_VALUE = 1;
    public const FALSE_VALUE = 0;

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
    public function getSearchableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null)
    {
        throw new \RuntimeException('Not supported');
    }

    #[\Override]
    public function getFilterableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null)
    {
        return $originalValue ? self::TRUE_VALUE : self::FALSE_VALUE;
    }

    #[\Override]
    public function getSortableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null)
    {
        return $originalValue ? self::TRUE_VALUE : self::FALSE_VALUE;
    }
}
