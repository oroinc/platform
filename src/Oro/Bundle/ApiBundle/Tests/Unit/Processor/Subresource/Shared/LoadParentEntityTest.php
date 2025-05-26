<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\LoadParentEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\AclProtectedEntityLoader;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LoadParentEntityTest extends ChangeRelationshipProcessorTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private AclProtectedEntityLoader&MockObject $entityLoader;
    private LoadParentEntity $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityLoader = $this->createMock(AclProtectedEntityLoader::class);

        $this->processor = new LoadParentEntity($this->doctrineHelper, $this->entityLoader);
    }

    public function testProcessWhenParentEntityIsAlreadyLoaded(): void
    {
        $parentEntity = new \stdClass();

        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');
        $this->entityLoader->expects(self::never())
            ->method('findEntity');

        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);

        self::assertSame($parentEntity, $this->context->getParentEntity());
    }

    public function testProcessForNotManageableEntity(): void
    {
        $parentClass = 'Test\Class';
        $parentConfig = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentClass, $parentConfig)
            ->willReturn(null);
        $this->entityLoader->expects(self::never())
            ->method('findEntity');

        $this->context->setParentClassName($parentClass);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasParentEntity());
    }

    public function testProcessForManageableEntity(): void
    {
        $parentClass = 'Test\Class';
        $parentId = 123;
        $parentConfig = new EntityDefinitionConfig();
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentEntity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentClass, $parentConfig)
            ->willReturn($parentClass);

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $parentClass,
                $parentId,
                self::identicalTo($parentConfig),
                self::identicalTo($parentMetadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn($parentEntity);

        $this->context->setParentClassName($parentClass);
        $this->context->setParentId($parentId);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        self::assertSame($parentEntity, $this->context->getParentEntity());
    }

    public function testProcessForManageableEntityWhenEntityNotFound(): void
    {
        $parentClass = 'Test\Class';
        $parentId = 123;
        $parentConfig = new EntityDefinitionConfig();
        $parentMetadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentClass, $parentConfig)
            ->willReturn($parentClass);

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $parentClass,
                $parentId,
                self::identicalTo($parentConfig),
                self::identicalTo($parentMetadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn(null);

        $this->context->setParentClassName($parentClass);
        $this->context->setParentId($parentId);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasParentEntity());
    }

    public function testProcessForManageableEntityWhenNoAccessToEntity(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('No access to the parent entity.');

        $parentClass = 'Test\Class';
        $parentId = 123;
        $parentConfig = new EntityDefinitionConfig();
        $parentMetadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentClass, $parentConfig)
            ->willReturn($parentClass);

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $parentClass,
                $parentId,
                self::identicalTo($parentConfig),
                self::identicalTo($parentMetadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willThrowException(new AccessDeniedException('No access to the entity.'));

        $this->context->setParentClassName($parentClass);
        $this->context->setParentId($parentId);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasParentEntity());
    }

    public function testProcessForResourceBasedOnManageableEntity(): void
    {
        $parentClass = 'Test\Class';
        $parentResourceClass = 'Test\ParentResourceClass';
        $parentId = 123;
        $parentConfig = new EntityDefinitionConfig();
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentEntity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentResourceClass, $parentConfig)
            ->willReturn($parentClass);

        $this->entityLoader->expects(self::once())
            ->method('findEntity')
            ->with(
                $parentClass,
                $parentId,
                self::identicalTo($parentConfig),
                self::identicalTo($parentMetadata),
                self::identicalTo($this->context->getRequestType())
            )
            ->willReturn($parentEntity);

        $this->context->setParentClassName($parentResourceClass);
        $this->context->setParentId($parentId);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);

        self::assertSame($parentEntity, $this->context->getParentEntity());
    }
}
