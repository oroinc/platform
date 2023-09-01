<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\JsonApi;

use Doctrine\ORM\NonUniqueResultException;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Create\JsonApi\LoadUpsertEntity;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\SetOperationFlags;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\CreateProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\AclProtectedEntityLoader;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\ApiBundle\Util\UpsertCriteriaBuilder;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LoadUpsertEntityTest extends CreateProcessorTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AclProtectedEntityLoader|\PHPUnit\Framework\MockObject\MockObject */
    private $entityLoader;

    /** @var UpsertCriteriaBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $upsertCriteriaBuilder;

    /** @var EntityIdHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityIdHelper;

    /** @var LoadUpsertEntity */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityLoader = $this->createMock(AclProtectedEntityLoader::class);
        $this->upsertCriteriaBuilder = $this->createMock(UpsertCriteriaBuilder::class);
        $this->entityIdHelper = $this->createMock(EntityIdHelper::class);

        $this->processor = new LoadUpsertEntity(
            $this->doctrineHelper,
            $this->entityLoader,
            $this->upsertCriteriaBuilder,
            $this->entityIdHelper
        );
    }

    public function testProcessWhenEntityAlreadyLoaded(): void
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::never())
            ->method('resolveManageableEntityClass');

        $this->context->setResult($entity);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessWhenUpsertOperationWasNotRequested(): void
    {
        $this->doctrineHelper->expects(self::never())
            ->method('resolveManageableEntityClass');

        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
    }

    public function testProcessForNotManageableEntity(): void
    {
        $entityClass = 'Test\Entity';

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn(null);
        $this->entityLoader->expects(self::never())
            ->method(self::anything());

        $this->context->set(SetOperationFlags::UPSERT_FLAG, true);
        $this->context->setClassName($entityClass);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
    }

    public function testProcessWhenNoMetadata(): void
    {
        $entityClass = 'Test\Entity';

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn('Test\ResolvedEntity');
        $this->entityLoader->expects(self::never())
            ->method(self::anything());

        $this->context->set(SetOperationFlags::UPSERT_FLAG, true);
        $this->context->setClassName($entityClass);
        $this->context->setMetadata(null);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
    }

    public function testProcessUpsertOperationIsDisabled(): void
    {
        $entityClass = 'Test\Entity';
        $config = new EntityDefinitionConfig();
        $config->getUpsertConfig()->setEnabled(false);

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn('Test\ResolvedEntity');
        $this->entityLoader->expects(self::never())
            ->method(self::anything());

        $this->context->set(SetOperationFlags::UPSERT_FLAG, 'unsupported');
        $this->context->setClassName($entityClass);
        $this->context->setId('test');
        $this->context->setMetadata($this->createMock(EntityMetadata::class));
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
        self::assertEquals(
            [
                Error::createValidationError(Constraint::VALUE, 'The upsert operation is not allowed.')
                    ->setSource(ErrorSource::createByPointer('/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForUpsertByIdWhenItIsNotAllowed(): void
    {
        $entityClass = 'Test\Entity';

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn('Test\ResolvedEntity');
        $this->entityLoader->expects(self::never())
            ->method('findEntity');

        $this->context->set(SetOperationFlags::UPSERT_FLAG, true);
        $this->context->setClassName($entityClass);
        $this->context->setId('test');
        $this->context->setMetadata($this->createMock(EntityMetadata::class));
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'The upsert operation cannot use the entity identifier to find an entity.'
                )->setSource(ErrorSource::createByPointer('/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForUpsertByIdWhenEntityNotFound(): void
    {
        $entityClass = 'Test\Entity';
        $resolvedEntityClass = 'Test\ResolvedEntity';
        $entityId = 'test';
        $metadata = $this->createMock(EntityMetadata::class);
        $config = new EntityDefinitionConfig();
        $config->getUpsertConfig()->setAllowedById(true);

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($resolvedEntityClass);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $resolvedEntityClass,
                $entityId,
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn(null);

        $this->context->set(SetOperationFlags::UPSERT_FLAG, true);
        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setMetadata($metadata);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
    }

    public function testProcessForUpsertByIdWhenEntityFound(): void
    {
        $entityClass = 'Test\Entity';
        $resolvedEntityClass = 'Test\ResolvedEntity';
        $entityId = 'test';
        $metadata = $this->createMock(EntityMetadata::class);
        $config = new EntityDefinitionConfig();
        $config->getUpsertConfig()->setAllowedById(true);
        $foundEntity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($resolvedEntityClass);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $resolvedEntityClass,
                $entityId,
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn($foundEntity);

        $this->context->set(SetOperationFlags::UPSERT_FLAG, true);
        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setMetadata($metadata);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertSame($foundEntity, $this->context->getResult());
        self::assertTrue($this->context->isExisting());
    }

    public function testProcessForUpsertBySpecifiedFieldWhenThisFieldCannotBeUsedToIdentifyEntity(): void
    {
        $entityClass = 'Test\Entity';
        $metadata = $this->createMock(EntityMetadata::class);
        $config = new EntityDefinitionConfig();
        $config->getUpsertConfig()->addFields(['field1']);
        $config->getUpsertConfig()->addFields(['field2']);

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn('Test\ResolvedEntity');
        $this->upsertCriteriaBuilder->expects(self::never())
            ->method('getUpsertFindEntityCriteria');
        $this->entityLoader->expects(self::never())
            ->method(self::anything());

        $this->context->set(SetOperationFlags::UPSERT_FLAG, ['field3']);
        $this->context->setClassName($entityClass);
        $this->context->setMetadata($metadata);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'The upsert operation cannot use this field to find an entity.'
                )->setSource(ErrorSource::createByPointer('/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForUpsertBySpecifiedFieldsWhenTheseFieldsCannotBeUsedToIdentifyEntity(): void
    {
        $entityClass = 'Test\Entity';
        $metadata = $this->createMock(EntityMetadata::class);
        $config = new EntityDefinitionConfig();
        $config->getUpsertConfig()->addFields(['field1']);
        $config->getUpsertConfig()->addFields(['field2']);

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn('Test\ResolvedEntity');
        $this->upsertCriteriaBuilder->expects(self::never())
            ->method('getUpsertFindEntityCriteria');
        $this->entityLoader->expects(self::never())
            ->method(self::anything());

        $this->context->set(SetOperationFlags::UPSERT_FLAG, ['field2', 'field3']);
        $this->context->setClassName($entityClass);
        $this->context->setMetadata($metadata);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'The upsert operation cannot use these fields to find an entity.'
                )->setSource(ErrorSource::createByPointer('/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForUpsertBySpecifiedFieldsWhenFindCriteriaCannotBeBuilt(): void
    {
        $entityClass = 'Test\Entity';
        $metadata = $this->createMock(EntityMetadata::class);
        $config = new EntityDefinitionConfig();
        $config->getUpsertConfig()->addFields(['field1']);
        $config->getUpsertConfig()->addFields(['field2']);
        $upsertFields = ['field1'];
        $requestData = ['data' => []];

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn('Test\ResolvedEntity');
        $this->upsertCriteriaBuilder->expects(self::once())
            ->method('getUpsertFindEntityCriteria')
            ->with(
                self::identicalTo($metadata),
                $upsertFields,
                $requestData,
                '/meta/upsert',
                self::identicalTo($this->context)
            )
            ->willReturn(null);
        $this->entityLoader->expects(self::never())
            ->method(self::anything());

        $this->context->set(SetOperationFlags::UPSERT_FLAG, $upsertFields);
        $this->context->setClassName($entityClass);
        $this->context->setMetadata($metadata);
        $this->context->setConfig($config);
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
    }

    public function testProcessForUpsertBySpecifiedFieldsWhenEntityNotFound(): void
    {
        $entityClass = 'Test\Entity';
        $resolvedEntityClass = 'Test\ResolvedEntity';
        $metadata = $this->createMock(EntityMetadata::class);
        $config = new EntityDefinitionConfig();
        $config->getUpsertConfig()->addFields(['field1']);
        $config->getUpsertConfig()->addFields(['field2']);
        $upsertFields = ['field1'];
        $requestData = ['data' => []];
        $findCriteria = ['field1' => 123];

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($resolvedEntityClass);
        $this->upsertCriteriaBuilder->expects(self::once())
            ->method('getUpsertFindEntityCriteria')
            ->with(
                self::identicalTo($metadata),
                $upsertFields,
                $requestData,
                '/meta/upsert',
                self::identicalTo($this->context)
            )
            ->willReturn($findCriteria);
        $this->entityLoader->expects(self::once())
            ->method('findEntityBy')
            ->with(
                $resolvedEntityClass,
                $findCriteria,
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn(null);
        $this->entityIdHelper->expects(self::never())
            ->method('getEntityIdentifier');

        $this->context->set(SetOperationFlags::UPSERT_FLAG, $upsertFields);
        $this->context->setClassName($entityClass);
        $this->context->setMetadata($metadata);
        $this->context->setConfig($config);
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
    }

    public function testProcessForUpsertBySpecifiedFieldsWhenEntityFound(): void
    {
        $entityClass = 'Test\Entity';
        $resolvedEntityClass = 'Test\ResolvedEntity';
        $metadata = $this->createMock(EntityMetadata::class);
        $config = new EntityDefinitionConfig();
        $config->getUpsertConfig()->addFields(['field1']);
        $config->getUpsertConfig()->addFields(['field2']);
        $upsertFields = ['field1'];
        $requestData = ['data' => []];
        $findCriteria = ['field1' => 123];
        $foundEntity = new \stdClass();
        $foundEntityId = 1;

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($resolvedEntityClass);
        $this->upsertCriteriaBuilder->expects(self::once())
            ->method('getUpsertFindEntityCriteria')
            ->with(
                self::identicalTo($metadata),
                $upsertFields,
                $requestData,
                '/meta/upsert',
                self::identicalTo($this->context)
            )
            ->willReturn($findCriteria);
        $this->entityLoader->expects(self::once())
            ->method('findEntityBy')
            ->with(
                $resolvedEntityClass,
                $findCriteria,
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn($foundEntity);
        $this->entityIdHelper->expects(self::once())
            ->method('getEntityIdentifier')
            ->with(self::identicalTo($foundEntity), self::identicalTo($metadata))
            ->willReturn($foundEntityId);

        $this->context->set(SetOperationFlags::UPSERT_FLAG, $upsertFields);
        $this->context->setClassName($entityClass);
        $this->context->setMetadata($metadata);
        $this->context->setConfig($config);
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertSame($foundEntity, $this->context->getResult());
        self::assertTrue($this->context->isExisting());
        self::assertSame($foundEntityId, $this->context->getId());
    }

    public function testProcessForUpsertBySpecifiedFieldsWhenSeveralEntitiesFound(): void
    {
        $entityClass = 'Test\Entity';
        $resolvedEntityClass = 'Test\ResolvedEntity';
        $metadata = $this->createMock(EntityMetadata::class);
        $config = new EntityDefinitionConfig();
        $config->getUpsertConfig()->addFields(['field1']);
        $config->getUpsertConfig()->addFields(['field2']);
        $upsertFields = ['field1'];
        $requestData = ['data' => []];
        $findCriteria = ['field1' => 123];

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($resolvedEntityClass);
        $this->upsertCriteriaBuilder->expects(self::once())
            ->method('getUpsertFindEntityCriteria')
            ->with(
                self::identicalTo($metadata),
                $upsertFields,
                $requestData,
                '/meta/upsert',
                self::identicalTo($this->context)
            )
            ->willReturn($findCriteria);
        $this->entityLoader->expects(self::once())
            ->method('findEntityBy')
            ->with(
                $resolvedEntityClass,
                $findCriteria,
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willThrowException(new NonUniqueResultException());
        $this->entityIdHelper->expects(self::never())
            ->method('getEntityIdentifier');

        $this->context->set(SetOperationFlags::UPSERT_FLAG, $upsertFields);
        $this->context->setClassName($entityClass);
        $this->context->setMetadata($metadata);
        $this->context->setConfig($config);
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
        self::assertEquals(
            [
                Error::createConflictValidationError('The upsert operation founds more than one entity.')
            ],
            $this->context->getErrors()
        );
    }
}
