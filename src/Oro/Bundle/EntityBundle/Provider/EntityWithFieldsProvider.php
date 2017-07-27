<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigHelper;

/**
 * TODO: passing parameter $applyExclusions into getFields method should be refactored
 */
class EntityWithFieldsProvider
{
    /** @var EntityFieldProvider */
    protected $fieldProvider;

    /** @var EntityProvider */
    protected $entityProvider;

    /** @var EntityConfigHelper */
    protected $configHelper;

    /**
     * @param EntityFieldProvider $fieldProvider
     * @param EntityProvider      $entityProvider
     * @param EntityConfigHelper  $configHelper
     */
    public function __construct(
        EntityFieldProvider $fieldProvider,
        EntityProvider $entityProvider,
        EntityConfigHelper $configHelper
    ) {
        $this->fieldProvider  = $fieldProvider;
        $this->entityProvider = $entityProvider;
        $this->configHelper = $configHelper;
    }

    /**
     * @param bool $withVirtualFields
     * @param bool $withUnidirectional
     * @param bool $withRelations
     * @param bool $applyExclusions
     * @param bool $translate
     * @param bool $withRoutes
     *
     * @return array
     */
    public function getFields(
        $withVirtualFields = false,
        $withUnidirectional = false,
        $withRelations = true,
        $applyExclusions = true,
        $translate = true,
        $withRoutes = false
    ) {
        $result   = [];
        $entities = $this->entityProvider->getEntities(true, $applyExclusions, $translate);
        foreach ($entities as $entityData) {
            $currentClassName = $entityData['name'];

            $fields = $this->fieldProvider->getFields(
                $currentClassName,
                $withRelations,
                $withVirtualFields,
                false,
                $withUnidirectional,
                $applyExclusions,
                $translate
            );

            if ($withRoutes) {
                $entityData['routes'] = $this->configHelper->getAvailableRoutes($currentClassName);
            }

            $entityData['fields']      = $fields;
            $result[$currentClassName] = $entityData;
        }

        return $result;
    }
}
