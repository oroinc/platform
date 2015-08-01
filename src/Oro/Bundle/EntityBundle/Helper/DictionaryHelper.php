<?php

namespace Oro\Bundle\EntityBundle\Helper;

use Doctrine\ORM\Mapping\ClassMetadata;

use Rhumsaa\Uuid\Console\Exception;

use Symfony\Component\PropertyAccess\PropertyAccess;

class DictionaryHelper
{
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
     * @param ClassMetadata $meteData
     * @return string
     */
    public function getNameLabelField(ClassMetadata $meteData)
    {
        $fieldNames = $meteData->getFieldNames();
        $label = $this->accessor->getValue($fieldNames, '[label]');
        if ($label) {
            return 'label';
        }

        return 'name';
    }
}
