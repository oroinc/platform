<?php

namespace Oro\Bundle\EntityBundle\Helper;

use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityBundle\Exception\RuntimeException;

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
            throw new RuntimeException('Primary key for this entity is absent or contains few fields');
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
        if (in_array('label', $fieldNames)) {
            return 'label';
        }

        return 'name';
    }
}
