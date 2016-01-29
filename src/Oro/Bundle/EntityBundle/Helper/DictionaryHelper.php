<?php

namespace Oro\Bundle\EntityBundle\Helper;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Exception\RuntimeException;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;

class DictionaryHelper
{
    const DEFAULT_SEARCH_FIELD = 'label';
    const DEFAULT_SEARCH_FIELD_FOR_ENUM = 'name';

    const DEFAULT_REPRESENTATION_FIELD = 'label';
    const DEFAULT_REPRESENTATION_FIELD_FOR_ENUM = 'name';

    /**
     * @param ClassMetadata $metadata
     * @return mixed
     */
    public function getNamePrimaryKeyField(ClassMetadata $metadata)
    {
        $idNames = $metadata->getIdentifierFieldNames();
        if (count($idNames) === 1) {
            return $idNames[0];
        }

        throw new RuntimeException(
            sprintf('Primary key for entity %s is absent or contains more than one field', $metadata->getName())
        );
    }

    /**
     * @param ClassMetadata $doctrineMetadata
     * @param EntityMetadata|null $entityMetadata
     *
     * @return array
     */
    public function getSearchFields(ClassMetadata $doctrineMetadata, EntityMetadata $entityMetadata = null)
    {
        if ($entityMetadata && isset($entityMetadata->defaultValues['dictionary']['search_fields'])) {
            $searchFields = $entityMetadata->defaultValues['dictionary']['search_fields'];
            if ($searchFields) {
                return $searchFields;
            }
        }

        $fieldNames = $doctrineMetadata->getFieldNames();
        if (in_array(self::DEFAULT_SEARCH_FIELD, $fieldNames)) {
            return [self::DEFAULT_SEARCH_FIELD];
        }

        if (in_array(self::DEFAULT_SEARCH_FIELD_FOR_ENUM, $fieldNames)) {
            return [self::DEFAULT_SEARCH_FIELD_FOR_ENUM];
        }

        throw new \LogicException(
            sprintf('Search fields are not configured for class %s', $doctrineMetadata->getName())
        );
    }

    /**
     * @param ClassMetadata  $doctrineMetadata
     * @param EntityMetadata|null $entityMetadata
     *
     * @return string|null
     */
    public function getRepresentationField(ClassMetadata $doctrineMetadata, EntityMetadata $entityMetadata = null)
    {
        $fieldNames = $doctrineMetadata->getFieldNames();
        if ($entityMetadata && isset($entityMetadata->defaultValues['dictionary']['representation_field'])) {
            $representationField = $entityMetadata->defaultValues['dictionary']['representation_field'];
            if (in_array($representationField, $fieldNames)) {
                return $representationField;
            }
        }

        if (in_array(self::DEFAULT_REPRESENTATION_FIELD, $fieldNames)) {
            return self::DEFAULT_REPRESENTATION_FIELD;
        }

        if (in_array(self::DEFAULT_REPRESENTATION_FIELD_FOR_ENUM, $fieldNames)) {
            return self::DEFAULT_REPRESENTATION_FIELD_FOR_ENUM;
        }

        return null;
    }
}
