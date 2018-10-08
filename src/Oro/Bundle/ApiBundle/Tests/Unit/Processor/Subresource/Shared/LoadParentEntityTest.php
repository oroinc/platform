<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\LoadParentEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityLoader;

class LoadParentEntityTest extends GetSubresourceProcessorTestCase
{
    private const TEST_PARENT_CLASS_NAME = 'Test\Class';

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityLoader */
    private $entityLoader;

    /** @var LoadParentEntity */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityLoader = $this->createMock(EntityLoader::class);

        $this->processor = new LoadParentEntity($this->doctrineHelper, $this->entityLoader);
    }

    public function testProcessWhenParentEntityIsAlreadyLoaded()
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');

        $this->context->setParentEntity($entity);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getParentEntity());
    }

    public function testProcessForNotManageableEntity()
    {
        $parentConfig = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with(self::TEST_PARENT_CLASS_NAME, $parentConfig)
            ->willReturn(null);

        $this->context->setParentClassName(self::TEST_PARENT_CLASS_NAME);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasParentEntity());
    }

    public function testProcessForManageableEntity()
    {
        $parentId = 123;
        $parentConfig = new EntityDefinitionConfig();
        $parentMetadata = new EntityMetadata();
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with(self::TEST_PARENT_CLASS_NAME, $parentConfig)
            ->willReturn(self::TEST_PARENT_CLASS_NAME);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(self::TEST_PARENT_CLASS_NAME, $parentId, self::identicalTo($parentMetadata))
            ->willReturn($entity);

        $this->context->setParentClassName(self::TEST_PARENT_CLASS_NAME);
        $this->context->setParentId($parentId);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getParentEntity());
    }

    public function testProcessForManageableEntityWhenEntityNotFound()
    {
        $parentId = 123;
        $parentConfig = new EntityDefinitionConfig();
        $parentMetadata = new EntityMetadata();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with(self::TEST_PARENT_CLASS_NAME, $parentConfig)
            ->willReturn(self::TEST_PARENT_CLASS_NAME);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(self::TEST_PARENT_CLASS_NAME, $parentId, self::identicalTo($parentMetadata))
            ->willReturn(null);

        $this->context->setParentClassName(self::TEST_PARENT_CLASS_NAME);
        $this->context->setParentId($parentId);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        self::assertNull($this->context->getParentEntity());
        self::assertTrue($this->context->hasParentEntity());
    }

    public function testProcessForResourceBasedOnManageableEntity()
    {
        $parentResourceClass = 'Test\ParentResourceClass';
        $parentId = 123;
        $parentMetadata = new EntityMetadata();
        $entity = new \stdClass();

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setParentResourceClass($parentResourceClass);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->willReturn($parentResourceClass);
        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with($parentResourceClass, $parentId, self::identicalTo($parentMetadata))
            ->willReturn($entity);

        $this->context->setParentClassName(self::TEST_PARENT_CLASS_NAME);
        $this->context->setParentId($parentId);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getParentEntity());
    }

    public function testProcessForResourceBasedOnNotManageableEntity()
    {
        $parentResourceClass = 'Test\ParentResourceClass';
        $parentId = 123;
        $parentMetadata = new EntityMetadata();

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setParentResourceClass($parentResourceClass);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->willReturn(null);
        $this->entityLoader->expects(self::never())
            ->method('findEntity');

        $this->context->setParentClassName(self::TEST_PARENT_CLASS_NAME);
        $this->context->setParentId($parentId);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        self::assertNull($this->context->getParentEntity());
    }
}
