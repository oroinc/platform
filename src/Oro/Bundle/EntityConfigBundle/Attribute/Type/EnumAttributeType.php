<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Provides metadata about enum attribute type.
 */
class EnumAttributeType implements AttributeTypeInterface
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
    public function getSearchableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null)
    {
        if ($originalValue === null) {
            return null;
        }

        $this->ensureSupportedType($originalValue);

        /** @var EnumOptionInterface $originalValue */
        return $originalValue->getName();
    }

    /**
     * Enum is uses array representation as in general it may combine multiple values
     *
     */
    #[\Override]
    public function getFilterableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null)
    {
        if ($originalValue === null) {
            return [];
        }

        /** @var EnumOptionInterface $originalValue */
        $this->ensureSupportedType($originalValue);

        $key = sprintf('%s_enum.%s', $attribute->getFieldName(), $originalValue->getInternalId());

        return [$key => 1];
    }

    #[\Override]
    public function getSortableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null)
    {
        if ($originalValue === null) {
            return null;
        }

        $this->ensureSupportedType($originalValue);

        /** @var EnumOptionInterface $originalValue */
        return $originalValue->getPriority();
    }

    /**
     * @param mixed $originalValue
     * @throws \InvalidArgumentException
     */
    protected function ensureSupportedType($originalValue)
    {
        if (!$originalValue instanceof EnumOptionInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    EnumOptionInterface::class,
                    is_object($originalValue) ? get_class($originalValue) : gettype($originalValue)
                )
            );
        }
    }
}
