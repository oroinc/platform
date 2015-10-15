<?php

namespace Oro\Bundle\EntityBundle\Helper;

use Doctrine\ORM\Mapping\ClassMetadata;

use Rhumsaa\Uuid\Console\Exception;

use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;

use Symfony\Component\PropertyAccess\PropertyAccess;

class DictionaryHelper
{
    const DEFAULT_SEARCH_FIELD = 'label';

    /** @var \Symfony\Component\PropertyAccess\PropertyAccessor */
    protected $accessor;

    public function __construct()
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

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

        throw new Exception(
            sprintf('Primary key for entity %s is absent or contains more than one field', $metadata->getName())
        );
    }

    /**
     * @param ClassMetadata  $doctrineMetadata
     * @param EntityMetadata $entityMetadata
     *
     * @return array
     * @throws \LogicException
     */
    public function getSearchFields(ClassMetadata $doctrineMetadata, EntityMetadata $entityMetadata)
    {
        if (isset($entityMetadata->defaultValues['dictionary']['search_fields'])) {
            if ($searchFields = $entityMetadata->defaultValues['dictionary']['search_fields']) {
                return $searchFields;
            }
        }

        $fieldNames = $doctrineMetadata->getFieldNames();

        if (in_array(self::DEFAULT_SEARCH_FIELD, $fieldNames)) {
            return [self::DEFAULT_SEARCH_FIELD];
        }

        throw new \LogicException(
            sprintf('Search fields are not configured for class %s', $doctrineMetadata->getName())
        );
    }

    /**
     * @param ClassMetadata  $doctrineMetadata
     * @param EntityMetadata $entityMetadata
     *
     * @return string|null
     */
    public function getRepresentationField(ClassMetadata $doctrineMetadata, EntityMetadata $entityMetadata)
    {
        if (isset($entityMetadata->defaultValues['dictionary']['representation_field'])) {
            $fieldNames = $doctrineMetadata->getFieldNames();
            $representationField = $entityMetadata->defaultValues['dictionary']['representation_field'];
            if (in_array($representationField, $fieldNames)) {
                return $representationField;
            }
        }

        return null;
    }
}
