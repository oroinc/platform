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

    /**
     * @return ValueNormalizer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getValueNormalizer()
    {
        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->willReturnCallback(
                function ($value, $dataType, $requestType, $isArrayAllowed) {
                    self::assertEquals(DataType::ENTITY_TYPE, $dataType);
                    self::assertEquals($this->requestType, $requestType);
                    self::assertFalse($isArrayAllowed);

                    if (false !== strpos($value, 'WithoutAlias')) {
                        throw new EntityAliasNotFoundException($value);
                    }

                    return strtolower(str_replace('\\', '_', $value));
                }
            );

        return $valueNormalizer;
    }

    /**
     * @return EntityIdTransformerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getEntityIdTransformer()
    {
        $entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $entityIdTransformer->expects(self::any())
            ->method('transform')
            ->willReturnCallback(
                function ($id, EntityMetadata $metadata) {
                    return sprintf('%s::%s', $metadata->getClassName(), $id);
                }
            );

        return $entityIdTransformer;
    }

    /**
     * @param EntityIdTransformerInterface $entityIdTransformer
     *
     * @return EntityIdTransformerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getEntityIdTransformerRegistry(EntityIdTransformerInterface $entityIdTransformer)
    {
        $entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);
        $entityIdTransformerRegistry->expects(self::any())
            ->method('getEntityIdTransformer')
            ->with($this->requestType)
            ->willReturn($entityIdTransformer);

        return $entityIdTransformerRegistry;
    }

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
     * @return MetaPropertyMetadata
     */
    protected function createMetaPropertyMetadata($fieldName)
    {
        $fieldMetadata = new MetaPropertyMetadata();
        $fieldMetadata->setName($fieldName);

        return $fieldMetadata;
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
     * @param string   $associationName
     * @param string   $targetClass
     * @param bool     $isCollection
     * @param string[] $idFields
     *
     * @return AssociationMetadata
     */
    protected function createAssociationMetadata(
        $associationName,
        $targetClass,
        $isCollection = false,
        array $idFields = ['id']
    ) {
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

    /**
     * @param EntityMetadata $metadata
     */
    protected function addEntityPredefinedMetaProperties(EntityMetadata $metadata): EntityMetadata
    {
        $metadata->addMetaProperty(new MetaPropertyMetadata('__path__'));
        $metadata->addMetaProperty(new MetaPropertyMetadata('__class__'));
        $metadata->addMetaProperty(new MetaPropertyMetadata('__type__'));
        $metadata->addMetaProperty(new MetaPropertyMetadata('__id__'));

        return $metadata;
    }

    /**
     * @param AssociationMetadata $association
     */
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
