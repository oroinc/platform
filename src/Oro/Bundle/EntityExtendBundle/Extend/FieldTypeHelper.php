<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

use Oro\Bundle\EntityExtendBundle\Configuration\EntityExtendConfigurationProvider;

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

    /**
     * @param string $type
     *
     * @return string
     */
    public function getUnderlyingType($type)
    {
        $underlyingTypes = $this->configurationProvider->getUnderlyingTypes();

        return $underlyingTypes[$type] ?? $type;
    }
}
