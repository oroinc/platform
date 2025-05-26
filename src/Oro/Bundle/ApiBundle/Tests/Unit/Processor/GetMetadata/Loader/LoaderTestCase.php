<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoaderTestCase extends TestCase
{
    protected function getClassMetadataMock(?string $className = null): ClassMetadata&MockObject
    {
        if ($className) {
            $classMetadata = $this->getMockBuilder(ClassMetadata::class)
                ->setConstructorArgs([$className])
                ->getMock();
        } else {
            $classMetadata = $this->createMock(ClassMetadata::class);
        }
        $classMetadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_NONE;
        $classMetadata->expects(self::any())
            ->method('isInheritanceTypeNone')
            ->willReturnCallback(function () use ($classMetadata) {
                return ClassMetadata::INHERITANCE_TYPE_NONE === $classMetadata->inheritanceType;
            });

        return $classMetadata;
    }

    protected function createMetaPropertyMetadata(string $fieldName, string $dataType): MetaPropertyMetadata
    {
        return new MetaPropertyMetadata($fieldName, $dataType);
    }

    protected function createFieldMetadata(string $fieldName, string $dataType): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);
        $fieldMetadata->setDataType($dataType);
        $fieldMetadata->setIsNullable(false);

        return $fieldMetadata;
    }

    protected function createAssociationMetadata(
        string $associationName,
        string $targetClass,
        string $associationType,
        bool $isCollection,
        ?string $dataType,
        array $acceptableTargetClasses,
        bool $collapsed = false
    ): AssociationMetadata {
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
