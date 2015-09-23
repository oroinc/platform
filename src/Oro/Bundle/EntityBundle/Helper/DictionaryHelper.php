<?php

namespace Oro\Bundle\EntityBundle\Helper;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Rhumsaa\Uuid\Console\Exception;

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
        if (count($idNames) !== 1) {
            throw new Exception('Primary key for this entity is absent or contains few fields');
        }

        return $idNames[0];
    }

    /**
     * @param ClassMetadata  $doctrineMetadata
     * @param EntityMetadata $entityMetadata
     *
     * @return string
     * @throws \LogicException
     */
    public function getNameLabelField(ClassMetadata $doctrineMetadata, EntityMetadata $entityMetadata)
    {
        $fieldNames = $doctrineMetadata->getFieldNames();

        if (isset($entityMetadata->defaultValues['dictionary']['search_field'])) {
            $fieldName = $entityMetadata->defaultValues['dictionary']['search_field'];
            if (in_array($fieldName, $fieldNames)) {
                return $fieldName;
            }
        }

        if (in_array(self::DEFAULT_SEARCH_FIELD, $fieldNames)) {
            return self::DEFAULT_SEARCH_FIELD;
        }

        throw new \LogicException(sprintf('Value field is not configured for class %s', $doctrineMetadata->getName()));
    }
}
