<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\NormalizeIncludedData;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\EntityLoader;

class NormalizeIncludedDataTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityInstantiator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityLoader;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityIdTransformer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var NormalizeIncludedData */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityInstantiator = $this->createMock(EntityInstantiator::class);
        $this->entityLoader = $this->createMock(EntityLoader::class);
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->processor = new NormalizeIncludedData(
            $this->doctrineHelper,
            $this->entityInstantiator,
            $this->entityLoader,
            $this->valueNormalizer,
            $this->entityIdTransformer,
            $this->configProvider,
            $this->metadataProvider
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     *
     * @return Config
     */
    private function getConfig(EntityDefinitionConfig $definition)
    {
        $config = new Config();
        $config->setDefinition($definition);

        return $config;
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
        $includedEntity = new \stdClass();

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($normalizedType)
            ->willReturn($includedEntity);

        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);

        $this->assertSame($includedData, $this->context->getIncludedData());
        $this->assertNotNull($this->context->getIncludedEntities());
        $this->assertSame(
            $includedEntity,
            $this->context->getIncludedEntities()->get($normalizedType, 'testId')
        );
    }

    public function testProcessWhenIncludedEntitiesAlreadyLoaded()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId']
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->context->setIncludedEntities(new IncludedEntityCollection());
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                ['data' => ['type' => 'testType', 'id' => 'testId']]
            ],
            $this->context->getIncludedData()
        );
        $this->assertCount(0, $this->context->getIncludedEntities());
    }

    public function testProcessIncludedSectionDoesNotExistInRequestData()
    {
        $requestData = [];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getIncludedData());
        $this->assertNull($this->context->getIncludedEntities());
    }

    public function testProcessIncludedSectionExistInRequestData()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId']
            ]
        ];
        $normalizedType = 'Test\Class';
        $includedEntity = new \stdClass();

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($normalizedType)
            ->willReturn($includedEntity);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                ['data' => ['type' => 'testType', 'id' => 'testId']]
            ],
            $this->context->getIncludedData()
        );
        $this->assertNotNull($this->context->getIncludedEntities());
        $this->assertSame(
            $includedEntity,
            $this->context->getIncludedEntities()->get($normalizedType, 'testId')
        );
        $this->assertAttributeSame(
            ['Test\PrimaryClass', 'primaryId', null],
            'primaryEntity',
            $this->context->getIncludedEntities()
        );
    }

    public function testProcessForNewIncludedEntityOrObject()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId']
            ]
        ];
        $normalizedType = 'Test\Class';
        $includedEntity = new \stdClass();

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
            ->willReturn($includedEntity);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
        $this->assertNotNull($this->context->getIncludedEntities());
        $this->assertSame(
            $includedEntity,
            $this->context->getIncludedEntities()->get($normalizedType, 'testId')
        );
    }

    public function testProcessForExistingIncludedObject()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId', 'meta' => ['update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $error = Error::createValidationError(
            Constraint::VALUE,
            'Only manageable entity can be updated.'
        )->setSource(ErrorSource::createByPointer('/included/0/meta/update'));

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

        $this->assertNull($this->context->getIncludedEntities());
        $this->assertEquals([$error], $this->context->getErrors());
    }

    public function testProcessForExistingIncludedEntity()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId', 'meta' => ['update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;
        $includedEntity = new \stdClass();
        $metadata = new EntityMetadata();

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($normalizedType)
            ->willReturn(true);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($normalizedType, $normalizedId, self::isInstanceOf($metadata))
            ->willReturn($includedEntity);

        $config = new EntityDefinitionConfig();
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra(), new FilterIdentifierFieldsConfigExtra()]
            )
            ->willReturn($this->getConfig($config));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                self::identicalTo($config)
            )
            ->willReturn($metadata);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with('testId', self::identicalTo($metadata))
            ->willReturn($normalizedId);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
        $this->assertNotNull($this->context->getIncludedEntities());
        $this->assertSame(
            $includedEntity,
            $this->context->getIncludedEntities()->get($normalizedType, $normalizedId)
        );
    }

    public function testProcessForExistingIncludedEntityWhichDoesNotExistInDatabase()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId', 'meta' => ['update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;
        $metadata = new EntityMetadata();
        $error = Error::createValidationError(
            Constraint::ENTITY,
            'The entity does not exist.'
        )->setSource(ErrorSource::createByPointer('/included/0'));

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($normalizedType)
            ->willReturn(true);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($normalizedType, $normalizedId, self::isInstanceOf($metadata))
            ->willReturn(null);

        $config = new EntityDefinitionConfig();
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra(), new FilterIdentifierFieldsConfigExtra()]
            )
            ->willReturn($this->getConfig($config));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                self::identicalTo($config)
            )
            ->willReturn($metadata);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with('testId', self::identicalTo($metadata))
            ->willReturn($normalizedId);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getIncludedEntities());
        $this->assertEquals([$error], $this->context->getErrors());
    }

    public function testProcessWhenIncludedEntityTypeIsUnknown()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId', 'meta' => ['update' => true]]
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

        $this->assertNull($this->context->getIncludedEntities());
        $this->assertEquals([$error], $this->context->getErrors());
    }

    public function testProcessForInvalidUpdateFlag()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId', 'meta' => ['update' => null]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $error = Error::createValidationError(
            Constraint::VALUE,
            'This value should be boolean.'
        )->setSource(ErrorSource::createByPointer('/included/0/meta/update'));

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

        $this->assertNull($this->context->getIncludedEntities());
        $this->assertEquals([$error], $this->context->getErrors());
    }

    public function testProcessWhenNormalizationOfIncludedEntityIdFailed()
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId', 'meta' => ['update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $metadata = new EntityMetadata();
        $exception = new \Exception('some error');
        $error = Error::createValidationError(Constraint::ENTITY_ID)
            ->setSource(ErrorSource::createByPointer('/included/0/id'))
            ->setInnerException($exception);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($normalizedType)
            ->willReturn(true);

        $config = new EntityDefinitionConfig();
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra(), new FilterIdentifierFieldsConfigExtra()]
            )
            ->willReturn($this->getConfig($config));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                self::identicalTo($config)
            )
            ->willReturn($metadata);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('testType', DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with('testId', self::identicalTo($metadata))
            ->willThrowException($exception);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getIncludedEntities());
        $this->assertEquals([$error], $this->context->getErrors());
    }
}
