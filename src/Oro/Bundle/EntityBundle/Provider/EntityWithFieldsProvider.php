<?php

namespace Oro\Bundle\EntityBundle\Provider;

/**
 * TODO: passing parameter $applyExclusions into getFields method should be refactored
 */
class EntityWithFieldsProvider
{
    /** @var EntityFieldProvider */
    protected $fieldProvider;

    /** @var EntityProvider */
    protected $entityProvider;

    /**
     * @param EntityFieldProvider $fieldProvider
     * @param EntityProvider      $entityProvider
     */
    public function __construct(EntityFieldProvider $fieldProvider, EntityProvider $entityProvider)
    {
        $this->fieldProvider  = $fieldProvider;
        $this->entityProvider = $entityProvider;
    }

    /**
     * @param bool $withVirtualFields
     * @param bool $withUnidirectional
     * @param bool $withRelations
     * @param bool $applyExclusions
     * @param bool $translate
     *
     * @return array
     */
    public function getFields(
        $withVirtualFields = false,
        $withUnidirectional = false,
        $withRelations = true,
        $applyExclusions = true,
        $translate = true
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

            $entityData['fields']      = $fields;
            $result[$currentClassName] = $entityData;
        }

        return $result;
    }
}
