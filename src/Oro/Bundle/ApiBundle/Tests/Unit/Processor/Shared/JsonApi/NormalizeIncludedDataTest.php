<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ApiBundle\Collection\IncludedObjectCollection;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\NormalizeIncludedData;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;

class NormalizeIncludedDataTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityInstantiator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityIdTransformer;

    /** @var NormalizeIncludedData */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityInstantiator = $this->getMockBuilder(EntityInstantiator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->valueNormalizer = $this->getMockBuilder(ValueNormalizer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityIdTransformer = $this->getMock(EntityIdTransformerInterface::class);

        $this->processor = new NormalizeIncludedData(
            $this->doctrineHelper,
            $this->entityInstantiator,
            $this->valueNormalizer,
            $this->entityIdTransformer
        );
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "/included/0" element should be an array.
     */
    public function testProcessForAlreadyNormalizedIncludedDataButTheyHaveInvalidElement()
    {
        $includedData = [
            null
        ];

        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "/included/0" element should have "data" property.
     */
    public function testProcessForAlreadyNormalizedIncludedDataButTheyHaveInvalidSchema()
    {
        $includedData = [
            ['type' => 'testType', 'id' => 'testId']
        ];

        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "data" property of "/included/0" element should be an array.
     */
    public function testProcessForAlreadyNormalizedIncludedDataButTheyHaveInvalidDataElement()
    {
        $includedData = [
            [
                'data' => null
            ]
        ];

        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);
    }

    public function testProcessForAlreadyNormalizedIncludedData()
    {
        $includedData = [
            ['data' => ['type' => 'testType', 'id' => 'testId']]
        ];
        $normalizedType = 'Test\Class';
        $includedObject = new \stdClass();

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($normalizedType)
            ->willReturn($includedObject);

        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);
        $this->assertSame($includedData, $this->context->getIncludedData());
        $this->assertNotNull($this->context->getIncludedObjects());
        $this->assertSame(
            $includedObject,
            $this->context->getIncludedObjects()->get($normalizedType, 'testId')
        );
    }

    public function testProcessWhenIncludedObjectsAlreadyLoaded()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId']
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->context->setIncludedObjects(new IncludedObjectCollection());
        $this->processor->process($this->context);
        $this->assertEquals(
            [
                ['data' => ['type' => 'testType', 'id' => 'testId']]
            ],
            $this->context->getIncludedData()
        );
        $this->assertCount(0, $this->context->getIncludedObjects());
    }

    public function testProcessIncludedSectionDoesNotExistInRequestData()
    {
        $requestData = [];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);
        $this->assertEquals([], $this->context->getIncludedData());
        $this->assertNull($this->context->getIncludedObjects());
    }

    public function testProcessIncludedSectionExistInRequestData()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId']
            ]
        ];
        $normalizedType = 'Test\Class';
        $includedObject = new \stdClass();

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($normalizedType)
            ->willReturn($includedObject);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);
        $this->assertEquals(
            [
                ['data' => ['type' => 'testType', 'id' => 'testId']]
            ],
            $this->context->getIncludedData()
        );
        $this->assertNotNull($this->context->getIncludedObjects());
        $this->assertSame(
            $includedObject,
            $this->context->getIncludedObjects()->get($normalizedType, 'testId')
        );
    }

    public function testProcessForNewIncludedObjectOrEntity()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId']
            ]
        ];
        $normalizedType = 'Test\Class';
        $includedObject = new \stdClass();

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($normalizedType)
            ->willReturn($includedObject);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasErrors());
        $this->assertNotNull($this->context->getIncludedObjects());
        $this->assertSame(
            $includedObject,
            $this->context->getIncludedObjects()->get($normalizedType, 'testId')
        );
    }

    public function testProcessForExistingIncludedObject()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId', 'meta' => ['_update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $error = Error::createValidationError(
            Constraint::VALUE,
            'Only manageable entity can be updated.'
        )->setSource(ErrorSource::createByPointer('/included/0/meta/_update'));

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($normalizedType)
            ->willReturn(false);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);
        $this->assertNull($this->context->getIncludedObjects());
        $this->assertEquals([$error], $this->context->getErrors());
    }

    public function testProcessForExistingIncludedEntity()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId', 'meta' => ['_update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;
        $includedEntity = new \stdClass();

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($normalizedType)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($normalizedType, true)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with($normalizedType, $normalizedId)
            ->willReturn($includedEntity);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($normalizedType, 'testId')
            ->willReturn($normalizedId);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasErrors());
        $this->assertNotNull($this->context->getIncludedObjects());
        $this->assertSame(
            $includedEntity,
            $this->context->getIncludedObjects()->get($normalizedType, $normalizedId)
        );
    }

    public function testProcessForExistingIncludedEntityWhichDoesNotExistInDatabase()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId', 'meta' => ['_update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;
        $error = Error::createValidationError(
            Constraint::ENTITY,
            'The entity does not exist.'
        )->setSource(ErrorSource::createByPointer('/included/0'));

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($normalizedType)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($normalizedType, true)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with($normalizedType, $normalizedId)
            ->willReturn(null);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($normalizedType, 'testId')
            ->willReturn($normalizedId);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);
        $this->assertNull($this->context->getIncludedObjects());
        $this->assertEquals([$error], $this->context->getErrors());
    }

    public function testProcessWhenIncludedObjectTypeIsUnknown()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId', 'meta' => ['_update' => true]]
            ]
        ];
        $error = Error::createValidationError(Constraint::ENTITY_TYPE)
            ->setSource(ErrorSource::createByPointer('/included/0/type'));

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willThrowException(new \Exception('some error'));
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);
        $this->assertNull($this->context->getIncludedObjects());
        $this->assertEquals([$error], $this->context->getErrors());
    }

    public function testProcessForInvalidUpdateFlag()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId', 'meta' => ['_update' => null]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $error = Error::createValidationError(
            Constraint::VALUE,
            'This value should be boolean.'
        )->setSource(ErrorSource::createByPointer('/included/0/meta/_update'));

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);
        $this->assertNull($this->context->getIncludedObjects());
        $this->assertEquals([$error], $this->context->getErrors());
    }

    public function testProcessWhenNormalizationOfIncludedEntityIdFailed()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId', 'meta' => ['_update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $exception = new \Exception('some error');
        $error = Error::createValidationError(Constraint::ENTITY_ID)
            ->setSource(ErrorSource::createByPointer('/included/0/id'))
            ->setInnerException($exception);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($normalizedType)
            ->willReturn(true);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($normalizedType, 'testId')
            ->willThrowException($exception);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);
        $this->assertNull($this->context->getIncludedObjects());
        $this->assertEquals([$error], $this->context->getErrors());
    }
}
