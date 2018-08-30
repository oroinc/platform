<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigHelper;

/**
 * Provides detailed information about entities and fields.
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
        $this->fieldProvider = $fieldProvider;
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
        $result = [];
        $entities = $this->entityProvider->getEntities(true, $applyExclusions, $translate);

        $this->fieldProvider->enableCaching();
        try {
            foreach ($entities as $entity) {
                $result[$entity['name']] = $this->processEntity(
                    $entity,
                    $withVirtualFields,
                    $withUnidirectional,
                    $withRelations,
                    $applyExclusions,
                    $translate,
                    $withRoutes
                );
            }
        } finally {
            $this->fieldProvider->enableCaching(false);
        }

        return $result;
    }

    /**
     * @param string $entityClass
     * @param bool   $withVirtualFields
     * @param bool   $withUnidirectional
     * @param bool   $withRelations
     * @param bool   $applyExclusions
     * @param bool   $translate
     * @param bool   $withRoutes
     *
     * @return array
     */
    public function getFieldsForEntity(
        $entityClass,
        $withVirtualFields = false,
        $withUnidirectional = false,
        $withRelations = true,
        $applyExclusions = true,
        $translate = true,
        $withRoutes = false
    ) {
        $entity = $this->entityProvider->getEnabledEntity($entityClass, $applyExclusions, $translate);
        $entity = $this->processEntity(
            $entity,
            $withVirtualFields,
            $withUnidirectional,
            $withRelations,
            $applyExclusions,
            $translate,
            $withRoutes
        );

        return $entity;
    }

    /**
     * @param array $entity
     * @param bool  $withVirtualFields
     * @param bool  $withUnidirectional
     * @param bool  $withRelations
     * @param bool  $applyExclusions
     * @param bool  $translate
     * @param bool  $withRoutes
     *
     * @return array
     */
    private function processEntity(
        $entity,
        $withVirtualFields = false,
        $withUnidirectional = false,
        $withRelations = true,
        $applyExclusions = true,
        $translate = true,
        $withRoutes = false
    ) {
        $currentClassName = $entity['name'];
        $entity['fields'] = $this->fieldProvider->getFields(
            $currentClassName,
            $withRelations,
            $withVirtualFields,
            false,
            $withUnidirectional,
            $applyExclusions,
            $translate
        );
        if ($withRoutes) {
            $entity['routes'] = $this->configHelper->getAvailableRoutes($currentClassName);
        }

        return $entity;
    }
}
