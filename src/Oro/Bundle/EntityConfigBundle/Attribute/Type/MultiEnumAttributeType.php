<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Provides metadata about multi-enum attribute type.
 */
class MultiEnumAttributeType extends EnumAttributeType
{
    /**
     * {@inheritdoc}
     */
    public function isSortable(FieldConfigModel $attribute)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        $this->ensureTraversable($originalValue);

        $values = [];
        foreach ($originalValue as $enum) {
            $values[] = parent::getSearchableValue($attribute, $enum, $localization);
        }

        return implode(' ', $values);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        $this->ensureTraversable($originalValue);

        $value = [];

        /** @var AbstractEnumValue[] $originalValue */
        foreach ($originalValue as $enum) {
            $this->ensureSupportedType($enum);

            $key = sprintf('%s_enum.%s', $attribute->getFieldName(), $enum->getId());

            $value[$key] = 1;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function ensureTraversable($originalValue)
    {
        if (!\is_array($originalValue) && !$originalValue instanceof \Traversable) {
            throw new \InvalidArgumentException(\sprintf(
                'Value must be an array or Traversable, [%s] given',
                get_debug_type($originalValue)
            ));
        }
    }
}
