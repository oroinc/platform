<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Symfony\Component\HttpFoundation\ParameterBag;

class EntityFieldListProvider extends EntityFieldRecursiveProvider
{
    /** @var string|null */
    protected $currentClassName = null;

    /**
     * {@inheritdoc}
     */
    public function isApplied(ParameterBag $parameters)
    {
        $entityName  = str_replace('_', '\\', $parameters->get('entityName'));
        $isPlainList = ('1' === $parameters->get('plain-list'));

        return !empty($entityName) && $isPlainList;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields(
        $entityName,
        $withRelations = false,
        $withVirtualFields = false,
        $withEntityDetails = false,
        $withUnidirectional = false,
        $deepLevel = 0,
        $lastDeepLevelRelations = false,
        $translate = true
    ) {
        $result = [];
        // force some params
        $withRelations = true;
        $deepLevel = 0;

        $entities = $this->entityProvider->getEntities();
        foreach ($entities as $entityData) {
            $currentClassName = $entityData['name'];

            $fields = parent::getFields(
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
