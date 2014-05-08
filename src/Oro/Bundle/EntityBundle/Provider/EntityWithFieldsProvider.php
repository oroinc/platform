<?php

namespace Oro\Bundle\EntityBundle\Provider;

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
     * {@inheritdoc}
     */
    public function getFields(
        $withVirtualFields = false,
        $withEntityDetails = false,
        $withUnidirectional = false,
        $translate = true
    ) {
        $result        = [];
        $withRelations = true;

        $entities = $this->entityProvider->getEntities();

        foreach ($entities as $entityData) {
            $currentClassName = $entityData['name'];

            $fields = $this->fieldProvider->getFields(
                $currentClassName,
                $withRelations,
                $withVirtualFields,
                $withEntityDetails,
                $withUnidirectional,
                $translate
            );

            $result[$currentClassName] = $entityData;
            $result[$currentClassName]['fields'] = $fields;
        }

        return $result;
    }
}
