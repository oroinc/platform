<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\ApiBundle\Util\QueryAclHelper;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LoadEntityTest extends GetProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdHelper */
    private $entityIdHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryAclHelper */
    private $queryAclHelper;

    /** @var LoadEntity */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityIdHelper = $this->createMock(EntityIdHelper::class);
        $this->queryAclHelper = $this->createMock(QueryAclHelper::class);

        $this->processor = new LoadEntity(
            $this->doctrineHelper,
            $this->entityIdHelper,
            $this->queryAclHelper
        );
    }

    public function testProcessWhenEntityAlreadyLoaded()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');
        $this->doctrineHelper->expects(self::never())
            ->method('createQueryBuilder');

        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $entityClass = 'Test\Entity';
        $config = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn(null);
        $this->doctrineHelper->expects(self::never())
            ->method('createQueryBuilder');

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
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($entityClass);

        $qb = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with($entityClass, 'e')
            ->willReturn($qb);

        $this->entityIdHelper->expects(self::once())
            ->method('applyEntityIdentifierRestriction')
            ->with(self::identicalTo($qb), $entityId, self::identicalTo($metadata));

        $query = $this->createMock(AbstractQuery::class);
        $this->queryAclHelper->expects(self::once())
            ->method('protectQuery')
            ->with(self::identicalTo($qb), self::identicalTo($config), $this->context->getRequestType())
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessForManageableEntityWhenEntityNotFound()
    {
        $entityClass = 'Test\Entity';
        $entityId = 123;
        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityClass);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with($entityClass)
            ->willReturn(['id']);

        $qb = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->with($entityClass, 'e')
            ->willReturn($qb);

        $this->entityIdHelper->expects(self::exactly(2))
            ->method('applyEntityIdentifierRestriction')
            ->with(self::identicalTo($qb), $entityId, self::identicalTo($metadata));

        $query = $this->createMock(AbstractQuery::class);
        $this->queryAclHelper->expects(self::once())
            ->method('protectQuery')
            ->with(self::identicalTo($qb), self::identicalTo($config), $this->context->getRequestType())
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $notAclProtectedQuery = $this->createMock(AbstractQuery::class);
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($notAclProtectedQuery);
        $notAclProtectedQuery->expects(self::once())
            ->method('getOneOrNullResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn(null);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessForManageableEntityWhenNoAccessToEntity()
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
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with($entityClass)
            ->willReturn(['id']);

        $qb = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->with($entityClass, 'e')
            ->willReturn($qb);

        $this->entityIdHelper->expects(self::exactly(2))
            ->method('applyEntityIdentifierRestriction')
            ->with(self::identicalTo($qb), $entityId, self::identicalTo($metadata));

        $query = $this->createMock(AbstractQuery::class);
        $this->queryAclHelper->expects(self::once())
            ->method('protectQuery')
            ->with(self::identicalTo($qb), self::identicalTo($config), $this->context->getRequestType())
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $notAclProtectedQuery = $this->createMock(AbstractQuery::class);
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($notAclProtectedQuery);
        $notAclProtectedQuery->expects(self::once())
            ->method('getOneOrNullResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn(['id' => $entityId]);

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
        $metadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($parentEntityClass);

        $qb = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with($parentEntityClass, 'e')
            ->willReturn($qb);

        $this->entityIdHelper->expects(self::once())
            ->method('applyEntityIdentifierRestriction')
            ->with(self::identicalTo($qb), $entityId, self::identicalTo($metadata));

        $query = $this->createMock(AbstractQuery::class);
        $this->queryAclHelper->expects(self::once())
            ->method('protectQuery')
            ->with(self::identicalTo($qb), self::identicalTo($config), $this->context->getRequestType())
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($entity);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setConfig($config);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }
}
