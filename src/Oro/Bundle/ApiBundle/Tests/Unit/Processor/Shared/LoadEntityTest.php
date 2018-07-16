<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityLoader;

class LoadEntityTest extends GetProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityLoader */
    private $entityLoader;

    /** @var LoadEntity */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityLoader = $this->createMock(EntityLoader::class);

        $this->processor = new LoadEntity(
            $this->doctrineHelper,
            $this->entityLoader
        );
    }

    public function testProcessWhenEntityAlreadyLoaded()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');
        $this->entityLoader->expects(self::never())
            ->method('findEntity');

        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testProcessForNotExistingEntity()
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;
        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityClass);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($entityClass, $entityId, $metadata)
            ->willReturn(null);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessForNotManageableEntity()
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

    public function testProcessForManageableEntity()
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;
        $entity = new \stdClass();
        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($entityClass);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($entityClass, $entityId, $metadata)
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessForManageableEntityWhenEntityIsNotLoaded()
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;
        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($entityClass);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($entityClass, $entityId, $metadata)
            ->willReturn(null);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessForApiResourceBasedOnManageableEntity()
    {
        $entityClass = UserProfile::class;
        $parentEntityClass = User::class;
        $entityId = 123;
        $entity = new \stdClass();
        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentEntityClass);
        $metadata = new EntityMetadata();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($parentEntityClass);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($parentEntityClass, $entityId, $metadata)
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }
}
