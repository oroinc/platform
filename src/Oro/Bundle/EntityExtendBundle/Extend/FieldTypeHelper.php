<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\Configuration\EntityExtendConfigurationProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The helper class that provides methods related to extended field types.
 */
class FieldTypeHelper
{
    /** @var EntityExtendConfigurationProvider */
    private $configurationProvider;

    public function __construct(EntityExtendConfigurationProvider $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * Check if given form type is relation
     *
     * @param string $type
     *
     * @return bool
     */
    public static function isRelation($type)
    {
        return in_array(
            $type,
            [
                RelationType::TO_ONE,
                RelationType::TO_MANY,
                RelationType::ONE_TO_ONE,
                RelationType::ONE_TO_MANY,
                RelationType::MANY_TO_ONE,
                RelationType::MANY_TO_MANY
            ],
            true
        );
    }

    public function getUnderlyingType(?string $type, ?ConfigInterface $fieldConfig = null): ?string
    {
        if (null !== $fieldConfig && ExtendHelper::isEnumerableType($type)) {
            return $this->getEnumUnderlyingType($fieldConfig);
        }
        $underlyingTypes = $this->configurationProvider->getUnderlyingTypes();

        return $underlyingTypes[$type] ?? $type;
    }


    /**
     * Needed for oro platform update with enums based on relations.
     */
    protected function getEnumUnderlyingType(ConfigInterface $fieldConfig): string
    {
        $fieldType = $fieldConfig->getId()->getFieldType();
        if ($fieldConfig->is('is_serialized')) {
            return $fieldType;
        }

        return self::matchEnumUnderlyingType($fieldType);
    }

    public static function matchEnumUnderlyingType(string $type): string
    {
        return match ($type) {
            'enum' => RelationType::MANY_TO_ONE,
            'multiEnum' => RelationType::MANY_TO_MANY,
            default => $type
        };
    }
}
