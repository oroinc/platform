<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Adds the relation type for fields that represent associations.
 */
class EntityExtendedStructureOptionsListener
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function onOptionsRequest(EntityStructureOptionsEvent $event): void
    {
        $data = $event->getData();
        foreach ($data as $entityStructure) {
            $className = $entityStructure->getClassName();
            $fields = $entityStructure->getFields();
            foreach ($fields as $field) {
                $field->setRelationType($this->getRelationType($className, $field));
            }
        }
        $event->setData($data);
    }

    /**
     * Determines which kind of relation is used. Generally used to convert 'ref-one' and 'ref-many' to real relations.
     */
    private function getRelationType(string $className, EntityFieldStructure $field): ?string
    {
        $fieldName = $field->getName();
        //Process field that represents unidirectional relation from other entity to the current.
        //For example "Oro\Bundle\AccountBundle\Entity\Account::referredBy"
        $data = explode('::', $fieldName);
        if (\count($data) === 2) {
            [$className, $fieldName] = $data;
        }

        if ($this->doctrineHelper->isManageableEntity($className)) {
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($className);
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
        }

        $fieldRelationType = $field->getRelationType();
        if ($fieldRelationType) {
            return lcfirst($fieldRelationType);
        }

        return null;
    }
}
