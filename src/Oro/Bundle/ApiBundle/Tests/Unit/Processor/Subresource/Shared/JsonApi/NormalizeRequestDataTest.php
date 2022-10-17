<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\JsonApi\NormalizeRequestData;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NormalizeRequestDataTest extends ChangeRelationshipProcessorTestCase
{
    private const ASSOCIATION_NAME = 'testAssociation';

    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdTransformerInterface */
    private $entityIdTransformer;

    /** @var NormalizeRequestData */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);
        $entityIdTransformerRegistry->expects(self::any())
            ->method('getEntityIdTransformer')
            ->with($this->context->getRequestType())
            ->willReturn($this->entityIdTransformer);

        $this->processor = new NormalizeRequestData($this->valueNormalizer, $entityIdTransformerRegistry);
    }

    public function testNormalizeDataForToOneAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(new AssociationMetadata(self::ASSOCIATION_NAME))
            ->setTargetMetadata($associationTargetMetadata);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('entity', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false)
            ->willReturn('Test\Class');
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with('val', self::identicalTo($associationTargetMetadata))
            ->willReturn('normalizedVal');

        $this->context->setRequestData(['data' => ['type' => 'entity', 'id' => 'val']]);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                'id'    => 'normalizedVal',
                'class' => 'Test\Class'
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testNormalizeEmptyDataForToOneAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(new AssociationMetadata(self::ASSOCIATION_NAME))
            ->setTargetMetadata($associationTargetMetadata);

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData(['data' => null]);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => null
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testNormalizeDataForToOneAssociationWithMetaSection()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addMetaProperty(new MetaPropertyMetadata('meta1'));
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(new AssociationMetadata(self::ASSOCIATION_NAME))
            ->setTargetMetadata($associationTargetMetadata);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('entity', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false)
            ->willReturn('Test\Class');
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with('val', self::identicalTo($associationTargetMetadata))
            ->willReturn('normalizedVal');

        $this->context->setRequestData([
            'meta' => ['meta1' => 'val1', 'meta2' => 'val2'],
            'data' => ['type' => 'entity', 'id' => 'val']
        ]);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        $expectedData = [
            'meta1'                => 'val1',
            self::ASSOCIATION_NAME => [
                'id'    => 'normalizedVal',
                'class' => 'Test\Class'
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testNormalizeDataForToManyAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(new AssociationMetadata(self::ASSOCIATION_NAME))
            ->setTargetMetadata($associationTargetMetadata);

        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['entity1', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false, 'Test\Class1'],
                ['entity2', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false, 'Test\Class2']
            ]);
        $this->entityIdTransformer->expects(self::exactly(2))
            ->method('reverseTransform')
            ->willReturnMap([
                ['val1', $associationTargetMetadata, 'normalizedVal1'],
                ['val2', $associationTargetMetadata, 'normalizedVal2']
            ]);

        $this->context->setRequestData(
            [
                'data' => [
                    ['type' => 'entity1', 'id' => 'val1'],
                    ['type' => 'entity2', 'id' => 'val2']
                ]
            ]
        );
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                [
                    'id'    => 'normalizedVal1',
                    'class' => 'Test\Class1'
                ],
                [
                    'id'    => 'normalizedVal2',
                    'class' => 'Test\Class2'
                ]
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testNormalizeEmptyDataForToManyAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(new AssociationMetadata(self::ASSOCIATION_NAME))
            ->setTargetMetadata($associationTargetMetadata);

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData(['data' => []]);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => []
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testNormalizeDataForToManyAssociationWithMetaSection()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addMetaProperty(new MetaPropertyMetadata('meta1'));
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(new AssociationMetadata(self::ASSOCIATION_NAME))
            ->setTargetMetadata($associationTargetMetadata);

        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['entity1', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false, 'Test\Class1'],
                ['entity2', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false, 'Test\Class2']
            ]);
        $this->entityIdTransformer->expects(self::exactly(2))
            ->method('reverseTransform')
            ->willReturnMap([
                ['val1', $associationTargetMetadata, 'normalizedVal1'],
                ['val2', $associationTargetMetadata, 'normalizedVal2']
            ]);

        $this->context->setRequestData(
            [
                'meta' => ['meta1' => 'val1', 'meta2' => 'val2'],
                'data' => [
                    ['type' => 'entity1', 'id' => 'val1'],
                    ['type' => 'entity2', 'id' => 'val2']
                ]
            ]
        );
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        $expectedData = [
            'meta1'                => 'val1',
            self::ASSOCIATION_NAME => [
                [
                    'id'    => 'normalizedVal1',
                    'class' => 'Test\Class1'
                ],
                [
                    'id'    => 'normalizedVal2',
                    'class' => 'Test\Class2'
                ]
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessWithInvalidEntityTypeForToOneAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(new AssociationMetadata(self::ASSOCIATION_NAME))
            ->setTargetMetadata($associationTargetMetadata);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->willThrowException(new EntityAliasNotFoundException('cannot normalize entity type'));
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData(['data' => ['type' => 'entity', 'id' => 'val']]);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                'id'    => 'val',
                'class' => 'entity'
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertEquals(
            [
                Error::createValidationError('entity type constraint')
                    ->setInnerException(new EntityAliasNotFoundException('cannot normalize entity type'))
                    ->setSource(ErrorSource::createByPointer('/data/type'))
            ],
            $this->context->getErrors()
        );
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessWithInvalidIdentifierForToOneAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(new AssociationMetadata(self::ASSOCIATION_NAME))
            ->setTargetMetadata($associationTargetMetadata);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('entity', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false)
            ->willReturn('Test\Class');
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->willThrowException(new \Exception('cannot normalize id'));

        $this->context->setRequestData(['data' => ['type' => 'entity', 'id' => 'val']]);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                'id'    => 'val',
                'class' => 'Test\Class'
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertEquals(
            [
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPointer('/data/id'))
            ],
            $this->context->getErrors()
        );
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessWithInvalidEntityTypesForToManyAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(new AssociationMetadata(self::ASSOCIATION_NAME))
            ->setTargetMetadata($associationTargetMetadata);

        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willThrowException(new EntityAliasNotFoundException('cannot normalize entity type'));
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData(
            [
                'data' => [
                    ['type' => 'entity1', 'id' => 'val1'],
                    ['type' => 'entity2', 'id' => 'val2']
                ]
            ]
        );
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                [
                    'id'    => 'val1',
                    'class' => 'entity1'
                ],
                [
                    'id'    => 'val2',
                    'class' => 'entity2'
                ]
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertEquals(
            [
                Error::createValidationError('entity type constraint')
                    ->setInnerException(new EntityAliasNotFoundException('cannot normalize entity type'))
                    ->setSource(ErrorSource::createByPointer('/data/0/type')),
                Error::createValidationError('entity type constraint')
                    ->setInnerException(new EntityAliasNotFoundException('cannot normalize entity type'))
                    ->setSource(ErrorSource::createByPointer('/data/1/type'))
            ],
            $this->context->getErrors()
        );
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessWithInvalidIdentifiersForToManyAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationTargetMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(new AssociationMetadata(self::ASSOCIATION_NAME))
            ->setTargetMetadata($associationTargetMetadata);

        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['entity1', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false, 'Test\Class1'],
                ['entity2', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false, 'Test\Class2']
            ]);
        $this->entityIdTransformer->expects(self::exactly(2))
            ->method('reverseTransform')
            ->willThrowException(new \Exception('cannot normalize id'));

        $this->context->setRequestData(
            [
                'data' => [
                    ['type' => 'entity1', 'id' => 'val1'],
                    ['type' => 'entity2', 'id' => 'val2']
                ]
            ]
        );
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                [
                    'id'    => 'val1',
                    'class' => 'Test\Class1'
                ],
                [
                    'id'    => 'val2',
                    'class' => 'Test\Class2'
                ]
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertEquals(
            [
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPointer('/data/0/id')),
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPointer('/data/1/id'))
            ],
            $this->context->getErrors()
        );
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessWithNotResolvedIdentifierForToOneAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationTargetMetadata = new EntityMetadata('Test\Class');
        $parentMetadata->addAssociation(new AssociationMetadata(self::ASSOCIATION_NAME))
            ->setTargetMetadata($associationTargetMetadata);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('entity', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false)
            ->willReturn('Test\Class');
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->willReturn(null);

        $this->context->setRequestData(['data' => ['type' => 'entity', 'id' => 'val']]);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                'id'    => null,
                'class' => 'Test\Class'
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertEquals(
            [
                'requestData.id' => new NotResolvedIdentifier('val', 'Test\Class')
            ],
            $this->context->getNotResolvedIdentifiers()
        );
    }

    public function testProcessWithNotResolvedIdentifiersForToManyAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationTargetMetadata = new EntityMetadata('Test\Class');
        $parentMetadata->addAssociation(new AssociationMetadata(self::ASSOCIATION_NAME))
            ->setTargetMetadata($associationTargetMetadata);

        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['entity1', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false, 'Test\Class1'],
                ['entity2', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false, 'Test\Class2']
            ]);
        $this->entityIdTransformer->expects(self::exactly(2))
            ->method('reverseTransform')
            ->willReturnCallback(function ($value, EntityMetadata $metadata) {
                if ('val1' === $value) {
                    return null;
                }

                return 'normalized::' . $metadata->getClassName() . '::' . $value;
            });

        $this->context->setRequestData(
            [
                'data' => [
                    ['type' => 'entity1', 'id' => 'val1'],
                    ['type' => 'entity2', 'id' => 'val2']
                ]
            ]
        );
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                [
                    'id'    => null,
                    'class' => 'Test\Class1'
                ],
                [
                    'id'    => 'normalized::Test\Class::val2',
                    'class' => 'Test\Class2'
                ]
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertFalse($this->context->hasErrors());
        self::assertEquals(
            [
                'requestData.0.id' => new NotResolvedIdentifier('val1', 'Test\Class')
            ],
            $this->context->getNotResolvedIdentifiers()
        );
    }
}
