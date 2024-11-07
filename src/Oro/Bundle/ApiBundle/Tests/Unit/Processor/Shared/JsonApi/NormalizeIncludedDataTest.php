<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Doctrine\ORM\NonUniqueResultException;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\NormalizeIncludedData;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\AclProtectedEntityLoader;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\UpsertCriteriaBuilder;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class NormalizeIncludedDataTest extends FormProcessorTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityInstantiator|\PHPUnit\Framework\MockObject\MockObject */
    private $entityInstantiator;

    /** @var AclProtectedEntityLoader|\PHPUnit\Framework\MockObject\MockObject */
    private $entityLoader;

    /** @var ValueNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $valueNormalizer;

    /** @var EntityIdTransformerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $entityIdTransformer;

    /** @var NormalizeIncludedData */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityInstantiator = $this->createMock(EntityInstantiator::class);
        $this->entityLoader = $this->createMock(AclProtectedEntityLoader::class);
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);
        $entityIdTransformerRegistry->expects(self::any())
            ->method('getEntityIdTransformer')
            ->with($this->context->getRequestType())
            ->willReturn($this->entityIdTransformer);

        $this->processor = new NormalizeIncludedData(
            $this->doctrineHelper,
            $this->entityInstantiator,
            $this->entityLoader,
            $this->valueNormalizer,
            $entityIdTransformerRegistry,
            $this->configProvider,
            $this->metadataProvider,
            new UpsertCriteriaBuilder($this->valueNormalizer)
        );
    }

    private function getConfig(EntityDefinitionConfig $definition): Config
    {
        $config = new Config();
        $config->setDefinition($definition);

        return $config;
    }

    public function testProcessForAlreadyNormalizedIncludedDataButTheyHaveInvalidElement(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "/included/0" element should be an array.');

        $includedData = [
            null
        ];

        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);
    }

    public function testProcessForAlreadyNormalizedIncludedDataButTheyHaveInvalidSchema(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "/included/0" element should have "data" property.');

        $includedData = [
            ['type' => 'testType', 'id' => 'testId']
        ];

        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);
    }

    public function testProcessForAlreadyNormalizedIncludedDataButTheyHaveInvalidDataElement(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "data" property of "/included/0" element should be an array.');

        $includedData = [
            ['data' => null]
        ];

        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);
    }

    public function testProcessForAlreadyNormalizedIncludedData(): void
    {
        $type = 'testType';
        $id = 'testId';
        $includedData = [
            ['data' => ['type' => $type, 'id' => $id]]
        ];
        $normalizedType = 'Test\Class';
        $includedEntity = new \stdClass();

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra(ApiAction::CREATE), new FilterIdentifierFieldsConfigExtra()]
            )
            ->willReturn($this->getConfig(new EntityDefinitionConfig()));
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($normalizedType)
            ->willReturn($includedEntity);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);

        self::assertSame($includedData, $this->context->getIncludedData());
        self::assertNotNull($this->context->getIncludedEntities());
        $addedIncludedEntity = $this->context->getIncludedEntities()->get($normalizedType, $id);
        self::assertSame($includedEntity, $addedIncludedEntity);
        self::assertEquals(
            new IncludedEntityData('/included/0', 0, false, ApiAction::CREATE),
            $this->context->getIncludedEntities()->getData($addedIncludedEntity)
        );
    }

    public function testProcessWhenIncludedEntitiesAlreadyLoaded(): void
    {
        $requestData = [
            'included' => [
                ['type' => 'testType', 'id' => 'testId']
            ]
        ];

        $this->context->setRequestData($requestData);
        $this->context->setIncludedEntities(new IncludedEntityCollection());
        $this->processor->process($this->context);

        self::assertEquals(
            [
                ['data' => ['type' => 'testType', 'id' => 'testId']]
            ],
            $this->context->getIncludedData()
        );
        self::assertCount(0, $this->context->getIncludedEntities());
    }

    public function testProcessIncludedSectionDoesNotExistInRequestData(): void
    {
        $requestData = [];

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedData());
        self::assertNull($this->context->getIncludedEntities());
    }

    public function testProcessIncludedSectionExistInRequestData(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id]
            ]
        ];
        $normalizedType = 'Test\Class';
        $includedEntity = new \stdClass();

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra(ApiAction::CREATE), new FilterIdentifierFieldsConfigExtra()]
            )
            ->willReturn($this->getConfig(new EntityDefinitionConfig()));
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

        self::assertEquals(
            [
                ['data' => ['type' => $type, 'id' => $id]]
            ],
            $this->context->getIncludedData()
        );
        self::assertNotNull($this->context->getIncludedEntities());
        $addedIncludedEntity = $this->context->getIncludedEntities()->get($normalizedType, $id);
        self::assertSame($includedEntity, $addedIncludedEntity);
        self::assertEquals(
            new IncludedEntityData('/included/0', 0, false, ApiAction::CREATE),
            $this->context->getIncludedEntities()->getData($addedIncludedEntity)
        );

        self::assertTrue($this->context->getIncludedEntities()->isPrimaryEntity('Test\PrimaryClass', 'primaryId'));
    }

    public function testProcessForNewIncludedEntityOrObject(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id]
            ]
        ];
        $normalizedType = 'Test\Class';
        $includedEntity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra(ApiAction::CREATE), new FilterIdentifierFieldsConfigExtra()]
            )
            ->willReturn($this->getConfig(new EntityDefinitionConfig()));
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

        self::assertFalse($this->context->hasErrors());
        self::assertNotNull($this->context->getIncludedEntities());
        $addedIncludedEntity = $this->context->getIncludedEntities()->get($normalizedType, $id);
        self::assertSame($includedEntity, $addedIncludedEntity);
        self::assertEquals(
            new IncludedEntityData('/included/0', 0, false, ApiAction::CREATE),
            $this->context->getIncludedEntities()->getData($addedIncludedEntity)
        );
    }

    public function testProcessForValidateFlagThatNotAffectNormalization(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id, 'meta' => ['validate' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $includedEntity = new \stdClass();

        $config = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra(ApiAction::CREATE), new FilterIdentifierFieldsConfigExtra()]
            )
            ->willReturn($this->getConfig($config));

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertNotNull($this->context->getIncludedEntities());
        $addedIncludedEntity = $this->context->getIncludedEntities()->get($normalizedType, $id);
        self::assertEquals($includedEntity, $addedIncludedEntity);
        self::assertEquals(
            new IncludedEntityData('/included/0', 0, false, ApiAction::CREATE),
            $this->context->getIncludedEntities()->getData($addedIncludedEntity)
        );
    }

    public function testProcessForInvalidUpdateFlag(): void
    {
        $type = 'testType';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => 'testId', 'meta' => ['update' => null]]
            ]
        ];
        $normalizedType = 'Test\Class';

        $this->doctrineHelper->expects(self::never())
            ->method('resolveManageableEntityClass');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createValidationError(Constraint::VALUE, 'This value should be a boolean.')
                    ->setSource(ErrorSource::createByPointer('/included/0/meta/update'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpdateFlagForExistingIncludedObject(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id, 'meta' => ['update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn(null);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra($this->context->getAction()), new FilterIdentifierFieldsConfigExtra()]
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
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($id, self::identicalTo($metadata))
            ->willReturn($normalizedId);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createValidationError(Constraint::VALUE, 'Only manageable entity can be updated.')
                    ->setSource(ErrorSource::createByPointer('/included/0'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpdateFlagForExistingIncludedEntity(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id, 'meta' => ['update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;
        $includedEntity = new \stdClass();

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $normalizedType,
                self::identicalTo($normalizedId),
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn($includedEntity);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra($this->context->getAction()), new FilterIdentifierFieldsConfigExtra()]
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
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($id, self::identicalTo($metadata))
            ->willReturn($normalizedId);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertNotNull($this->context->getIncludedEntities());
        $addedIncludedEntity = $this->context->getIncludedEntities()->get($normalizedType, $id);
        self::assertSame($includedEntity, $addedIncludedEntity);
        self::assertEquals(
            new IncludedEntityData('/included/0', 0, true, ApiAction::UPDATE),
            $this->context->getIncludedEntities()->getData($addedIncludedEntity)
        );
    }

    public function testProcessWithUpdateFlagForExistingIncludedEntityWithCompositeId(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id, 'meta' => ['update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = ['id1' => 'test', 'id2' => 1];
        $includedEntity = new \stdClass();

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $normalizedType,
                self::identicalTo($normalizedId),
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn($includedEntity);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra($this->context->getAction()), new FilterIdentifierFieldsConfigExtra()]
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
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($id, self::identicalTo($metadata))
            ->willReturn($normalizedId);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertNotNull($this->context->getIncludedEntities());
        $addedIncludedEntity = $this->context->getIncludedEntities()->get($normalizedType, $id);
        self::assertSame($includedEntity, $addedIncludedEntity);
        self::assertEquals(
            new IncludedEntityData('/included/0', 0, true, ApiAction::UPDATE),
            $this->context->getIncludedEntities()->getData($addedIncludedEntity)
        );
    }

    public function testProcessWithUpdateFlagForExistingIncludedEntityWhichDoesNotExistInDatabase(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id, 'meta' => ['update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $normalizedType,
                self::identicalTo($normalizedId),
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn(null);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra($this->context->getAction()), new FilterIdentifierFieldsConfigExtra()]
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
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($id, self::identicalTo($metadata))
            ->willReturn($normalizedId);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createValidationError(Constraint::ENTITY, 'The entity does not exist.')
                    ->setSource(ErrorSource::createByPointer('/included/0'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpdateFlagWhenAccessToEntityDenied(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id, 'meta' => ['update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;
        $accessDeniedException = new AccessDeniedException('No access to the entity.');

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $normalizedType,
                self::identicalTo($normalizedId),
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willThrowException($accessDeniedException);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra($this->context->getAction()), new FilterIdentifierFieldsConfigExtra()]
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
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($id, self::identicalTo($metadata))
            ->willReturn($normalizedId);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createByException($accessDeniedException)
                    ->setSource(ErrorSource::createByPointer('/included/0'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpdateFlagWhenSeveralEntitiesFound(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id, 'meta' => ['update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $normalizedType,
                self::identicalTo($normalizedId),
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willThrowException(new NonUniqueResultException());

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra($this->context->getAction()), new FilterIdentifierFieldsConfigExtra()]
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
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($id, self::identicalTo($metadata))
            ->willReturn($normalizedId);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createConflictValidationError('The upsert operation founds more than one entity.')
                    ->setSource(ErrorSource::createByPointer('/included/0'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpdateFlagWhenIncludedEntityTypeIsUnknown(): void
    {
        $type = 'testType';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => 'testId', 'meta' => ['update' => true]]
            ]
        ];

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willThrowException(new EntityAliasNotFoundException($type));
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createValidationError(Constraint::ENTITY_TYPE, 'Unknown entity type: testType.')
                    ->setSource(ErrorSource::createByPointer('/included/0/type'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpdateFlagWhenNormalizationOfIncludedEntityIdFailed(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id, 'meta' => ['update' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $exception = new \Exception('some error');

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::never())
            ->method('resolveManageableEntityClass');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra($this->context->getAction()), new FilterIdentifierFieldsConfigExtra()]
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
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($id, self::identicalTo($metadata))
            ->willThrowException($exception);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createValidationError(Constraint::ENTITY_ID)
                    ->setSource(ErrorSource::createByPointer('/included/0/id'))
                    ->setInnerException($exception)
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForInvalidUpsertFlag(): void
    {
        $type = 'testType';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => 'testId', 'meta' => ['upsert' => null]]
            ]
        ];
        $normalizedType = 'Test\Class';

        $this->doctrineHelper->expects(self::never())
            ->method('resolveManageableEntityClass');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'This value should be a boolean or an array of strings.'
                )->setSource(ErrorSource::createByPointer('/included/0/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpsertFlagAndUpsertOperationIsDisabled(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id, 'meta' => ['upsert' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;

        $config = new EntityDefinitionConfig();
        $fullConfig = new EntityDefinitionConfig();
        $fullConfig->getUpsertConfig()->setEnabled(false);

        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::never())
            ->method('resolveManageableEntityClass');
        $this->entityLoader->expects(self::never())
            ->method('findEntity');

        $this->configProvider->expects(self::exactly(2))
            ->method('getConfig')
            ->with($normalizedType, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturnCallback(function ($className, $version, $requestType, $extras) use ($config, $fullConfig) {
                return \count($extras) === 1
                    ? $this->getConfig($fullConfig)
                    : $this->getConfig($config);
            });
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
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($id, self::identicalTo($metadata))
            ->willReturn($normalizedId);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createValidationError(Constraint::VALUE, 'The upsert operation is not allowed.')
                    ->setSource(ErrorSource::createByPointer('/included/0/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpsertFlagByIdWhenItIsNotAllowed(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id, 'meta' => ['upsert' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;

        $config = new EntityDefinitionConfig();
        $fullConfig = new EntityDefinitionConfig();

        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::never())
            ->method('resolveManageableEntityClass');
        $this->entityLoader->expects(self::never())
            ->method('findEntity');

        $this->configProvider->expects(self::exactly(2))
            ->method('getConfig')
            ->with($normalizedType, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturnCallback(function ($className, $version, $requestType, $extras) use ($config, $fullConfig) {
                return \count($extras) === 1
                    ? $this->getConfig($fullConfig)
                    : $this->getConfig($config);
            });
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
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($id, self::identicalTo($metadata))
            ->willReturn($normalizedId);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'The upsert operation cannot use the entity identifier to find an entity.'
                )->setSource(ErrorSource::createByPointer('/included/0/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpsertFlagForExistingIncludedObject(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id, 'meta' => ['upsert' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;

        $config = new EntityDefinitionConfig();
        $fullConfig = new EntityDefinitionConfig();
        $fullConfig->getUpsertConfig()->setAllowedById(true);

        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn(null);

        $this->configProvider->expects(self::exactly(2))
            ->method('getConfig')
            ->with($normalizedType, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturnCallback(function ($className, $version, $requestType, $extras) use ($config, $fullConfig) {
                return \count($extras) === 1
                    ? $this->getConfig($fullConfig)
                    : $this->getConfig($config);
            });
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
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($id, self::identicalTo($metadata))
            ->willReturn($normalizedId);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createValidationError(Constraint::VALUE, 'Only manageable entity can be updated.')
                    ->setSource(ErrorSource::createByPointer('/included/0'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpsertFlagForExistingIncludedEntity(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id, 'meta' => ['upsert' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;
        $includedEntity = new \stdClass();

        $config = new EntityDefinitionConfig();
        $fullConfig = new EntityDefinitionConfig();
        $fullConfig->getUpsertConfig()->setAllowedById(true);

        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $normalizedType,
                self::identicalTo($normalizedId),
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn($includedEntity);

        $this->configProvider->expects(self::exactly(2))
            ->method('getConfig')
            ->with($normalizedType, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturnCallback(function ($className, $version, $requestType, $extras) use ($config, $fullConfig) {
                return \count($extras) === 1
                    ? $this->getConfig($fullConfig)
                    : $this->getConfig($config);
            });
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
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($id, self::identicalTo($metadata))
            ->willReturn($normalizedId);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertNotNull($this->context->getIncludedEntities());
        $addedIncludedEntity = $this->context->getIncludedEntities()->get($normalizedType, $id);
        self::assertSame($includedEntity, $addedIncludedEntity);
        self::assertEquals(
            new IncludedEntityData('/included/0', 0, true, ApiAction::UPDATE),
            $this->context->getIncludedEntities()->getData($addedIncludedEntity)
        );
    }

    public function testProcessWithUpsertFlagForExistingIncludedEntityWhichDoesNotExistInDatabase(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => $id, 'meta' => ['upsert' => true]]
            ]
        ];
        $normalizedType = 'Test\Class';
        $normalizedId = 123;
        $includedEntity = new \stdClass();

        $config = new EntityDefinitionConfig();
        $fullConfig = new EntityDefinitionConfig();
        $fullConfig->getUpsertConfig()->setAllowedById(true);

        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $normalizedType,
                self::identicalTo($normalizedId),
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn(null);

        $this->configProvider->expects(self::exactly(3))
            ->method('getConfig')
            ->with($normalizedType, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturnCallback(function ($className, $version, $requestType, $extras) use ($config, $fullConfig) {
                if (\count($extras) === 1) {
                    return $this->getConfig($fullConfig);
                }

                return $extras[0]->getAction() === ApiAction::CREATE
                    ? $this->getConfig(new EntityDefinitionConfig())
                    : $this->getConfig($config);
            });
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
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);
        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($id, self::identicalTo($metadata))
            ->willReturn($normalizedId);

        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($normalizedType)
            ->willReturn($includedEntity);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertNotNull($this->context->getIncludedEntities());
        $addedIncludedEntity = $this->context->getIncludedEntities()->get($normalizedType, $id);
        self::assertSame($includedEntity, $addedIncludedEntity);
        self::assertEquals(
            new IncludedEntityData('/included/0', 0, false, ApiAction::CREATE),
            $this->context->getIncludedEntities()->getData($addedIncludedEntity)
        );
    }

    public function testProcessWithUpsertFlagBySpecifiedFieldWhenThisFieldCannotBeUsedToIdentifyEntity(): void
    {
        $type = 'testType';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => 'testId', 'meta' => ['upsert' => ['field3']]]
            ]
        ];
        $normalizedType = 'Test\Class';

        $fullConfig = new EntityDefinitionConfig();
        $fullConfig->getUpsertConfig()->addFields(['field1']);
        $fullConfig->getUpsertConfig()->addFields(['field2']);

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::never())
            ->method(self::anything());

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra($this->context->getAction())]
            )
            ->willReturn($this->getConfig($fullConfig));
        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'The upsert operation cannot use this field to find an entity.'
                )->setSource(ErrorSource::createByPointer('/included/0/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpsertFlagBySpecifiedFieldsWhenTheseFieldsCannotBeUsedToIdentifyEntity(): void
    {
        $type = 'testType';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => 'testId', 'meta' => ['upsert' => ['field2', 'field3']]]
            ]
        ];
        $normalizedType = 'Test\Class';

        $fullConfig = new EntityDefinitionConfig();
        $fullConfig->getUpsertConfig()->addFields(['field1']);
        $fullConfig->getUpsertConfig()->addFields(['field2']);

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::never())
            ->method(self::anything());

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra($this->context->getAction())]
            )
            ->willReturn($this->getConfig($fullConfig));
        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'The upsert operation cannot use these fields to find an entity.'
                )->setSource(ErrorSource::createByPointer('/included/0/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpsertFlagBySpecifiedFieldsWhenFindCriteriaCannotBeBuilt(): void
    {
        $type = 'testType';
        $requestData = [
            'included' => [
                ['type' => $type, 'id' => 'testId', 'meta' => ['upsert' => ['field1']]]
            ]
        ];
        $normalizedType = 'Test\Class';

        $fullConfig = new EntityDefinitionConfig();
        $fullConfig->getUpsertConfig()->addFields(['field1']);
        $fullConfig->getUpsertConfig()->addFields(['field2']);

        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::never())
            ->method(self::anything());

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra($this->context->getAction())]
            )
            ->willReturn($this->getConfig($fullConfig));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                self::identicalTo($fullConfig)
            )
            ->willReturn($metadata);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($type, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn($normalizedType);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'The "field1" field does not exist in the request data.'
                )->setSource(ErrorSource::createByPointer('/included/0/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpsertFlagBySpecifiedFieldsWhenEntityNotFound(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                [
                    'type'       => $type,
                    'id'         => $id,
                    'meta'       => ['upsert' => ['field1']],
                    'attributes' => ['field1' => 'val1', 'field2' => 'val2']
                ]
            ]
        ];
        $normalizedType = 'Test\Class';
        $includedEntity = new \stdClass();

        $config = new EntityDefinitionConfig();
        $fullConfig = new EntityDefinitionConfig();
        $fullConfig->getUpsertConfig()->addFields(['field1']);
        $fullConfig->getUpsertConfig()->addFields(['field2']);

        $fullMetadata = new EntityMetadata('Test\Entity');
        $fullMetadata->addField(new FieldMetadata('field1'))->setDataType('string');
        $fullMetadata->addField(new FieldMetadata('field2'))->setDataType('string');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::once())
            ->method('findEntityBy')
            ->with(
                $normalizedType,
                ['field1' => 'normalizedVal1'],
                self::identicalTo($fullConfig),
                self::identicalTo($fullMetadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn(null);

        $this->configProvider->expects(self::exactly(2))
            ->method('getConfig')
            ->with($normalizedType, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturnCallback(function ($className, $version, $requestType, $extras) use ($config, $fullConfig) {
                return \count($extras) === 1
                    ? $this->getConfig($fullConfig)
                    : $this->getConfig($config);
            });
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                self::identicalTo($fullConfig)
            )
            ->willReturn($fullMetadata);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                [$type, DataType::ENTITY_CLASS, $requestType, false, false, [], $normalizedType],
                ['val1', 'string', $requestType, false, false, [], 'normalizedVal1']
            ]);

        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($normalizedType)
            ->willReturn($includedEntity);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertNotNull($this->context->getIncludedEntities());
        $addedIncludedEntity = $this->context->getIncludedEntities()->get($normalizedType, $id);
        self::assertSame($includedEntity, $addedIncludedEntity);
        self::assertEquals(
            new IncludedEntityData('/included/0', 0, false, ApiAction::CREATE),
            $this->context->getIncludedEntities()->getData($addedIncludedEntity)
        );
    }

    public function testProcessWithUpsertFlagBySpecifiedFieldsWhenEntityFound(): void
    {
        $type = 'testType';
        $id = 'testId';
        $requestData = [
            'included' => [
                [
                    'type'       => $type,
                    'id'         => $id,
                    'meta'       => ['upsert' => ['field1']],
                    'attributes' => ['field1' => 'val1', 'field2' => 'val2']
                ]
            ]
        ];
        $normalizedType = 'Test\Class';
        $includedEntity = new \stdClass();

        $fullConfig = new EntityDefinitionConfig();
        $fullConfig->getUpsertConfig()->addFields(['field1']);
        $fullConfig->getUpsertConfig()->addFields(['field2']);

        $fullMetadata = new EntityMetadata('Test\Entity');
        $fullMetadata->addField(new FieldMetadata('field1'))->setDataType('string');
        $fullMetadata->addField(new FieldMetadata('field2'))->setDataType('string');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::once())
            ->method('findEntityBy')
            ->with(
                $normalizedType,
                ['field1' => 'normalizedVal1'],
                self::identicalTo($fullConfig),
                self::identicalTo($fullMetadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn($includedEntity);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra($this->context->getAction())]
            )
            ->willReturn($this->getConfig($fullConfig));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                self::identicalTo($fullConfig)
            )
            ->willReturn($fullMetadata);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                [$type, DataType::ENTITY_CLASS, $requestType, false, false, [], $normalizedType],
                ['val1', 'string', $requestType, false, false, [], 'normalizedVal1']
            ]);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
        self::assertNotNull($this->context->getIncludedEntities());
        $addedIncludedEntity = $this->context->getIncludedEntities()->get($normalizedType, $id);
        self::assertSame($includedEntity, $addedIncludedEntity);
        self::assertEquals(
            new IncludedEntityData('/included/0', 0, true, ApiAction::CREATE),
            $this->context->getIncludedEntities()->getData($addedIncludedEntity)
        );
    }

    public function testProcessWithUpsertFlagBySpecifiedFieldsWhenAccessToEntityDenied(): void
    {
        $type = 'testType';
        $requestData = [
            'included' => [
                [
                    'type'       => $type,
                    'id'         => 'testId',
                    'meta'       => ['upsert' => ['field1']],
                    'attributes' => ['field1' => 'val1', 'field2' => 'val2']
                ]
            ]
        ];
        $normalizedType = 'Test\Class';
        $accessDeniedException = new AccessDeniedException('No access to the entity.');

        $fullConfig = new EntityDefinitionConfig();
        $fullConfig->getUpsertConfig()->addFields(['field1']);
        $fullConfig->getUpsertConfig()->addFields(['field2']);

        $fullMetadata = new EntityMetadata('Test\Entity');
        $fullMetadata->addField(new FieldMetadata('field1'))->setDataType('string');
        $fullMetadata->addField(new FieldMetadata('field2'))->setDataType('string');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::once())
            ->method('findEntityBy')
            ->with(
                $normalizedType,
                ['field1' => 'normalizedVal1'],
                self::identicalTo($fullConfig),
                self::identicalTo($fullMetadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willThrowException($accessDeniedException);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra($this->context->getAction())]
            )
            ->willReturn($this->getConfig($fullConfig));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                self::identicalTo($fullConfig)
            )
            ->willReturn($fullMetadata);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                [$type, DataType::ENTITY_CLASS, $requestType, false, false, [], $normalizedType],
                ['val1', 'string', $requestType, false, false, [], 'normalizedVal1']
            ]);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createByException($accessDeniedException)
                    ->setSource(ErrorSource::createByPointer('/included/0'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithUpsertFlagBySpecifiedFieldsWhenSeveralEntitiesFound(): void
    {
        $type = 'testType';
        $requestData = [
            'included' => [
                [
                    'type'       => $type,
                    'id'         => 'testId',
                    'meta'       => ['upsert' => ['field1']],
                    'attributes' => ['field1' => 'val1', 'field2' => 'val2']
                ]
            ]
        ];
        $normalizedType = 'Test\Class';

        $fullConfig = new EntityDefinitionConfig();
        $fullConfig->getUpsertConfig()->addFields(['field1']);
        $fullConfig->getUpsertConfig()->addFields(['field2']);

        $fullMetadata = new EntityMetadata('Test\Entity');
        $fullMetadata->addField(new FieldMetadata('field1'))->setDataType('string');
        $fullMetadata->addField(new FieldMetadata('field2'))->setDataType('string');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($normalizedType)
            ->willReturn($normalizedType);
        $this->entityLoader->expects(self::once())
            ->method('findEntityBy')
            ->with(
                $normalizedType,
                ['field1' => 'normalizedVal1'],
                self::identicalTo($fullConfig),
                self::identicalTo($fullMetadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willThrowException(new NonUniqueResultException());

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra($this->context->getAction())]
            )
            ->willReturn($this->getConfig($fullConfig));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $normalizedType,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                self::identicalTo($fullConfig)
            )
            ->willReturn($fullMetadata);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                [$type, DataType::ENTITY_CLASS, $requestType, false, false, [], $normalizedType],
                ['val1', 'string', $requestType, false, false, [], 'normalizedVal1']
            ]);

        $this->context->setClassName('Test\PrimaryClass');
        $this->context->setId('primaryId');
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getIncludedEntities());
        self::assertEquals(
            [
                Error::createConflictValidationError('The upsert operation founds more than one entity.')
                    ->setSource(ErrorSource::createByPointer('/included/0'))
            ],
            $this->context->getErrors()
        );
    }
}
