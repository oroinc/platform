<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update\JsonApi;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\SetOperationFlags;
use Oro\Bundle\ApiBundle\Processor\Update\JsonApi\LoadUpsertEntity;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update\UpdateProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\AclProtectedEntityLoader;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;

class LoadUpsertEntityTest extends UpdateProcessorTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AclProtectedEntityLoader|\PHPUnit\Framework\MockObject\MockObject */
    private $entityLoader;

    /** @var EntityInstantiator|\PHPUnit\Framework\MockObject\MockObject */
    private $entityInstantiator;

    /** @var EntityIdHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityIdHelper;

    /** @var LoadUpsertEntity */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityLoader = $this->createMock(AclProtectedEntityLoader::class);
        $this->entityInstantiator = $this->createMock(EntityInstantiator::class);
        $this->entityIdHelper = $this->createMock(EntityIdHelper::class);

        $this->processor = new LoadUpsertEntity(
            $this->doctrineHelper,
            $this->entityLoader,
            $this->entityInstantiator,
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
        $this->entityInstantiator->expects(self::never())
            ->method('instantiate');

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
        $newEntity = new \stdClass();

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
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with($resolvedEntityClass)
            ->willReturn($newEntity);
        $this->entityIdHelper->expects(self::once())
            ->method('setEntityIdentifier')
            ->with(self::identicalTo($newEntity), $entityId, self::identicalTo($metadata));

        $this->context->set(SetOperationFlags::UPSERT_FLAG, true);
        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setMetadata($metadata);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertSame($newEntity, $this->context->getResult());
        self::assertFalse($this->context->isExisting());
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

    public function testProcessForUpsertBySpecifiedFields(): void
    {
        $entityClass = 'Test\Entity';
        $resolvedEntityClass = 'Test\ResolvedEntity';
        $entityId = 'test';
        $metadata = $this->createMock(EntityMetadata::class);
        $config = new EntityDefinitionConfig();
        $config->getUpsertConfig()->addFields(['field1']);
        $config->getUpsertConfig()->addFields(['field2']);
        $upsertFields = ['field1'];
        $requestData = ['data' => []];

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($resolvedEntityClass);
        $this->entityLoader->expects(self::never())
            ->method(self::anything());
        $this->entityInstantiator->expects(self::never())
            ->method('instantiate');
        $this->entityIdHelper->expects(self::never())
            ->method('setEntityIdentifier');

        $this->context->set(SetOperationFlags::UPSERT_FLAG, $upsertFields);
        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setMetadata($metadata);
        $this->context->setConfig($config);
        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'Only the entity identifier can be used by the upsert operation to find an entity.'
                )->setSource(ErrorSource::createByPointer('/meta/upsert'))
            ],
            $this->context->getErrors()
        );
    }
}
