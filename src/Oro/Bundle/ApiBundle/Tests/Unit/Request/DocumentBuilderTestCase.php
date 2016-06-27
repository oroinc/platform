<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;

class DocumentBuilderTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string   $class
     * @param string[] $idFields
     *
     * @return EntityMetadata
     */
    protected function getEntityMetadata($class, array $idFields)
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName($class);
        $metadata->setIdentifierFieldNames($idFields);

        return $metadata;
    }

    /**
     * @param string $fieldName
     *
     * @return FieldMetadata
     */
    protected function createFieldMetadata($fieldName)
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);

        return $fieldMetadata;
    }

    /**
     * @param string $associationName
     * @param string $targetClass
     * @param bool   $isCollection
     *
     * @return AssociationMetadata
     */
    protected function createAssociationMetadata($associationName, $targetClass, $isCollection = false)
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setAcceptableTargetClassNames([$targetClass]);
        $associationMetadata->setIsCollection($isCollection);
        $associationMetadata->setTargetMetadata($this->getEntityMetadata($targetClass, ['id']));
        $associationMetadata->getTargetMetadata()->addField($this->createFieldMetadata('id'));

        return $associationMetadata;
    }
}
