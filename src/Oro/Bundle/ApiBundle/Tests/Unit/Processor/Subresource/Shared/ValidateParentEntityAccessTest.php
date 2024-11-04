<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityAccess;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\ApiBundle\Util\QueryAclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ValidateParentEntityAccessTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var EntityIdHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityIdHelper;

    /** @var QueryAclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $queryAclHelper;

    /** @var QueryHintResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $queryHintResolver;

    /** @var ValidateParentEntityAccess */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityIdHelper = $this->createMock(EntityIdHelper::class);
        $this->queryAclHelper = $this->createMock(QueryAclHelper::class);
        $this->queryHintResolver = $this->createMock(QueryHintResolverInterface::class);

        $this->processor = new ValidateParentEntityAccess(
            $this->doctrineHelper,
            $this->entityIdHelper,
            $this->queryAclHelper,
            $this->queryHintResolver
        );
    }

    public function testProcessForSubresourceThatDoesNotAssociatedWithAnyFieldInParentEntityConfig()
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentMetadata = new EntityMetadata('Test\Entity');

        $this->doctrineHelper->expects(self::never())
            ->method('getManageableEntityClass');

        $this->context->setParentClassName('Test\Class');
        $this->context->setParentId(-1);
        $this->context->setAssociationName('association');
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $parentClass = 'Test\Class';
        $associationName = 'association';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentClass, $parentConfig)
            ->willReturn(null);

        $this->context->setParentClassName($parentClass);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);
    }

    public function testProcessForManageableEntity()
    {
        $parentClass = 'Test\Class';
        $parentId = 123;
        $associationName = 'association';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName);
        $parentMetadata = new EntityMetadata($parentClass);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentClass, $parentConfig)
            ->willReturn($parentClass);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with($parentClass)
            ->willReturn(['id']);

        $qb = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with($parentClass, 'e')
            ->willReturn($qb);

        $this->entityIdHelper->expects(self::once())
            ->method('applyEntityIdentifierRestriction')
            ->with(self::identicalTo($qb), $parentId, self::identicalTo($parentMetadata));

        $query = $this->createMock(AbstractQuery::class);
        $this->queryAclHelper->expects(self::once())
            ->method('protectQuery')
            ->with(self::identicalTo($qb), self::identicalTo($parentConfig), $this->context->getRequestType())
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn(['id' => $parentId]);

        $this->context->setParentClassName($parentClass);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);
    }

    public function testProcessForManageableEntityWhenEntityNotFound()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('The parent entity does not exist.');

        $parentClass = Group::class;
        $parentId = 123;
        $associationName = 'association';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName);
        $parentConfig->addHint('HINT_TEST');
        $parentMetadata = new EntityMetadata($parentClass);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentClass, $parentConfig)
            ->willReturn($parentClass);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with($parentClass)
            ->willReturn(['id']);

        $qb = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->with($parentClass, 'e')
            ->willReturn($qb);

        $this->entityIdHelper->expects(self::exactly(2))
            ->method('applyEntityIdentifierRestriction')
            ->with(self::identicalTo($qb), $parentId, self::identicalTo($parentMetadata));

        $query = $this->createMock(AbstractQuery::class);
        $this->queryAclHelper->expects(self::once())
            ->method('protectQuery')
            ->with(self::identicalTo($qb), self::identicalTo($parentConfig), $this->context->getRequestType())
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn(null);

        $notAclProtectedQuery = new Query($this->em);
        $notAclProtectedQuery->setDQL(sprintf('SELECT e.id FROM %s AS e', $parentClass));
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($notAclProtectedQuery);
        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            $notAclProtectedQuery->getSQL(),
            []
        );

        $this->queryHintResolver->expects(self::once())
            ->method('resolveHints')
            ->with(self::identicalTo($notAclProtectedQuery), ['HINT_TEST']);

        $this->context->setParentClassName($parentClass);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);
    }

    public function testProcessForManageableEntityWhenNoAccessToEntity()
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('No access to the parent entity.');

        $parentClass = Group::class;
        $parentId = 123;
        $associationName = 'association';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName);
        $parentConfig->addHint('HINT_TEST');
        $parentMetadata = new EntityMetadata($parentClass);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentClass, $parentConfig)
            ->willReturn($parentClass);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with($parentClass)
            ->willReturn(['id']);

        $qb = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->with($parentClass, 'e')
            ->willReturn($qb);

        $this->entityIdHelper->expects(self::exactly(2))
            ->method('applyEntityIdentifierRestriction')
            ->with(self::identicalTo($qb), $parentId, self::identicalTo($parentMetadata));

        $query = $this->createMock(AbstractQuery::class);
        $this->queryAclHelper->expects(self::once())
            ->method('protectQuery')
            ->with(self::identicalTo($qb), self::identicalTo($parentConfig), $this->context->getRequestType())
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn(null);

        $notAclProtectedQuery = new Query($this->em);
        $notAclProtectedQuery->setDQL(sprintf('SELECT e.id FROM %s AS e', $parentClass));
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($notAclProtectedQuery);
        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            $notAclProtectedQuery->getSQL(),
            [['id_0' => $parentId]]
        );

        $this->queryHintResolver->expects(self::once())
            ->method('resolveHints')
            ->with(self::identicalTo($notAclProtectedQuery), ['HINT_TEST']);

        $this->context->setParentClassName($parentClass);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);
    }

    public function testProcessForResourceBasedOnManageableEntity()
    {
        $parentClass = 'Test\Class';
        $parentResourceClass = 'Test\ParentResourceClass';
        $parentId = 123;
        $associationName = 'association';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName);
        $parentMetadata = new EntityMetadata($parentClass);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($parentResourceClass, $parentConfig)
            ->willReturn($parentClass);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with($parentClass)
            ->willReturn(['id']);

        $qb = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with($parentClass, 'e')
            ->willReturn($qb);

        $this->entityIdHelper->expects(self::once())
            ->method('applyEntityIdentifierRestriction')
            ->with(self::identicalTo($qb), $parentId, self::identicalTo($parentMetadata));

        $query = $this->createMock(AbstractQuery::class);
        $this->queryAclHelper->expects(self::once())
            ->method('protectQuery')
            ->with(self::identicalTo($qb), self::identicalTo($parentConfig), $this->context->getRequestType())
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn(['id' => $parentId]);

        $this->context->setParentClassName($parentResourceClass);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);
    }
}
