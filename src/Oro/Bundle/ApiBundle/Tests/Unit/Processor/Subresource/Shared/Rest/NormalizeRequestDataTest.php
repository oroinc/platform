<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared\Rest;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\Rest\NormalizeRequestData;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;

class NormalizeRequestDataTest extends ChangeRelationshipProcessorTestCase
{
    private const ASSOCIATION_NAME = 'testAssociation';

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdTransformerInterface */
    private $entityIdTransformer;

    /** @var NormalizeRequestData */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);
        $entityIdTransformerRegistry->expects(self::any())
            ->method('getEntityIdTransformer')
            ->with($this->context->getRequestType())
            ->willReturn($this->entityIdTransformer);

        $this->processor = new NormalizeRequestData($entityIdTransformerRegistry);
    }

    private function createAssociationMetadata(
        string $associationName,
        string $targetClass,
        bool $isCollection
    ): AssociationMetadata {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setIsCollection($isCollection);
        $associationTargetMetadata = new EntityMetadata($targetClass);
        $associationMetadata->setTargetMetadata($associationTargetMetadata);

        return $associationMetadata;
    }

    public function testNormalizeDataForAssociationWithoutMetadata()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');

        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData(['val']);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => 'val'
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testNormalizeDataForToOneAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationMetadata = $parentMetadata->addAssociation(
            $this->createAssociationMetadata(self::ASSOCIATION_NAME, 'Test\Class', false)
        );

        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with('val', self::identicalTo($associationMetadata->getTargetMetadata()))
            ->willReturn('normalizedVal');

        $this->context->setRequestData(['val']);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->context->setParentMetadata($parentMetadata);
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
        $parentMetadata->addAssociation(
            $this->createAssociationMetadata(self::ASSOCIATION_NAME, 'Test\Class', false)
        );

        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData([null]);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => null
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testNormalizeDataForToManyAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationMetadata = $parentMetadata->addAssociation(
            $this->createAssociationMetadata(self::ASSOCIATION_NAME, 'Test\Class', true)
        );

        $this->entityIdTransformer->expects(self::exactly(2))
            ->method('reverseTransform')
            ->willReturnMap([
                ['val1', $associationMetadata->getTargetMetadata(), 'normalizedVal1'],
                ['val2', $associationMetadata->getTargetMetadata(), 'normalizedVal2']
            ]);

        $this->context->setRequestData([['val1', 'val2']]);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(true);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                [
                    'id'    => 'normalizedVal1',
                    'class' => 'Test\Class'
                ],
                [
                    'id'    => 'normalizedVal2',
                    'class' => 'Test\Class'
                ]
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testNormalizeEmptyDataForToManyAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(
            $this->createAssociationMetadata(self::ASSOCIATION_NAME, 'Test\Class', true)
        );

        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData([[]]);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(true);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => []
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessWithInvalidIdentifierForToOneAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(
            $this->createAssociationMetadata(self::ASSOCIATION_NAME, 'Test\Class', false)
        );

        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->willThrowException(new \Exception('cannot normalize id'));

        $this->context->setRequestData(['val']);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->context->setParentMetadata($parentMetadata);
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
                    ->setSource(ErrorSource::createByPropertyPath('0'))
            ],
            $this->context->getErrors()
        );
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessWithInvalidIdentifiersForToManyAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(
            $this->createAssociationMetadata(self::ASSOCIATION_NAME, 'Test\Class', true)
        );

        $this->entityIdTransformer->expects(self::exactly(2))
            ->method('reverseTransform')
            ->willThrowException(new \Exception('cannot normalize id'));

        $this->context->setRequestData([['val1', 'val2']]);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(true);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                [
                    'id'    => 'val1',
                    'class' => 'Test\Class'
                ],
                [
                    'id'    => 'val2',
                    'class' => 'Test\Class'
                ]
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertEquals(
            [
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPropertyPath('0.0')),
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPropertyPath('0.1'))
            ],
            $this->context->getErrors()
        );
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessWithNotResolvedIdentifierForToOneAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(
            $this->createAssociationMetadata(self::ASSOCIATION_NAME, 'Test\Class', false)
        );

        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->willReturn(null);

        $this->context->setRequestData(['val']);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                'id'    => null,
                'class' => 'Test\Class'
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertFalse($this->context->hasErrors());
        self::assertEquals(
            [
                'requestData.0' => new NotResolvedIdentifier('val', 'Test\Class')
            ],
            $this->context->getNotResolvedIdentifiers()
        );
    }

    public function testProcessWithNotResolvedIdentifiersForToManyAssociation()
    {
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(
            $this->createAssociationMetadata(self::ASSOCIATION_NAME, 'Test\Class', true)
        );

        $this->entityIdTransformer->expects(self::exactly(2))
            ->method('reverseTransform')
            ->willReturnCallback(function ($value, EntityMetadata $metadata) {
                if ('val1' === $value) {
                    return null;
                }

                return 'normalized::' . $metadata->getClassName() . '::' . $value;
            });

        $this->context->setRequestData([['val1', 'val2']]);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(true);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                [
                    'id'    => null,
                    'class' => 'Test\Class'
                ],
                [
                    'id'    => 'normalized::Test\Class::val2',
                    'class' => 'Test\Class'
                ]
            ]
        ];

        self::assertEquals($expectedData, $this->context->getRequestData());
        self::assertFalse($this->context->hasErrors());
        self::assertEquals(
            [
                'requestData.0.0' => new NotResolvedIdentifier('val1', 'Test\Class')
            ],
            $this->context->getNotResolvedIdentifiers()
        );
    }
}
