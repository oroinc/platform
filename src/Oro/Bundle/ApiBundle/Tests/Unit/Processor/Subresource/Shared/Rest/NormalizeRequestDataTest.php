<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared\Rest;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
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

    protected function setUp()
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

    /**
     * @param string $associationName
     * @param string $targetClass
     * @param bool   $isCollection
     *
     * @return AssociationMetadata
     */
    private function createAssociationMetadata($associationName, $targetClass, $isCollection)
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setIsCollection($isCollection);
        $associationTargetMetadata = new EntityMetadata();
        $associationTargetMetadata->setClassName($targetClass);
        $associationMetadata->setTargetMetadata($associationTargetMetadata);

        return $associationMetadata;
    }

    public function testNormalizeDataForAssociationWithoutMetadata()
    {
        $parentMetadata = new EntityMetadata();

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
    }

    public function testNormalizeDataForToOneAssociation()
    {
        $parentMetadata = new EntityMetadata();
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
    }

    public function testNormalizeEmptyDataForToOneAssociation()
    {
        $parentMetadata = new EntityMetadata();
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
    }

    public function testNormalizeDataForToManyAssociation()
    {
        $parentMetadata = new EntityMetadata();
        $associationMetadata = $parentMetadata->addAssociation(
            $this->createAssociationMetadata(self::ASSOCIATION_NAME, 'Test\Class', true)
        );

        $this->entityIdTransformer->expects(self::exactly(2))
            ->method('reverseTransform')
            ->willReturnMap(
                [
                    ['val1', $associationMetadata->getTargetMetadata(), 'normalizedVal1'],
                    ['val2', $associationMetadata->getTargetMetadata(), 'normalizedVal2']
                ]
            );

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
    }

    public function testNormalizeEmptyDataForToManyAssociation()
    {
        $parentMetadata = new EntityMetadata();
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
    }

    public function testProcessWithInvalidIdentifierForToOneAssociation()
    {
        $parentMetadata = new EntityMetadata();
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
                    ->setSource(ErrorSource::createByPropertyPath(self::ASSOCIATION_NAME))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidIdentifiersForToManyAssociation()
    {
        $parentMetadata = new EntityMetadata();
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
                    ->setSource(ErrorSource::createByPropertyPath(self::ASSOCIATION_NAME . '.0')),
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPropertyPath(self::ASSOCIATION_NAME . '.1'))
            ],
            $this->context->getErrors()
        );
    }
}
