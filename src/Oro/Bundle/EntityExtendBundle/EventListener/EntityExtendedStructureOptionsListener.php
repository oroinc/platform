<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Adds the relation type for fields that represent associations.
 */
class EntityExtendedStructureOptionsListener
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param EntityStructureOptionsEvent $event
     */
    public function onOptionsRequest(EntityStructureOptionsEvent $event)
    {
        $data = $event->getData();
        foreach ($data as $entityStructure) {
            $className = $entityStructure->getClassName();
            $fields = $entityStructure->getFields();
            foreach ($fields as $field) {
                $relationType = $this->getRelationType($className, $field->getName());
                $field->setRelationType($relationType ?: lcfirst($field->getRelationType()));
            }
        }
        $event->setData($data);
    }

    /**
     * Determines which kind of relation is used. Generally used to convert 'ref-one' and 'ref-many' to real relations.
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return null|string
     */
    private function getRelationType($className, $fieldName)
    {
        //Process field that represents unidirectional relation from other entity to the current.
        //For example "Oro\Bundle\AccountBundle\Entity\Account::referredBy"
        $data = explode('::', $fieldName);
        if (count($data) === 2) {
            list($className, $fieldName) = $data;
        }

        if (!$this->doctrineHelper->isManageableEntity($className)) {
            return null;
        }

        $metadata = $this->doctrineHelper->getEntityMetadata($className);
        if ($metadata->hasAssociation($fieldName)) {
            $fieldInfo = $metadata->getAssociationMapping($fieldName);
            switch ($fieldInfo['type']) {
                case ClassMetadataInfo::ONE_TO_ONE:
                    return RelationType::ONE_TO_ONE;
                case ClassMetadataInfo::MANY_TO_ONE:
                    return RelationType::MANY_TO_ONE;
                case ClassMetadataInfo::ONE_TO_MANY:
                    return RelationType::ONE_TO_MANY;
                case ClassMetadataInfo::MANY_TO_MANY:
                    return RelationType::MANY_TO_MANY;
            }
        }

        return null;
    }
}
