<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Provides detailed information about entities and fields.
 */
class EntityWithFieldsProvider
{
    /** @var EntityFieldProvider */
    private $fieldProvider;

    /** @var EntityProvider */
    private $entityProvider;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(
        EntityFieldProvider $fieldProvider,
        EntityProvider $entityProvider,
        ConfigManager $configManager
    ) {
        $this->fieldProvider = $fieldProvider;
        $this->entityProvider = $entityProvider;
        $this->configManager = $configManager;
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
            $entity['routes'] = $this->getAvailableRoutes($currentClassName);
        }

        return $entity;
    }

    /**
     * @param string $className
     *
     * @return array
     */
    private function getAvailableRoutes($className)
    {
        $metadata = $this->configManager->getEntityMetadata($className);

        return null !== $metadata ? $metadata->getRoutes() : [];
    }
}
