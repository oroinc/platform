<?php

namespace Oro\Bundle\EntityBundle\Provider;

class EntityWithFieldsProvider
{
    /** @var EntityFieldRecursiveProvider */
    protected $fieldRecursiveProvider;

    public function __construct(EntityFieldRecursiveProvider $fieldRecursiveProvider)
    {
        $this->fieldRecursiveProvider = $fieldRecursiveProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields(
        $withVirtualFields = false,
        $withEntityDetails = false,
        $withUnidirectional = false,
        $lastDeepLevelRelations = false,
        $translate = true
    ) {
        $result        = [];
        $withRelations = true;
        $deepLevel     = 0;

        $entities = $this->fieldRecursiveProvider
            ->getEntityProvider()
            ->getEntities();

        foreach ($entities as $entityData) {
            $currentClassName = $entityData['name'];

            $fields = $this->fieldRecursiveProvider->getFields(
                $currentClassName,
                $withRelations,
                $withVirtualFields,
                $withEntityDetails,
                $withUnidirectional,
                $deepLevel,
                $lastDeepLevelRelations,
                $translate
            );

            $result[$currentClassName] = $entityData;
            $result[$currentClassName]['fields'] = $fields;
        }

        return $result;
    }
}
