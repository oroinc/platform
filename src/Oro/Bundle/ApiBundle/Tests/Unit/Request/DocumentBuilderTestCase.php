<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;

class DocumentBuilderTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var RequestType */
    protected $requestType;

    protected function getValueNormalizer(): ValueNormalizer|\PHPUnit\Framework\MockObject\MockObject
    {
        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->willReturnCallback(function ($value, $dataType, $requestType, $isArrayAllowed) {
                self::assertEquals(DataType::ENTITY_TYPE, $dataType);
                self::assertEquals($this->requestType, $requestType);
                self::assertFalse($isArrayAllowed);

                if (str_contains($value, 'WithoutAlias')) {
                    throw new EntityAliasNotFoundException($value);
                }

                return strtolower(str_replace('\\', '_', $value));
            });

        return $valueNormalizer;
    }

    protected function getEntityIdTransformer(): EntityIdTransformerInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        $entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $entityIdTransformer->expects(self::any())
            ->method('transform')
            ->willReturnCallback(function ($id, EntityMetadata $metadata) {
                return sprintf('%s::%s', $metadata->getClassName(), $id);
            });

        return $entityIdTransformer;
    }

    protected function getEntityIdTransformerRegistry(
        EntityIdTransformerInterface $entityIdTransformer
    ): EntityIdTransformerRegistry|\PHPUnit\Framework\MockObject\MockObject {
        $entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);
        $entityIdTransformerRegistry->expects(self::any())
            ->method('getEntityIdTransformer')
            ->with($this->requestType)
            ->willReturn($entityIdTransformer);

        return $entityIdTransformerRegistry;
    }

    protected function getEntityMetadata(string $class, array $idFieldNames): EntityMetadata
    {
        $metadata = new EntityMetadata($class);
        $metadata->setIdentifierFieldNames($idFieldNames);

        return $metadata;
    }

    protected function createMetaPropertyMetadata(string $fieldName): MetaPropertyMetadata
    {
        $fieldMetadata = new MetaPropertyMetadata();
        $fieldMetadata->setName($fieldName);

        return $fieldMetadata;
    }

    protected function createFieldMetadata(string $fieldName): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);

        return $fieldMetadata;
    }

    protected function createAssociationMetadata(
        string $associationName,
        string $targetClass,
        bool $isCollection = false,
        array $idFields = ['id']
    ): AssociationMetadata {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setAcceptableTargetClassNames([$targetClass]);
        $associationMetadata->setIsCollection($isCollection);
        $associationMetadata->setTargetMetadata($this->getEntityMetadata($targetClass, $idFields));
        foreach ($idFields as $idField) {
            $associationMetadata->getTargetMetadata()->addField($this->createFieldMetadata($idField));
        }

        return $associationMetadata;
    }

    protected function addEntityPredefinedMetaProperties(EntityMetadata $metadata): EntityMetadata
    {
        $metadata->addMetaProperty(new MetaPropertyMetadata('__path__'));
        $metadata->addMetaProperty(new MetaPropertyMetadata('__class__'));
        $metadata->addMetaProperty(new MetaPropertyMetadata('__type__'));
        $metadata->addMetaProperty(new MetaPropertyMetadata('__id__'));

        return $metadata;
    }

    protected function addAssociationPredefinedMetaProperties(AssociationMetadata $association): AssociationMetadata
    {
        $association->addRelationshipMetaProperty(new MetaAttributeMetadata('__path__'));
        $association->addRelationshipMetaProperty(new MetaAttributeMetadata('__class__'));
        $association->addRelationshipMetaProperty(new MetaAttributeMetadata('__type__'));
        $association->addRelationshipMetaProperty(new MetaAttributeMetadata('__id__'));
        $association->addMetaProperty(new MetaAttributeMetadata('__path__'));
        $association->addMetaProperty(new MetaAttributeMetadata('__class__'));
        $association->addMetaProperty(new MetaAttributeMetadata('__type__'));
        $association->addMetaProperty(new MetaAttributeMetadata('__id__'));
        $this->addEntityPredefinedMetaProperties($association->getTargetMetadata());

        if ($association->isCollection()) {
            $association->addRelationshipMetaProperty(
                new MetaAttributeMetadata('__has_more__', 'boolean', 'has_more')
            );
        }

        return $association;
    }
}
