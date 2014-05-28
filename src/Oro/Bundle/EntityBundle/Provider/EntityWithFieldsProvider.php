<?php

namespace Oro\Bundle\EntityBundle\Provider;

/**
 * TODO: passing parameter $withExclusions into getFields method should be refactored
 */
class EntityWithFieldsProvider
{
    /** @var EntityFieldProvider */
    protected $fieldProvider;

    /** @var EntityProvider */
    protected $entityProvider;

    public function __construct(EntityFieldProvider $fieldProvider, EntityProvider $entityProvider)
    {
        $this->fieldProvider  = $fieldProvider;
        $this->entityProvider = $entityProvider;
    }

    /**
     * @param bool $withVirtualFields
     * @param bool $withUnidirectional
     * @param bool $withRelations
     * @param bool $withExclusions
     * @param bool $translate
     *
     * @return array
     */
    public function getFields(
        $withVirtualFields = false,
        $withUnidirectional = false,
        $withRelations = true,
        $withExclusions = true,
        $translate = true
    ) {
        $result   = [];
        $entities = $this->entityProvider->getEntities(true, $withExclusions);
        foreach ($entities as $entityData) {
            $currentClassName = $entityData['name'];

            $fields = $this->fieldProvider->getFields(
                $currentClassName,
                $withRelations,
                $withVirtualFields,
                false,
                $withUnidirectional,
                $withExclusions,
                $translate
            );

            $result[$currentClassName]           = $entityData;
            $result[$currentClassName]['fields'] = $fields;
        }

        return $result;
    }
}
