<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Entity attribute type for enum field type
 */
class EnumAttributeType implements AttributeTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'enum';
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable(FieldConfigModel $attribute = null)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(FieldConfigModel $attribute = null)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isSortable(FieldConfigModel $attribute = null)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        if ($originalValue === null) {
            return null;
        }

        $this->ensureSupportedType($originalValue);

        /** @var AbstractEnumValue $originalValue */
        return $originalValue->getName();
    }

    /**
     * Enum is uses array representation as in general it may combine multiple values
     *
     * {@inheritdoc}
     */
    public function getFilterableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        if ($originalValue === null) {
            return [];
        }

        /** @var AbstractEnumValue $originalValue */
        $this->ensureSupportedType($originalValue);

        $key = sprintf('%s_%s', $attribute->getFieldName(), $originalValue->getId());

        return [$key => 1];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        if ($originalValue === null) {
            return null;
        }

        $this->ensureSupportedType($originalValue);

        /** @var AbstractEnumValue $originalValue */
        return $originalValue->getPriority();
    }

    /**
     * @param mixed $originalValue
     * @throws \InvalidArgumentException
     */
    protected function ensureSupportedType($originalValue)
    {
        if (!$originalValue instanceof AbstractEnumValue) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    AbstractEnumValue::class,
                    is_object($originalValue) ? get_class($originalValue) : gettype($originalValue)
                )
            );
        }
    }
}
