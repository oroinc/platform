<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\JsonApi\NormalizeRequestData;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;

class NormalizeRequestDataTest extends ChangeRelationshipProcessorTestCase
{
    const ASSOCIATION_NAME = 'testAssociation';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityIdTransformer;

    /** @var NormalizeRequestData */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityIdTransformer = $this->getMock('Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface');

        $this->processor = new NormalizeRequestData($this->valueNormalizer, $this->entityIdTransformer);
    }

    public function testNormalizeDataForToOneAssociation()
    {
        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with('entity', DataType::ENTITY_CLASS, $this->context->getRequestType(), false)
            ->willReturn('Test\Class');
        $this->entityIdTransformer->expects($this->once())
            ->method('reverseTransform')
            ->with('Test\Class', 'val')
            ->willReturn('normalizedVal');

        $this->context->setRequestData(['data' => ['type' => 'entity', 'id' => 'val']]);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                'id'    => 'normalizedVal',
                'class' => 'Test\Class'
            ]
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
    }

    public function testNormalizeEmptyDataForToOneAssociation()
    {
        $this->valueNormalizer->expects($this->never())
            ->method('normalizeValue');
        $this->entityIdTransformer->expects($this->never())
            ->method('reverseTransform');

        $this->context->setRequestData(['data' => null]);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => null
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
    }

    public function testNormalizeDataForToManyAssociation()
    {
        $this->valueNormalizer->expects($this->exactly(2))
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['entity1', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, 'Test\Class1'],
                    ['entity2', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, 'Test\Class2'],
                ]
            );
        $this->entityIdTransformer->expects($this->exactly(2))
            ->method('reverseTransform')
            ->willReturnMap(
                [
                    ['Test\Class1', 'val1', 'normalizedVal1'],
                    ['Test\Class2', 'val2', 'normalizedVal2'],
                ]
            );

        $this->context->setRequestData(
            [
                'data' => [
                    ['type' => 'entity1', 'id' => 'val1'],
                    ['type' => 'entity2', 'id' => 'val2']
                ]
            ]
        );
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

        $this->assertEquals($expectedData, $this->context->getRequestData());
    }

    public function testNormalizeEmptyDataForToManyAssociation()
    {
        $this->valueNormalizer->expects($this->never())
            ->method('normalizeValue');
        $this->entityIdTransformer->expects($this->never())
            ->method('reverseTransform');

        $this->context->setRequestData(['data' => []]);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => []
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
    }

    public function testProcessWithInvalidEntityTypeForToOneAssociation()
    {
        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->willThrowException(new \Exception('cannot normalize entity type'));
        $this->entityIdTransformer->expects($this->never())
            ->method('reverseTransform');

        $this->context->setRequestData(['data' => ['type' => 'entity', 'id' => 'val']]);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                'id'    => 'val',
                'class' => 'entity'
            ]
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
        $this->assertEquals(
            [
                Error::createValidationError('entity type constraint')
                    ->setInnerException(new \Exception('cannot normalize entity type'))
                    ->setSource(ErrorSource::createByPointer('/data/type'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidIdentifierForToOneAssociation()
    {
        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with('entity', DataType::ENTITY_CLASS, $this->context->getRequestType(), false)
            ->willReturn('Test\Class');
        $this->entityIdTransformer->expects($this->once())
            ->method('reverseTransform')
            ->willThrowException(new \Exception('cannot normalize id'));

        $this->context->setRequestData(['data' => ['type' => 'entity', 'id' => 'val']]);
        $this->context->setAssociationName(self::ASSOCIATION_NAME);
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        $expectedData = [
            self::ASSOCIATION_NAME => [
                'id'    => 'val',
                'class' => 'Test\Class'
            ]
        ];

        $this->assertEquals($expectedData, $this->context->getRequestData());
        $this->assertEquals(
            [
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPointer('/data/id'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidEntityTypesForToManyAssociation()
    {
        $this->valueNormalizer->expects($this->exactly(2))
            ->method('normalizeValue')
            ->willThrowException(new \Exception('cannot normalize entity type'));
        $this->entityIdTransformer->expects($this->never())
            ->method('reverseTransform');

        $this->context->setRequestData(
            [
                'data' => [
                    ['type' => 'entity1', 'id' => 'val1'],
                    ['type' => 'entity2', 'id' => 'val2']
                ]
            ]
        );
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

        $this->assertEquals($expectedData, $this->context->getRequestData());
        $this->assertEquals(
            [
                Error::createValidationError('entity type constraint')
                    ->setInnerException(new \Exception('cannot normalize entity type'))
                    ->setSource(ErrorSource::createByPointer('/data/0/type')),
                Error::createValidationError('entity type constraint')
                    ->setInnerException(new \Exception('cannot normalize entity type'))
                    ->setSource(ErrorSource::createByPointer('/data/1/type')),
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidIdentifiersForToManyAssociation()
    {
        $this->valueNormalizer->expects($this->exactly(2))
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['entity1', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, 'Test\Class1'],
                    ['entity2', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, 'Test\Class2'],
                ]
            );
        $this->entityIdTransformer->expects($this->exactly(2))
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

        $this->assertEquals($expectedData, $this->context->getRequestData());
        $this->assertEquals(
            [
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPointer('/data/0/id')),
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('cannot normalize id'))
                    ->setSource(ErrorSource::createByPointer('/data/1/id')),
            ],
            $this->context->getErrors()
        );
    }
}
