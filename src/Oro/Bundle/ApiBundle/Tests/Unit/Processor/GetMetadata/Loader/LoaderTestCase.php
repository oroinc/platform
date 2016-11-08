<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;

class LoaderTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string|null $className
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ClassMetadata
     */
    protected function getClassMetadataMock($className = null)
    {
        if ($className) {
            $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
                ->setConstructorArgs([$className])
                ->getMock();
        } else {
            $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
                ->disableOriginalConstructor()
                ->getMock();
        }
        $classMetadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_NONE;

        return $classMetadata;
    }

    /**
     * @param string $fieldName
     * @param string $dataType
     *
     * @return MetaPropertyMetadata
     */
    protected function createMetaPropertyMetadata($fieldName, $dataType)
    {
        $metaPropertyMetadata = new MetaPropertyMetadata();
        $metaPropertyMetadata->setName($fieldName);
        $metaPropertyMetadata->setDataType($dataType);

        return $metaPropertyMetadata;
    }

    /**
     * @param string $fieldName
     * @param string $dataType
     *
     * @return FieldMetadata
     */
    protected function createFieldMetadata($fieldName, $dataType)
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);
        $fieldMetadata->setDataType($dataType);
        $fieldMetadata->setIsNullable(false);

        return $fieldMetadata;
    }

    /**
     * @param string   $associationName
     * @param string   $targetClass
     * @param string   $associationType
     * @param bool     $isCollection
     * @param string   $dataType
     * @param string[] $acceptableTargetClasses
     * @param bool     $collapsed
     *
     * @return AssociationMetadata
     */
    protected function createAssociationMetadata(
        $associationName,
        $targetClass,
        $associationType,
        $isCollection,
        $dataType,
        array $acceptableTargetClasses,
        $collapsed = false
    ) {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setAssociationType($associationType);
        $associationMetadata->setIsCollection($isCollection);
        $associationMetadata->setDataType($dataType);
        $associationMetadata->setAcceptableTargetClassNames($acceptableTargetClasses);
        $associationMetadata->setIsNullable(true);
        $associationMetadata->setCollapsed($collapsed);

        return $associationMetadata;
    }
}
