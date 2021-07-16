<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;

/**
 * Provider to get field type by name or check association
 * Used in converters to filter change sets
 */
class AuditFieldTypeProvider
{
    /**
     * @param ClassMetadata $entityMetadata
     * @param string $fieldName
     * @return string
     */
    public function getFieldType(ClassMetadata $entityMetadata, string $fieldName)
    {
        if ($entityMetadata->hasField($fieldName)) {
            $fieldType = $entityMetadata->getTypeOfField($fieldName);
            if ($fieldType instanceof Type) {
                return $fieldType->getName();
            }

            return $fieldType;
        }

        if ($this->isAssociation($entityMetadata, $fieldName)) {
            return AuditFieldTypeRegistry::COLLECTION_TYPE;
        }

        return AuditFieldTypeRegistry::TYPE_STRING;
    }

    public function isAssociation(ClassMetadata $classMetadata, string $fieldName): bool
    {
        if ($classMetadata->hasAssociation($fieldName)) {
            return true;
        }

        if (UnidirectionalFieldHelper::isFieldUnidirectional($fieldName)) {
            return true;
        }

        return false;
    }
}
