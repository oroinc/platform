<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\AclProtectedEntityLoader;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LoadEntityTest extends GetProcessorTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private AclProtectedEntityLoader&MockObject $entityLoader;
    private LoadEntity $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityLoader = $this->createMock(AclProtectedEntityLoader::class);

        $this->processor = new LoadEntity($this->doctrineHelper, $this->entityLoader);
    }

    public function testProcessWhenEntityAlreadyLoaded(): void
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');
        $this->entityLoader->expects(self::never())
            ->method('findEntity');

        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity(): void
    {
        $entityClass = 'Test\Entity';
        $config = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn(null);
        $this->entityLoader->expects(self::never())
            ->method('findEntity');

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testProcessForManageableEntity(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;
        $entity = new \stdClass();
        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($entityClass);

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $entityClass,
                $entityId,
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessForManageableEntityWhenEntityNotFound(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;
        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityClass);


        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $entityClass,
                $entityId,
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn(null);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessForManageableEntityWhenNoAccessToEntity(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('No access to the entity.');

        $entityClass = 'Test\Entity';
        $entityId = 123;
        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityClass);

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $entityClass,
                $entityId,
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willThrowException(new AccessDeniedException('No access to the entity.'));

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessForApiResourceBasedOnManageableEntity(): void
    {
        $entityClass = UserProfile::class;
        $parentEntityClass = User::class;
        $entityId = 123;
        $entity = new \stdClass();
        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentEntityClass);
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($parentEntityClass);

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $parentEntityClass,
                $entityId,
                self::identicalTo($config),
                self::identicalTo($metadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }
}
